<?php

namespace Booni3\DhlExpressRest\Response;

class ShipmentResponse
{
    public $trackingNumber = '';
    public $trackingUrl = '';
    protected $documents = [];
    protected $label = [];
    protected $invoice = [];

    public static function fromArray(array $data)
    {
        $static = new static();
        $static->trackingNumber = $data['shipmentTrackingNumber'];
        $static->trackingUrl = $data['trackingUrl'];
        $static->documents = $data['documents'];
        $static->label = array_values(array_filter($data['documents'], function ($row) {
                return $row['typeCode'] == 'label';
            }))[0] ?? [];
        $static->invoice = array_values(array_filter($data['documents'], function ($row) {
                return $row['typeCode'] == 'invoice';
            }))[0] ?? [];

        return $static;
    }

    public function labelFormat(): ?string
    {
        return $this->label['imageFormat'] ?? null;
    }

    public function labelData()
    {
        if(! isset($this->label['content'])){
            return null;
        }

        return base64_decode($this->label['content']);
    }

    public function invoiceFormat(): ?string
    {
        return $this->invoice['imageFormat'] ?? null;
    }

    public function invoiceData()
    {
        if(! isset($this->invoice['content'])){
            return null;
        }

        return base64_decode($this->invoice['content']);
    }
}
