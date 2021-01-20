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
        $exportReason = 'permanent'
    ) {
        $this->item = [
            "description" => $description,
            "price" => $price,
            "priceCurrency" => $priceCurrency,
            "quantity" => [
                "value" => $qty,
                "unitOfMeasurement" => $qtyUnitOfMeasure
            ],
            "commodityCodes" => [
                [
                    "typeCode" => "outbound",
                    "value" => "HS".$hsCode
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

    public function toArray(): array
    {
        return $this->item;
    }
}
