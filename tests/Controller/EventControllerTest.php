<?php

namespace Tests\Thermo\Controller;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Thermo\Controller\EventController;
use Thermo\Model\Device;

class EventControllerTest extends TestCase
{
    public function testPostEmptyEvent()
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->never())
            ->method('partialUpdate');

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->never())
            ->method('writePoints');

        $request = new ServerRequest('POST', 'http://ther.mo', ['Content-Type' => 'application/json'], (string) json_encode([]));

        $controller = new EventController($dynamap, $influx, new NullLogger());
        $res = $controller->register($request, new Response(), ['mac' => '00:0A:95:9D:68:16']);

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertSame(['inserted' => 0], json_decode((string) $res->getBody(), true));
    }

    public function testPostBatteryEvent()
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('partialUpdate')
            ->with(Device::class, '00:0A:95:9D:68:16', ['last_battery' => 3000]);

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->never())
            ->method('writePoints');

        $events = [
            [
                'type' => 'battery',
                'data' => [
                    'levelMillivolt' => 3000,
                ],
            ],
        ];

        $request = new ServerRequest('POST', 'http://ther.mo', ['Content-Type' => 'application/json'], (string) json_encode($events));

        $controller = new EventController($dynamap, $influx, new NullLogger());
        $res = $controller->register($request, new Response(), ['mac' => '00:0A:95:9D:68:16']);

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertSame(['inserted' => 1], json_decode((string) $res->getBody(), true));
    }

    public function testPostTemperatureEvent()
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('partialUpdate')
            ->with(
                Device::class,
                '00:0A:95:9D:68:16',
                [
                    'last_update' => (new \DateTime('2018-06-03T21:38:05.111000+0200')),
                    'last_temperature' => 3000,
                ]
            );

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->once())
            ->method('writePoints');

        $events = [
            [
                'type' => 'temperature',
                'dateEvent' => '2018-06-03T19:38:05.111Z',
                'data' => [
                    'centidegreeCelsius' => 3000,
                ],
            ],
        ];

        $request = new ServerRequest('POST', 'http://ther.mo', ['Content-Type' => 'application/json'], (string) json_encode($events));

        $controller = new EventController($dynamap, $influx, new NullLogger());
        $res = $controller->register($request, new Response(), ['mac' => '00:0A:95:9D:68:16']);

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertSame(['inserted' => 1], json_decode((string) $res->getBody(), true));
    }
}
