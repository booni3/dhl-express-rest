<?php


namespace Booni3\DhlExpressRest\Api;


use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Shipment;
use Booni3\DhlExpressRest\ShipmentCreator;

class Shipments extends Client
{
    public function create(ShipmentCreator $creator): Shipment
    {
        return Shipment::fromResponse($this->post('shipments', [
                "plannedShippingDateAndTime" => $creator->readyAt->format(DHL::TIME_FORMAT),
                "pickup" => [
                    "isRequested" => $creator->pickupRequested
                ],
                "productCode" => $creator->productCode,
                "accounts" => [
                    [
                        "number" => $creator->billingAccountNumber,
                        "typeCode" => $creator->billingType
                    ]
                ],
                "valueAddedServices" => $creator->valueAddedServices(),
                "customerDetails" => [
                    "shipperDetails" => $creator->shipper->toArray(),
                    "receiverDetails" => $creator->receiver->toArray()
                ],
                "customerReferences" => $creator->references(),
                "content" => $creator->content()
            ] + $creator->outputimage()
        ));
    }
}
