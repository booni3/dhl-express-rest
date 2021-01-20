<?php


namespace Booni3\DhlExpressRest\Api;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    /** @var GuzzleClient */
    private $client;

    /** @var string */
    private $user;

    /** @var string */
    private $pass;

    public function __construct(GuzzleClient $client, string $user, string $pass)
    {
        $this->client = $client;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function get($endpoint = null, array $body = []): array
    {
        return $this->parse(function () use ($endpoint, $body) {
            return $this->client->request('GET', $endpoint, [
                'json' => $body,
                'auth' => [$this->user, $this->pass],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);
        });
    }

    public function post($endpoint = null, array $body = []): array
    {
        return $this->parse(function () use ($endpoint, $body) {
            return $this->client->request('POST', $endpoint, [
                'json' => $body,
                'auth' => [$this->user, $this->pass],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);
        });
    }


    private function parse(callable $callback)
    {
        $response = call_user_func($callback);

        $json = json_decode((string)$response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AmazonShippingResponseCouldNotBeParsed((string)$response->getBody());
        }

        return $json;
    }
}
