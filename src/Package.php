<?php


namespace Booni3\DhlExpressRest;


class Package
{
    public $package = [];

    public function __construct(float $weightKg, int $lengthCm, int $widthCm, int $heightCm, string $description, string $reference)
    {
        $this->package = [
            "customerReferences" => [
                [
                    "value" => $reference,
                    "typeCode" => "CU"
                ]
            ],
            "weight" => $weightKg,
            "description" => $description,
            "dimensions" => [
                "length" => $lengthCm,
                "width" => $widthCm,
                "height" => $heightCm
            ]
        ];
    }
}
