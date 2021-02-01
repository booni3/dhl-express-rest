<?php


namespace Booni3\DhlExpressRest\API;


use Booni3\DhlExpressRest\Response\ShipmentResponse;
use Booni3\DhlExpressRest\DTO\ShipmentCreator;

class Shipments extends Client
{
    public function create(ShipmentCreator $creator): ShipmentResponse
    {
        return ShipmentResponse::fromArray(
            $this->post('shipments', [
                    "plannedShippingDateAndTime" => $creator->plannedShippingDateAndTime(),
                    "pickup" => [
                        "isRequested" => $creator->pickupRequested
                    ],
                    "productCode" => $creator->productCode,
                    "accounts" => $creator->accounts(),
                    "valueAddedServices" => $creator->valueAddedServices(),
                    "customerDetails" => [
                        "shipperDetails" => $creator->shipper->toArray(),
                        "receiverDetails" => $creator->receiver->toArray()
                    ],
                    "customerReferences" => $creator->references(),
                    "content" => $creator->content()
                ] + $creator->outputimage()
            )
        );
    }
}
