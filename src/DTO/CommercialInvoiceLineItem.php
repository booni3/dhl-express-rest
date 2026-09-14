<?php

namespace Booni3\DhlExpressRest\DTO;

use Booni3\DhlExpressRest\ShipmentException;

class CommercialInvoiceLineItem extends LineItem
{
    /**
     * @param CommodityCode[] $commodityCodes
     */
    public function __construct(
        int $number,
        string $description,
        float $unitPrice,
        int $quantity,
        array $commodityCodes,
        string $countryOfManufacture,
        float $totalNetWeightKg,
        ?float $totalGrossWeightKg,
        float $preCalculatedTotalValue,
        ?bool $isTaxesPaid = null,
        string $exportReason = 'permanent'
    ) {
        $this->validate(
            $number,
            $unitPrice,
            $quantity,
            $commodityCodes,
            $totalNetWeightKg,
            $totalGrossWeightKg,
            $preCalculatedTotalValue
        );

        $this->item = [
            'number' => $number,
            'description' => $description,
            'price' => $unitPrice,
            'quantity' => [
                'value' => $quantity,
                'unitOfMeasurement' => 'PCS',
            ],
            'commodityCodes' => array_map(static function (CommodityCode $code): array {
                return $code->toArray();
            }, $commodityCodes),
            'exportReasonType' => $exportReason,
            'manufacturerCountry' => $countryOfManufacture,
            'weight' => [
                'netValue' => $totalNetWeightKg,
            ],
            'preCalculatedLineItemTotalValue' => $preCalculatedTotalValue,
        ];

        if ($totalGrossWeightKg !== null) {
            $this->item['weight']['grossValue'] = $totalGrossWeightKg;
        }

        if ($isTaxesPaid !== null) {
            $this->item['isTaxesPaid'] = $isTaxesPaid;
        }
    }

    private function validate(
        int $number,
        float $unitPrice,
        int $quantity,
        array $commodityCodes,
        float $totalNetWeightKg,
        ?float $totalGrossWeightKg,
        float $preCalculatedTotalValue
    ): void {
        if ($number < 1 || $number > 999) {
            throw ShipmentException::invalidLineItemNumber();
        }

        if ($unitPrice <= 0) {
            throw ShipmentException::invalidLineItemPrice();
        }

        if ($quantity < 1) {
            throw ShipmentException::invalidLineItemQuantity();
        }

        if (count($commodityCodes) < 1 || count($commodityCodes) > 2) {
            throw ShipmentException::invalidCommodityCodeCount();
        }

        $directions = [];

        foreach ($commodityCodes as $commodityCode) {
            if (! $commodityCode instanceof CommodityCode) {
                throw ShipmentException::invalidCommodityCodeType();
            }

            if (in_array($commodityCode->direction(), $directions, true)) {
                throw ShipmentException::duplicateCommodityCodeDirection();
            }

            $directions[] = $commodityCode->direction();
        }

        if ($totalNetWeightKg <= 0 || ($totalGrossWeightKg !== null && $totalGrossWeightKg <= 0)) {
            throw ShipmentException::invalidLineItemWeight();
        }

        if ($preCalculatedTotalValue <= 0) {
            throw ShipmentException::invalidLineItemTotal();
        }
    }
}
