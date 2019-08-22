<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;
use Thermo\Model\Device;
use Thermo\Util;

/**
 * List all available peanut.
 */
class ListController
{
    protected $dynamap;
    protected $log;

    public function __construct(Dynamap $dynamap, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->log = $log;
    }

    public function list(Request $request, Response $response)
    {
        $this->log->notice('getting list');

        $devices = $this->dynamap->getAll(Device::class);

        $data = [];
        foreach ($devices as $device) {
            $flatDevice = $device->toArray();

            $flatDevice['last_update'] = $flatDevice['last_update'] ? $flatDevice['last_update']->format('Y-m-d\TH:i:s') : null;
            $flatDevice['last_battery'] = Util::convertBattery($flatDevice['last_battery']);
            $flatDevice['last_temperature'] = Util::convertTemperature($flatDevice['last_temperature']);

            $data[$flatDevice['sort']] = $flatDevice;
        }

        rsort($data);

        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
