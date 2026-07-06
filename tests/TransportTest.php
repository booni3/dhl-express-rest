<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Exceptions\ResponseException;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TransportTest extends TestCase
{
    /** @test */
    public function production_clients_use_the_real_mydhl_base_uri()
    {
        $dhl = DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => false]);
        $clientMethod = new ReflectionMethod(DHL::class, 'client');

        if (PHP_VERSION_ID < 80100) {
            $clientMethod->setAccessible(true);
        }

        $client = $clientMethod->invoke($dhl);

        $this->assertSame('https://express.api.dhl.com/mydhlapi/', (string) $client->getConfig('base_uri'));
    }

    /** @test */
    public function requests_include_required_headers_and_basic_auth()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->single('1234567890');

        $request = $history[0]['request'];

        $this->assertSame(DHL::API_VERSION, $request->getHeaderLine('x-version'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('Basic '.base64_encode('u:p'), $request->getHeaderLine('Authorization'));
    }

    /** @test */
    public function json_400_responses_throw_response_exception_with_payload_status()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(400, ['Content-Type' => 'application/json'], '{"status":422,"detail":"Invalid shipment"}'),
        ], $history);

        try {
            DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
                ->tracking()
                ->single('bad');

            $this->fail('Expected a response exception.');
        } catch (ResponseException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertStringContainsString('Invalid shipment', $e->getMessage());
        }
    }

    /** @test */
    public function json_error_responses_without_status_use_the_http_status_code()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(400, ['Content-Type' => 'application/json'], '{"detail":"Missing status"}'),
        ], $history);

        try {
            DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
                ->tracking()
                ->single('bad');

            $this->fail('Expected a response exception.');
        } catch (ResponseException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertStringContainsString('Missing status', $e->getMessage());
        }
    }

    /** @test */
    public function non_json_500_responses_throw_parse_response_exception()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(500, ['Content-Type' => 'text/html'], '<html>Server error</html>'),
        ], $history);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Could not parse response: <html>Server error</html>');

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->single('bad');
    }

    /** @test */
    public function non_object_json_error_responses_throw_parse_response_exception()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(500, ['Content-Type' => 'application/json'], '"Server error"'),
        ], $history);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Could not parse response: "Server error"');

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->single('bad');
    }

    /** @test */
    public function invalid_json_success_responses_throw_parse_response_exception()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], 'not json'),
        ], $history);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Could not parse response: not json');

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->single('bad');
    }
}
