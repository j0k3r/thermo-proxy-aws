<?php

namespace Tests\Thermo\Controller;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Thermo\Controller\DetailController;
use Thermo\Model\Device;

class DetailControllerTest extends TestCase
{
    public function testDetailBadMac(): void
    {
        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->never())
            ->method('get');

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->never())
            ->method('query');

        $request = new ServerRequest('GET', 'http://ther.mo');

        $controller = new DetailController($dynamap, $influx, new NullLogger());
        $res = $controller->detail($request, new Response(), ['mac' => ':16']);

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $this->assertArrayHasKey('error', json_decode((string) $res->getBody(), true));
    }

    public function testDetail(): void
    {
        $date = (new \DateTimeImmutable())->setTimestamp(1566553890);

        $device = new Device('00:0A:95:9D:68:16');
        $device->setLabel('Home');
        $device->setColor('#FFFFFF');
        $device->setSort(2);
        $device->setLastUpdate($date);
        $device->setLastTemperature(2890);
        $device->setLastBattery(2890);

        $dynamap = $this->getMockBuilder('Dynamap\Dynamap')
            ->disableOriginalConstructor()
            ->getMock();
        $dynamap->expects($this->once())
            ->method('get')
            ->with(Device::class, '00:0A:95:9D:68:16')
            ->willReturn($device);

        $influx = $this->getMockBuilder('InfluxDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $influx->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($argument) {
                $data = [[
                    'mean' => 23.21,
                    'max' => 43.21,
                    'min' => 3.21,
                ]];

                if (false !== strpos($argument, 'SELECT MAX("value")/100 FROM')) {
                    $data = [
                        [
                            'max' => 30,
                            'time' => '2018-06-03T21:41:30',
                        ],
                    ];
                }

                if (false !== strpos($argument, 'SELECT MIN("value")/100 FROM')) {
                    $data = [
                        [
                            'min' => 3,
                            'time' => '2018-06-03T21:41:30',
                        ],
                    ];
                }

                if (false !== strpos($argument, 'GROUP BY')) {
                    $data = [
                        [
                            'mean' => 43.21,
                            'time' => '2018-06-03T09:00:00',
                        ], [
                            'mean' => 42.21,
                            'time' => '2018-06-03T09:30:00',
                        ], [
                            'mean' => 41.21,
                            'time' => '2018-06-03T10:00:00',
                        ], [
                            'mean' => 38.21,
                            'time' => '2018-06-03T10:30:00',
                        ], [
                            'mean' => 35.21,
                            'time' => '2018-06-03T11:00:00',
                        ], [
                            'mean' => 33.21,
                            'time' => '2018-06-03T11:30:00',
                        ], [
                            'mean' => 23.21,
                            'time' => '2018-06-03T12:00:00',
                        ],
                    ];
                }

                $resultSet = $this->getMockBuilder('InfluxDB\ResultSet')
                    ->disableOriginalConstructor()
                    ->getMock();
                $resultSet->expects($this->once())
                    ->method('getPoints')
                    ->willReturn($data);

                return $resultSet;
            });

        $request = new ServerRequest('GET', 'http://ther.mo');

        $controller = new DetailController($dynamap, $influx, new NullLogger());
        $res = $controller->detail($request, new Response(), ['mac' => '00:0A:95:9D:68:16']);

        $this->assertSame('application/json', $res->getHeader('Content-Type')[0]);
        $body = json_decode((string) $res->getBody(), true);
        $this->assertArrayHasKey('max', $body);
        $this->assertArrayHasKey('max_date', $body);
        $this->assertArrayHasKey('min', $body);
        $this->assertArrayHasKey('min_date', $body);
        $this->assertArrayHasKey('last_24h', $body);
        $this->assertArrayHasKey('mean_24h', $body);
        $this->assertArrayHasKey('max_24h', $body);
        $this->assertArrayHasKey('min_24h', $body);
        $this->assertArrayHasKey('last_30d', $body);
        $this->assertArrayHasKey('mean_30d', $body);
        $this->assertArrayHasKey('max_30d', $body);
        $this->assertArrayHasKey('min_30d', $body);
        $this->assertArrayHasKey('last_52w', $body);
        $this->assertArrayHasKey('mean_52w', $body);
        $this->assertArrayHasKey('max_52w', $body);
        $this->assertArrayHasKey('min_52w', $body);
    }
}
