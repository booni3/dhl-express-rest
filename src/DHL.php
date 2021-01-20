<?php

namespace Booni3\DhlExpressRest;

use Booni3\DhlExpressRest\Api\Shipments;
use GuzzleHttp\Client as GuzzleClient;

class DHL
{
    const URI_SANDBOX = 'https://express.api.dhl.com/mydhlapi/test/';
    const URI_PRODUCTION = 'https://express.api.dhl.com/mydhlapi/???/';
    const TIME_FORMAT = 'Y-m-d\TH:i:s \G\M\T\+\0\1\:\0\0';

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
        $this->client = $client;
    }

    public static function make(array $config, GuzzleClient $client = null): self
    {
        return new static($config, $client);
    }

    public function shipments()
    {
        return new Shipments($this->client(), $this->config);
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
