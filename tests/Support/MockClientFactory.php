<?php

namespace Booni3\DhlExpressRest\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class MockClientFactory
{
    public static function create(array $responses, array &$history): Client
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        return new Client([
            'base_uri' => 'https://example.test/',
            'handler' => $stack,
        ]);
    }
}
