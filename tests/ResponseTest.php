<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\Response\LandedCostResponse;
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

    /** @test */
    public function landed_cost_response_summarises_duty_tax_fee_and_dtp_service()
    {
        $response = LandedCostResponse::fromArray([
            'warnings' => ['Check import commodity code'],
            'products' => [
                [
                    'productName' => 'EXPRESS WORLDWIDE',
                    'productCode' => 'P',
                    'localProductCode' => 'P',
                    'totalPrice' => [
                        [
                            'priceCurrency' => 'GBP',
                            'price' => 169.87,
                        ],
                    ],
                    'detailedPriceBreakdown' => [
                        [
                            'currencyType' => 'BILLC',
                            'priceCurrency' => 'GBP',
                            'breakdown' => [
                                [
                                    'name' => 'DUTY TAX PAID',
                                    'serviceCode' => 'DD',
                                    'typeCode' => 'STSCH',
                                    'price' => 17.0,
                                    'priceCurrency' => 'USD',
                                ],
                                [
                                    'name' => 'TOTAL DUTIES',
                                    'typeCode' => 'DUTY',
                                    'price' => 8.5,
                                    'priceCurrency' => 'GBP',
                                ],
                                [
                                    'name' => 'TOTAL TAXES',
                                    'typeCode' => 'TAX',
                                    'price' => 0.0,
                                    'priceCurrency' => 'GBP',
                                ],
                                [
                                    'name' => 'TOTAL FEES',
                                    'typeCode' => 'FEE',
                                    'price' => 1.0,
                                    'priceCurrency' => 'GBP',
                                ],
                                [
                                    'name' => 'MPF',
                                    'typeCode' => 'FEE',
                                    'price' => 1.0,
                                    'priceCurrency' => 'GBP',
                                ],
                            ],
                        ],
                    ],
                    'items' => [
                        [
                            'number' => 1,
                            'breakdown' => [
                                [
                                    'name' => 'DUTY',
                                    'typeCode' => 'DUTY',
                                    'price' => 8.5,
                                    'priceCurrency' => 'GBP',
                                ],
                                [
                                    'name' => 'TAX',
                                    'typeCode' => 'TAX',
                                    'price' => 0.0,
                                    'priceCurrency' => 'GBP',
                                ],
                                [
                                    'name' => 'MERCHANDISE PROCESSING FEE',
                                    'typeCode' => 'FEE',
                                    'price' => 1.0,
                                    'priceCurrency' => 'GBP',
                                ],
                            ],
                        ],
                        [
                            'number' => 2,
                            'breakdown' => [
                                [
                                    'name' => 'FEE',
                                    'typeCode' => 'FEE',
                                    'price' => 1.0,
                                    'priceCurrency' => 'GBP',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $summary = $response->firstLandedCostProduct();

        $this->assertSame(['Check import commodity code'], $response->warnings);
        $this->assertSame('EXPRESS WORLDWIDE', $summary['productName']);
        $this->assertSame(169.87, $summary['totalPrice']);
        $this->assertSame('GBP', $summary['priceCurrency']);
        $this->assertSame(8.5, $summary['duty']);
        $this->assertSame(0.0, $summary['tax']);
        $this->assertSame(1.0, $summary['fee']);
        $this->assertSame('DD', $summary['dutyTaxPaidService']['serviceCode']);
        $this->assertSame(17.0, $summary['dutyTaxPaidService']['price']);
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
