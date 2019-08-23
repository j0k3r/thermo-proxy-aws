<?php

declare(strict_types=1);

namespace Thermo\Model;

use Thermo\Util;

class Device
{
    /** @var string */
    private $mac;

    /** @var string */
    private $label;

    /** @var string */
    private $color;

    /** @var int */
    private $sort;

    /** @var \DateTimeImmutable|null */
    private $last_update;

    /** @var int|null */
    private $last_temperature;

    /** @var int|null */
    private $last_battery;

    public function __construct(string $mac)
    {
        $this->mac = $mac;
    }

    public function getMac(): string
    {
        return $this->mac;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setLastUpdate(\DateTimeImmutable $last_update): void
    {
        $this->last_update = $last_update;
    }

    public function getLastUpdate(): ?\DateTimeImmutable
    {
        return $this->last_update;
    }

    public function setLastTemperature(int $last_temperature): void
    {
        $this->last_temperature = $last_temperature;
    }

    public function getLastTemperature(): ?int
    {
        return $this->last_temperature;
    }

    public function setLastBattery(int $last_battery): void
    {
        $this->last_battery = $last_battery;
    }

    public function getLastBattery(): ?int
    {
        return $this->last_battery;
    }

    public function toArray(): array
    {
        return [
            'mac' => $this->mac,
            'label' => $this->label,
            'color' => $this->color,
            'sort' => $this->sort,
            'last_update' => $this->last_update ? $this->last_update->format('Y-m-d H:i:s') : null,
            'last_temperature' => Util::convertTemperature($this->last_temperature),
            'last_battery' => Util::convertBattery($this->last_battery),
        ];
    }
}
