<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use InfluxDB\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;
use Thermo\Model\Device;
use Thermo\Util;

/**
 * Retrieve information about one peanut.
 */
class DetailController
{
    /** @var Dynamap */
    protected $dynamap;
    /** @var Database */
    protected $influx;
    /** @var AbstractLogger */
    protected $log;

    public function __construct(Dynamap $dynamap, Database $influx, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->influx = $influx;
        $this->log = $log;
    }

    public function detail(Request $request, Response $response, array $args): Response
    {
        $start = microtime(true);
        $mac = $args['mac'];
        if (false === filter_var($mac, FILTER_VALIDATE_MAC)) {
            $response->getBody()->write((string) json_encode([
                'error' => 'Mac address (' . $mac . ') is not valid.',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

        /** @var Device */
        $deviceObj = $this->dynamap->get(Device::class, $mac);
        $device = $deviceObj->toArray();

        $rowsMax = $this->influx
            ->query('SELECT MAX("value")/100 FROM "temperature" WHERE mac = \'' . $mac . '\'')
            ->getPoints();

        $rowsMin = $this->influx
            ->query('SELECT MIN("value")/100 FROM "temperature" WHERE mac = \'' . $mac . '\'')
            ->getPoints();

        // data for 24h
        $rows24h = $this->influx
            ->query('SELECT MEAN("value")/100 FROM "temperature" WHERE time > now() - 24h AND "mac"=\'' . $mac . '\' GROUP BY time(30m)')
            ->getPoints();

        $last24h = Util::formatValueForGraph($rows24h, 'H:i');
        // the first value has the same `time` as the last one
        // remove it to avoid overlap on the chart
        array_shift($last24h);

        $data24h = $this->influx
            ->query('SELECT MEAN("value")/100, MIN("value")/100, MAX("value")/100 FROM "temperature" WHERE time > now() - 24h AND "mac"=\'' . $mac . '\'')
            ->getPoints();

        // data for 30d
        $rows30d = $this->influx
            ->query('SELECT MEAN("value")/100 FROM "temperature" WHERE time > now() - 30d AND "mac"=\'' . $mac . '\' GROUP BY time(1d)')
            ->getPoints();

        $last30d = Util::formatValueForGraph($rows30d, 'd');

        $data30d = $this->influx
            ->query('SELECT MEAN("value")/100, MIN("value")/100, MAX("value")/100 FROM "temperature" WHERE time > now() - 30d AND "mac"=\'' . $mac . '\'')
            ->getPoints();

        // data for 52w (a year)
        $rows52w = $this->influx
            ->query('SELECT MEAN("value")/100 FROM "temperature" WHERE time > now() - 52w AND "mac"=\'' . $mac . '\' GROUP BY time(4w)')
            ->getPoints();

        $last52w = Util::formatValueForGraph($rows52w, 'm');
        // the first value has the same `time` as the last one
        // remove it to avoid overlap on the chart
        array_shift($last52w);

        $data52w = $this->influx
            ->query('SELECT MEAN("value")/100, MIN("value")/100, MAX("value")/100 FROM "temperature" WHERE time > now() - 52w AND "mac"=\'' . $mac . '\'')
            ->getPoints();

        $end = round((microtime(true) - $start) * 1000);

        $this->log->notice('getting details for ' . $mac . ' in ' . $end . 'ms');

        $response->getBody()->write((string) json_encode([
            'max' => empty($rowsMax) ? null : $rowsMax[0]['max'],
            'max_date' => empty($rowsMax) ? null : $rowsMax[0]['time'],
            'min' => empty($rowsMax) ? null : $rowsMin[0]['min'],
            'min_date' => empty($rowsMax) ? null : $rowsMin[0]['time'],
            'last_24h' => $last24h,
            'mean_24h' => empty($data24h[0]) ? null : round($data24h[0]['mean'], 1),
            'min_24h' => empty($data24h[0]) ? null : round($data24h[0]['min'], 1),
            'max_24h' => empty($data24h[0]) ? null : round($data24h[0]['max'], 1),
            'last_30d' => $last30d,
            'mean_30d' => empty($data30d[0]) ? null : round($data30d[0]['mean'], 1),
            'min_30d' => empty($data30d[0]) ? null : round($data30d[0]['min'], 1),
            'max_30d' => empty($data30d[0]) ? null : round($data30d[0]['max'], 1),
            'last_52w' => $last52w,
            'mean_52w' => empty($data52w[0]) ? null : round($data52w[0]['mean'], 1),
            'min_52w' => empty($data52w[0]) ? null : round($data52w[0]['min'], 1),
            'max_52w' => empty($data52w[0]) ? null : round($data52w[0]['max'], 1),
        ] + $device));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
