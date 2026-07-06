<?php


namespace Booni3\DhlExpressRest\Response;


class LandedCostResponse
{
    public $products = [];
    public $warnings = [];

    public static function fromArray(array $data)
    {
        $static = new static();
        $static->products = $data['products'] ?? [];
        $static->warnings = $data['warnings'] ?? [];

        return $static;
    }

    public function landedCostProducts(): array
    {
        return array_map(function (array $product) {
            $billingPrice = $this->billingPrice($product);

            return [
                'productName' => $product['productName'] ?? null,
                'productCode' => $product['productCode'] ?? null,
                'localProductCode' => $product['localProductCode'] ?? null,
                'totalPrice' => $billingPrice['price'] ?? null,
                'priceCurrency' => $billingPrice['priceCurrency'] ?? null,
                'duty' => $this->chargeTotal($product, 'DUTY'),
                'tax' => $this->chargeTotal($product, 'TAX'),
                'fee' => $this->chargeTotal($product, 'FEE'),
                'dutyTaxPaidService' => $this->firstDetailedService($product, 'DD'),
                'items' => $product['items'] ?? [],
            ];
        }, $this->products);
    }

    public function firstLandedCostProduct(): ?array
    {
        return $this->landedCostProducts()[0] ?? null;
    }

    private function billingPrice(array $product): array
    {
        $prices = $product['totalPrice'] ?? [];
        $billingPrices = array_values(
            array_filter($prices, function ($price) {
                return ($price['currencyType'] ?? null) === 'BILLC';
            })
        );

        return $billingPrices[0] ?? array_values($prices)[0] ?? [];
    }

    private function sumItemBreakdown(array $product, string $typeCode): ?float
    {
        $matches = $this->itemBreakdownsByType($product, $typeCode);

        if (! $matches) {
            return null;
        }

        return round(array_reduce($matches, function (float $carry, array $row): float {
            return $carry + (float) ($row['price'] ?? 0);
        }, 0.0), 2);
    }

    private function itemBreakdownsByType(array $product, string $typeCode): array
    {
        $matches = [];

        foreach ($product['items'] ?? [] as $item) {
            foreach ($item['breakdown'] ?? [] as $breakdown) {
                if (($breakdown['typeCode'] ?? null) === $typeCode) {
                    $matches[] = $breakdown;
                }
            }
        }

        return $matches;
    }

    private function chargeTotal(array $product, string $typeCode): ?float
    {
        $detailedTotal = $this->detailedChargeTotal($product, $typeCode);

        if ($detailedTotal !== null) {
            return $detailedTotal;
        }

        return $this->sumItemBreakdown($product, $typeCode);
    }

    private function detailedChargeTotal(array $product, string $typeCode): ?float
    {
        $matches = [];

        foreach ($product['detailedPriceBreakdown'] ?? [] as $priceBreakdown) {
            foreach ($priceBreakdown['breakdown'] ?? [] as $breakdown) {
                if (($breakdown['typeCode'] ?? null) !== $typeCode) {
                    continue;
                }

                if (strpos(strtoupper($breakdown['name'] ?? ''), 'TOTAL') === 0) {
                    $matches[] = $breakdown;
                }
            }
        }

        if (! $matches) {
            return null;
        }

        return round(array_reduce($matches, function (float $carry, array $row): float {
            return $carry + (float) ($row['price'] ?? 0);
        }, 0.0), 2);
    }

    private function firstDetailedService(array $product, string $serviceCode): ?array
    {
        foreach ($product['detailedPriceBreakdown'] ?? [] as $priceBreakdown) {
            foreach ($priceBreakdown['breakdown'] ?? [] as $breakdown) {
                if (($breakdown['serviceCode'] ?? null) === $serviceCode) {
                    return $breakdown;
                }
            }
        }

        return null;
    }
}
