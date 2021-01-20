<?php


namespace Booni3\DhlExpressRest\Api;


use Booni3\DhlExpressRest\Shipment;
use Booni3\DhlExpressRest\ShipmentCreator;

class Shipments extends Client
{
    public function create(ShipmentCreator $creator): Shipment
    {
        return Shipment::fromResponse($this->post('shipments',
            [
                "plannedShippingDateAndTime" => $creator->readyAt->format($this->timeformat),
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
                "content" => array_merge([
                    "unitOfMeasurement" => "metric",
                    "isCustomsDeclarable" => $creator->getIsCustomsDeclarable(),
                    "incoterm" => $creator->incoterm,
                    "description" => $creator->description,
                    "packages" => $creator->packages()
                ], $creator->exportDecliration())
            ] + $creator->outputimage()
        ));
    }
}
