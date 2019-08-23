<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use InfluxDB\Database;
use InfluxDB\Point;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;
use Thermo\Model\Device;

/**
 * The Sense Peanut App will post information on this endpoint.
 */
class EventController
{
    protected $dynamap;
    protected $influx;
    protected $log;

    public function __construct(Dynamap $dynamap, Database $influx, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->influx = $influx;
        $this->log = $log;
    }

    public function register(Request $request, Response $response, $args)
    {
        $start = microtime(true);

        $events = json_decode($request->getBody()->getContents(), true);

        foreach ($events as $event) {
            if ('temperature' === $event['type']) {
                $date = (\DateTime::createFromFormat('Y-m-d\TH:i:s.uO', $event['dateEvent']))
                    ->setTimezone(new \DateTimeZone('Europe/Paris'));

                $this->influx->writePoints([
                    new Point(
                        'temperature',
                        $event['data']['centidegreeCelsius'],
                        ['mac' => $args['mac']],
                        [],
                        $date->format('Uv')
                    ),
                ], Database::PRECISION_MILLISECONDS);

                $this->dynamap->partialUpdate(
                    Device::class,
                    $args['mac'],
                    [
                        'last_update' => $date,
                        'last_temperature' => $event['data']['centidegreeCelsius'],
                    ]
                );
            }

            if ('battery' === $event['type']) {
                $this->dynamap->partialUpdate(
                    Device::class,
                    $args['mac'],
                    [
                        'last_battery' => $event['data']['levelMillivolt'],
                    ]
                );
            }
        }

        $end = round((microtime(true) - $start) * 1000);

        $this->log->notice('inserted ' . \count($events) . ' events in ' . $end . 'ms');

        $response->getBody()->write(json_encode(['inserted' => \count($events)]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
