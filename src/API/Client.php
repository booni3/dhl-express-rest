<?php


namespace Booni3\DhlExpressRest\API;

use Booni3\DhlExpressRest\ConfigException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /** @var GuzzleClient */
    private $client;

    public function __construct(GuzzleClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function get($endpoint = null, array $body = []): array
    {
        return $this->parse(function () use ($endpoint, $body) {
            return $this->client->request('GET', $endpoint, [
                'json' => $body,
                'auth' => $this->auth(),
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
                'auth' => $this->auth(),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);
        });
    }


    private function parse(callable $callback)
    {
        try {
            $response = call_user_func($callback);
        } catch (ClientException $e) {
            dd(json_decode($e->getResponse()->getBody(), true));
        }

        $json = json_decode((string) $response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AmazonShippingResponseCouldNotBeParsed((string)$response->getBody());
        }

        return $json;
    }

    protected function auth(): array
    {
        if(! $user = $this->config['user'] ?? false){
            throw ConfigException::missingArgument('user');
        }

        if(! $pass = $this->config['pass'] ?? false){
            throw ConfigException::missingArgument('pass');
        }

        return [$user, $pass];
    }

}
