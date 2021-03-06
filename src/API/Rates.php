<?php


namespace Booni3\DhlExpressRest\API;


use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Response\RatesResponse;
use Booni3\DhlExpressRest\DTO\ShipmentCreator;

class Rates extends Client
{
    public function retrieve(ShipmentCreator $creator): RatesResponse
    {
        return RatesResponse::fromArray(
            $this->post('rates', [
                "customerDetails" => [
                    "shipperDetails" => $creator->shipper->toArray()['postalAddress'],
                    "receiverDetails" => $creator->receiver->toArray()['postalAddress']
                ],
                'accounts' => $creator->accounts(),
                //"productCode" => null,
                "plannedShippingDateAndTime" => $creator->readyAt->format(DHL::TIME_FORMAT),
                "unitOfMeasurement" => "metric",
                "isCustomsDeclarable" => $creator->customsDeclarable,
                "monetaryAmount" => [
                    [
                        "typeCode" => "declaredValue",
                        "value" => 100,
                        "currency" => "GBP"
                    ]
                ],
                "packages" => $creator->packageWeightAndDimensionsOnly()
            ])
        );
    }
}
