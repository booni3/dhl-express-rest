<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Response\LandedCostResponse;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class LandedCostPayloadTest extends TestCase
{
    /** @test */
    public function it_posts_a_landed_cost_payload()
    {
        $history = [];
        $payload = [
            'customerDetails' => [
                'shipperDetails' => [
                    'postalCode' => 'B24 8DW',
                    'cityName' => 'Birmingham',
                    'countryCode' => 'GB',
                ],
                'receiverDetails' => [
                    'postalCode' => '94114',
                    'cityName' => 'San Francisco',
                    'countryCode' => 'US',
                    'provinceCode' => 'CA',
                ],
            ],
            'accounts' => [
                [
                    'typeCode' => 'shipper',
                    'number' => '123456789',
                ],
            ],
            'unitOfMeasurement' => 'metric',
            'currencyCode' => 'GBP',
            'isCustomsDeclarable' => true,
            'getCostBreakdown' => true,
            'packages' => [
                [
                    'weight' => 1.2,
                    'dimensions' => [
                        'length' => 10,
                        'width' => 20,
                        'height' => 30,
                    ],
                ],
            ],
            'items' => [
                [
                    'number' => 1,
                    'quantity' => 1,
                    'unitPrice' => 84.95,
                    'unitPriceCurrencyCode' => 'GBP',
                    'manufacturerCountry' => 'GB',
                ],
            ],
        ];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[],"warnings":["w"]}'),
        ], $history);

        $response = DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->landedCost()
            ->retrieve($payload);

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertInstanceOf(LandedCostResponse::class, $response);
        $this->assertSame('/landed-cost', $history[0]['request']->getUri()->getPath());
        $this->assertSame($payload, $body);
        $this->assertSame(['w'], $response->warnings);
    }
}
