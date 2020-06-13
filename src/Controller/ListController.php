<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;
use Thermo\Model\Device;

/**
 * List all available peanut.
 */
class ListController
{
    /** @var Dynamap */
    protected $dynamap;
    /** @var AbstractLogger */
    protected $log;

    public function __construct(Dynamap $dynamap, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->log = $log;
    }

    public function list(Request $request, Response $response): Response
    {
        $start = microtime(true);

        $devices = $this->dynamap->getAll(Device::class);

        $data = [];
        foreach ($devices as $device) {
            $flatDevice = $device->toArray();

            $data[$flatDevice['sort']] = $flatDevice;
        }

        // sort device by sort ascending
        usort($data, function ($a, $b) {
            return $a['sort'] < $b['sort'] ? -1 : 1;
        });

        $end = round((microtime(true) - $start) * 1000);

        $this->log->notice('getting list in ' . $end . 'ms');

        $response->getBody()->write((string) json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
