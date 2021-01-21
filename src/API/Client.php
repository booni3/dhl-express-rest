<?php


namespace Booni3\DhlExpressRest\API;

use Booni3\DhlExpressRest\Exceptions\ConfigException;
use Booni3\DhlExpressRest\Exceptions\ResponseException;
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
            $success = json_decode((string) $response->getBody(), true);
        } catch (ClientException $e) {
            $clientException = json_decode((string)$e->getResponse()->getBody(), true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ResponseException::parseError($response->getBody());
        }

        if($clientException ?? null){
            throw ResponseException::clientException($clientException);
        }

        return $success;
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
