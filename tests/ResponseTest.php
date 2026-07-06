<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\Response\RatesResponse;
use Booni3\DhlExpressRest\Response\ShipmentResponse;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /** @test */
    public function shipment_response_tolerates_missing_optional_fields()
    {
        $response = ShipmentResponse::fromArray([]);

        $this->assertSame('', $response->trackingNumber);
        $this->assertSame('', $response->trackingUrl);
        $this->assertNull($response->labelFormat());
        $this->assertNull($response->labelData());
        $this->assertNull($response->invoiceFormat());
        $this->assertNull($response->invoiceData());
    }

    /** @test */
    public function rates_response_sorts_missing_prices_and_delivery_dates_last()
    {
        $response = RatesResponse::fromArray([
            'products' => [
                $this->rateProduct('NO_PRICE', null, null, null),
                $this->rateProduct('SLOW', 20.0, '2026-07-08T12:00:00', 2),
                $this->rateProduct('FAST', 30.0, '2026-07-07T12:00:00', 1),
            ],
        ]);

        $this->assertSame(['SLOW', 'FAST', 'NO_PRICE'], array_column($response->productsSortedByCheapest(), 'productCode'));
        $this->assertSame(['FAST', 'SLOW', 'NO_PRICE'], array_column($response->productsSortedByFastest(), 'productCode'));
        $this->assertSame('FAST', $response->shortestTransitDaysAndCheapest()['productCode']);
    }

    private function rateProduct(string $code, ?float $price, ?string $estimatedDelivery, ?int $totalTransitDays): array
    {
        $product = [
            'productName' => $code,
            'productCode' => $code,
            'networkTypeCode' => 'TD',
            'deliveryCapabilities' => [
                'totalTransitDays' => $totalTransitDays,
            ],
        ];

        if ($price !== null) {
            $product['totalPrice'] = [
                [
                    'currencyType' => 'BILLC',
                    'price' => $price,
                    'priceCurrency' => 'GBP',
                ],
            ];
        }

        if ($estimatedDelivery !== null) {
            $product['deliveryCapabilities']['estimatedDeliveryDateAndTime'] = $estimatedDelivery;
        }

        return $product;
    }
}
