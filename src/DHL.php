<?php

namespace Booni3\DhlExpressRest;

use GuzzleHttp\Client as GuzzleClient;
use Booni3\DhlExpress\Api\Shipments;

class DHL
{
    const URI_SANDBOX = 'https://express.api.dhl.com/mydhlapi/test/';
    const URI_PRODUCTION = 'https://express.api.dhl.com/mydhlapi/???/';

    /** @var GuzzleClient */
    protected $client;

    /** @var array */
    protected $config;

    /** @var string */
    protected $user;

    /** @var string */
    protected $pass;

    public function __construct(array $config, GuzzleClient $client = null)
    {
        $this->config = $config;
        $this->user = $this->config['user'];
        $this->pass = $this->config['pass'];
        $this->client = $client;
    }

    public static function make(array $config, GuzzleClient $client = null): self
    {
        return new static($config, $client);
    }

    public function shipments()
    {
        return new Shipments($this->client(), $this->user, $this->pass);
    }

    protected function client(): GuzzleClient
    {
        if($this->client){
            return $this->client;
        }

        return new GuzzleClient([
            'base_uri' => $this->baseUri(),
            'timeout' => $this->config['timeout'] ?? 15
        ]);
    }

    private function baseUri()
    {
        if($this->config['sandbox'] ?? false){
            return self::URI_SANDBOX;
        }

        return self::URI_PRODUCTION;
    }
}
