<?php


namespace Booni3\DhlExpressRest\API;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Exceptions\ConfigException;
use Booni3\DhlExpressRest\Exceptions\ResponseException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Query;

class Client
{
    /** @var GuzzleClient */
    private $client;

    /** @var array */
    private $config;

    public function __construct(GuzzleClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function get($endpoint = null, array $body = []): array
    {
        return $this->parse(function () use ($endpoint, $body) {
            return $this->client->request('GET', $endpoint, [
                'query' => Query::build($body),
                'auth' => $this->auth(),
                'headers' => $this->headers()
            ]);
        });
    }

    public function post($endpoint = null, array $body = []): array
    {
        return $this->parse(function () use ($endpoint, $body) {
            return $this->client->request('POST', $endpoint, [
                'json' => $body,
                'auth' => $this->auth(),
                'headers' => $this->headers()
            ]);
        });
    }

    private function parse(callable $callback)
    {
        try {
            $response = call_user_func($callback);
        } catch (BadResponseException $e) {
            $body = (string) $e->getResponse()->getBody();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ResponseException::parseError($body);
            }

            if (! is_array($decoded)) {
                throw ResponseException::parseError($body);
            }

            throw ResponseException::clientException($decoded, $e->getResponse()->getStatusCode());
        }

        $body = (string) $response->getBody();
        $success = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ResponseException::parseError($body);
        }

        return $success;
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-version' => DHL::API_VERSION,
        ];
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
