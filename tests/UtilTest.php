<?php

namespace Tests\Thermo;

use PHPUnit\Framework\TestCase;
use Thermo\Util;

class UtilTest extends TestCase
{
    public function testConvertTemperature(): void
    {
        $this->assertSame(1.0, Util::convertTemperature(100));
        $this->assertSame(24.18, Util::convertTemperature(2418));
        $this->assertSame(0.0, Util::convertTemperature(null));
    }

    public function testConvertBattery(): void
    {
        $this->assertSame('100 mV (Low)', Util::convertBattery(100));
        $this->assertSame('2618 mV (Good)', Util::convertBattery(2618));
    }

    public function testFormatValueForGraph(): void
    {
        $data = [
            [
                'time' => '2019-07-24T00:00:00Z',
                'mean' => '33.795357142857',
            ],
        ];
        $expected = [
            [
                'time' => '24',
                'value' => 33.8,
            ],
        ];

        $this->assertSame($expected, Util::formatValueForGraph($data, 'd'));
    }
}
