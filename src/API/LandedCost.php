<?php


namespace Booni3\DhlExpressRest\API;


use Booni3\DhlExpressRest\Response\LandedCostResponse;

class LandedCost extends Client
{
    public function retrieve(array $payload): LandedCostResponse
    {
        return LandedCostResponse::fromArray($this->post('landed-cost', $payload));
    }
}
