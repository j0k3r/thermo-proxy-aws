<?php

namespace Tests\Thermo\Model;

use PHPUnit\Framework\TestCase;
use Thermo\Model\Device;

class DeviceTest extends TestCase
{
    public function testToArray(): void
    {
        $date = (new \DateTimeImmutable())->setTimestamp(1566553890);

        $device = new Device('00:0A:95:9D:68:16');
        $device->setLabel('Home');
        $device->setColor('#FFFFFF');
        $device->setSort(1);
        $device->setLastUpdate($date);
        $device->setLastTemperature(2890);
        $device->setLastBattery(2890);

        $expected = [
            'mac' => '00:0A:95:9D:68:16',
            'label' => 'Home',
            'color' => '#FFFFFF',
            'sort' => 1,
            'last_update' => '2019-08-23 11:51:30',
            'last_temperature' => 28.9,
            'last_battery' => '2890 mV (Good)',
        ];

        $this->assertSame($expected, $device->toArray());
    }
}
