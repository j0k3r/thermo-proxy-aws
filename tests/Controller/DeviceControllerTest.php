<?php

namespace Tests\Thermo\Controller;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Thermo\Controller\DeviceController;
use Thermo\Model\Device;

class DeviceControllerTest extends TestCase
{
    public function testInitFailBecauseDataExists(): void
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('getAll')
            ->with(Device::class)
            ->willReturn([
                [
                    'mac' => '00:0A:95:9D:68:16',
                ],
            ]);

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $request = new ServerRequest('GET', 'http://ther.mo');

        $controller = new DeviceController($dynamap, null, $influx, new NullLogger());
        $res = $controller->init($request, new Response());

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertArrayHasKey('error', json_decode((string) $res->getBody(), true));
    }

    public function testInit(): void
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('getAll')
            ->with(Device::class)
            ->willReturn([]);
        $dynamap->expects($this->any())
            ->method('save');

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $influx->expects($this->once())
            ->method('create');

        $request = new ServerRequest('GET', 'http://ther.mo');

        $controller = new DeviceController($dynamap, null, $influx, new NullLogger());
        $res = $controller->init($request, new Response());

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertArrayHasKey('created', json_decode((string) $res->getBody(), true));
    }
}
