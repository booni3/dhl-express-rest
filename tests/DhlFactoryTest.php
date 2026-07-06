<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\AddressException;
use Booni3\DhlExpressRest\API\Rates;
use Booni3\DhlExpressRest\API\Shipments;
use Booni3\DhlExpressRest\API\Tracking;
use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Exceptions\ConfigException;
use Booni3\DhlExpressRest\Exceptions\ResponseException;
use Booni3\DhlExpressRest\ShipmentException;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class DhlFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_api_clients_when_constructed_with_a_mock_client()
    {
        $history = [];
        $client = MockClientFactory::create([], $history);
        $dhl = DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client);

        $this->assertInstanceOf(Shipments::class, $dhl->shipments());
        $this->assertInstanceOf(Rates::class, $dhl->rates());
        $this->assertInstanceOf(Tracking::class, $dhl->tracking());
    }

    /** @test */
    public function it_can_send_a_request_through_the_mock_client()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ], $history);

        $response = DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->single('1234567890');

        $this->assertSame(['ok' => true], $response);
        $this->assertCount(1, $history);
        $this->assertSame('GET', $history[0]['request']->getMethod());
        $this->assertSame('/shipments/1234567890/tracking', $history[0]['request']->getUri()->getPath());
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        $this->assertSame('all-checkpoints', $query['trackingView'] ?? null);
        $this->assertSame('all', $query['levelOfDetail'] ?? null);
    }

    /** @test */
    public function it_autoloads_all_public_exceptions_under_strict_psr4()
    {
        $this->assertInstanceOf(AddressException::class, new AddressException());
        $this->assertInstanceOf(ShipmentException::class, new ShipmentException());
        $this->assertInstanceOf(ConfigException::class, new ConfigException());
        $this->assertInstanceOf(ResponseException::class, new ResponseException());
    }

}
