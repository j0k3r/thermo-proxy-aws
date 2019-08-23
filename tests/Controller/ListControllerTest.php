<?php

namespace Test\Thermo\Controller;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Thermo\Controller\ListController;
use Thermo\Model\Device;

class ListControllerTest extends TestCase
{
    public function testListEmpty()
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('getAll')
            ->with(Device::class)
            ->willReturn([
            ]);

        $request = new ServerRequest('GET', 'http://ther.mo');

        $controller = new ListController($dynamap, new NullLogger());
        $res = $controller->list($request, new Response());

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertSame([], json_decode((string) $res->getBody(), true));
    }

    public function testList()
    {
        $date = (new \DateTimeImmutable())->setTimestamp(1566553890);

        $device1 = new Device('00:0A:95:9D:68:16');
        $device1->setLabel('Home');
        $device1->setColor('#FFFFFF');
        $device1->setSort(2);
        $device1->setLastUpdate($date);
        $device1->setLastTemperature(2890);
        $device1->setLastBattery(2890);

        $device2 = new Device('11:0A:95:9D:68:16');
        $device2->setLabel('Outside');
        $device2->setColor('#000000');
        $device2->setSort(1);
        $device2->setLastUpdate($date);
        $device2->setLastTemperature(120);
        $device2->setLastBattery(1202);

        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('getAll')
            ->with(Device::class)
            ->willReturn([
                $device1,
                $device2,
            ]);

        $request = new ServerRequest('GET', 'http://ther.mo');

        $controller = new ListController($dynamap, new NullLogger());
        $res = $controller->list($request, new Response());

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $body = json_decode((string) $res->getBody(), true);
        $this->assertCount(2, $body);

        $first = current($body);
        $this->assertSame('Outside', $first['label']);
        $this->assertSame(1.2, $first['last_temperature']);

        $second = next($body);
        $this->assertSame('Home', $second['label']);
        $this->assertSame(28.9, $second['last_temperature']);
    }
}
