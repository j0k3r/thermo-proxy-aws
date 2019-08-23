<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Thermo\Model\Device;

/**
 * This controller only create the default data in the table
 * using the `init_db.yml` file.
 *
 * If the table already contains information, it won't erase them.
 */
class DeviceController
{
    protected $dynamap;
    protected $log;

    public function __construct(Dynamap $dynamap, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->log = $log;
    }

    public function init(Request $request, Response $response, $args)
    {
        $this->log->notice('init devices');

        $devices = $this->dynamap->getAll(Device::class);

        // avoid overriding current database
        if (!empty($devices)) {
            $response->getBody()->write((string) json_encode([
                'error' => 'Some devices (' . \count($devices) . ') are already defined. Remove them to re-init the table.',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

        try {
            $devices = Yaml::parseFile(__DIR__ . '/../../init_db.yml');
        } catch (ParseException $exception) {
            $response->getBody()->write((string) json_encode([
                'error' => 'Error while reading the init_db.yml file',
                'exception' => $exception->getMessage(),
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

        $createdDevices = [];
        foreach ($devices['devices'] as $device) {
            $newDevice = new Device($device['mac']);
            $newDevice->setLabel($device['label']);
            $newDevice->setColor($device['color']);
            $newDevice->setSort($device['sort']);
            $newDevice->setLastUpdate($obj = (new \DateTimeImmutable())->setTimestamp(strtotime($device['last_update'])));
            $newDevice->setLastTemperature($device['last_temperature']);
            $newDevice->setLastBattery($device['last_battery']);

            $this->dynamap->save($newDevice);

            $createdDevices[] = $device['label'];
        }

        $response->getBody()->write((string) json_encode([
            'created' => $createdDevices,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
