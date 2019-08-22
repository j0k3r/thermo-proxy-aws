<?php

declare(strict_types=1);

namespace Thermo;

class Util
{
    public static function convertTemperature(int $temperature): float
    {
        return $temperature / 100;
    }

    public static function convertBattery(int $battery): int
    {
        return (int) ($battery / 3000 * 100);
    }

    public static function formatValueForGraph($data, $dateFormat): array
    {
        $newData = [];
        foreach ($data as $item) {
            $newData[] = [
                // todo format time zone
                // formatToTimeZone(item.time, dateFormat, { timeZone: 'Europe/Paris' }),
                'time' => '',
                'value' => $item['mean'] ? round($item['mean'], 2) : null,
            ];
        }

        return $newData;
    }
}
