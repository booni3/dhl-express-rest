<?php


namespace Booni3\DhlExpressRest\API;


use Booni3\DhlExpressRest\ShipmentException;
use Carbon\Carbon;

class Tracking extends Client
{
    public function single(string $trackingNumber)
    {
        return $this->get("shipments/$trackingNumber/tracking", [
            'trackingView' => 'all-checkpoints',
            'levelOfDetail' => 'all'
        ]);
    }

    public function multi(array $trackingNumbers, ?Carbon $from = null, ?Carbon $to = null)
    {
        if (count($trackingNumbers) === 0) {
            throw ShipmentException::missingInformation('tracking numbers');
        }

        if (count($trackingNumbers) > 200) {
            throw ShipmentException::missingInformation('tracking numbers');
        }

        $tracking = ['shipmentTrackingNumber' => array_values($trackingNumbers)];

        $data = array_filter([
            'dateRangeFrom' => $from ? $from->format('Y-m-d') : null,
            'dateRangeTo' => $to ? $to->format('Y-m-d') : null,
            'trackingView' => 'all-checkpoints',
            'levelOfDetail' => 'all'
        ], fn($row) => $row);

        return $this->get("tracking", $tracking + $data);
    }
}
