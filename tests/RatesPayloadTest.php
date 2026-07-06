<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use Booni3\DhlExpressRest\Tests\Support\ShipmentCreatorFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RatesPayloadTest extends TestCase
{
    /** @test */
    public function it_keeps_the_default_declared_value_payload()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->rates()
            ->retrieve(ShipmentCreatorFactory::ratesCreator());

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame([
            [
                'typeCode' => 'declaredValue',
                'value' => 100,
                'currency' => 'GBP',
            ],
        ], $body['monetaryAmount']);
    }

    /** @test */
    public function it_uses_explicit_declared_value_when_available()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ], $history);
        $creator = ShipmentCreatorFactory::ratesCreator();
        $creator->setExportDeclaration('sale', 'permanent', 'EUR', 250.0);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->rates()
            ->retrieve($creator);

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame([
            [
                'typeCode' => 'declaredValue',
                'value' => 250,
                'currency' => 'EUR',
            ],
        ], $body['monetaryAmount']);
    }

    /** @test */
    public function it_keeps_declared_value_fallback_currency_atomic()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ], $history);
        $creator = ShipmentCreatorFactory::ratesCreator();
        $creator->setExportDeclaration('sale', 'permanent', 'EUR', null);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->rates()
            ->retrieve($creator);

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame([
            [
                'typeCode' => 'declaredValue',
                'value' => 100,
                'currency' => 'GBP',
            ],
        ], $body['monetaryAmount']);
    }
}
