<?php

namespace Booni3\DhlExpressRest\DTO;

use Booni3\DhlExpressRest\ShipmentException;

class CommodityCode
{
    private const DIRECTIONS = ['inbound', 'outbound'];

    private string $direction;
    private string $value;

    public function __construct(string $direction, string $value)
    {
        $direction = strtolower($direction);

        if (! in_array($direction, self::DIRECTIONS, true)) {
            throw ShipmentException::invalidCommodityCodeDirection();
        }

        if (strlen($value) < 2 || strlen($value) > 18) {
            throw ShipmentException::invalidCommodityCodeValue();
        }

        $this->direction = $direction;
        $this->value = $value;
    }

    public static function inbound(string $value): self
    {
        return new self('inbound', $value);
    }

    public static function outbound(string $value): self
    {
        return new self('outbound', $value);
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function toArray(): array
    {
        return [
            'typeCode' => $this->direction,
            'value' => $this->value,
        ];
    }
}
