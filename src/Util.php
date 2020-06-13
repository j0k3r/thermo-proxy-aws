<?php

declare(strict_types=1);

namespace Thermo;

class Util
{
    public static function convertTemperature(?int $temperature): float
    {
        return $temperature / 100;
    }

    public static function convertBattery(?int $battery): int
    {
        return (int) ($battery / 3000 * 100);
    }

    public static function formatValueForGraph(array $data, string $dateFormat): array
    {
        $newData = [];
        foreach ($data as $item) {
            $date = (new \DateTime())
                ->setTimestamp(strtotime($item['time']))
                ->setTimezone(new \DateTimeZone('Europe/Paris'))
                ->format($dateFormat);

            $newData[] = [
                'time' => $date,
                'value' => $item['mean'] ? round($item['mean'], 1) : null,
            ];
        }

        return $newData;
    }
}
