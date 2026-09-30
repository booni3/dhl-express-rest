<?php


namespace Booni3\DhlExpressRest\API;


use Booni3\DhlExpressRest\Response\ShipmentResponse;
use Booni3\DhlExpressRest\DTO\ShipmentCreator;

class Shipments extends Client
{
    public function create(ShipmentCreator $creator): ShipmentResponse
    {
        return ShipmentResponse::fromArray(
            $this->post('shipments', $creator->toShipmentRequestArray())
        );
    }
}
