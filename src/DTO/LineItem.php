<?php


namespace Booni3\DhlExpressRest\DTO;


class LineItem
{
    public $item = [];

    public function __construct(
        string $description,
        float $price,
        int $qty,
        int $hsCode,
        string $countryOfManufacture,
        float $weightKgGross,
        ?float $weightKgNet = null,
        string $qtyUnitOfMeasure = 'BOX',
        string $priceCurrency = 'GBP',
        string $exportReason = 'permanent'
    ) {
        $this->item = [
            "description" => $description,
            "price" => $price,
            "quantity" => [
                "value" => $qty,
                "unitOfMeasurement" => $qtyUnitOfMeasure
            ],
            "commodityCodes" => [
                [
                    "typeCode" => "outbound",
                    "value" => (string) $hsCode
                ]
            ],
            "exportReasonType" => $exportReason,
            "manufacturerCountry" => $countryOfManufacture,
            "weight" => [
                "netValue" => $weightKgNet ?? $weightKgGross,
                "grossValue" => $weightKgGross
            ]
        ];
    }

    /**
     * @param CommodityCode[] $commodityCodes
     */
    public static function forCustomsInvoice(
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
    ): self {
        return new CommercialInvoiceLineItem(
            $number,
            $description,
            $unitPrice,
            $quantity,
            $commodityCodes,
            $countryOfManufacture,
            $totalNetWeightKg,
            $totalGrossWeightKg,
            $preCalculatedTotalValue,
            $isTaxesPaid,
            $exportReason
        );
    }

    public function toArray(): array
    {
        return $this->item;
    }
}
