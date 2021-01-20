<?php


namespace Booni3\DhlExpressRest;


class Shipment
{
    public $trackingNumber = '';
    public $trackingUrl = '';
    protected $documents = [];
    protected $label = [];

    public static function fromResponse(array $data)
    {
        $static = new static();
        $static->trackingNumber = $data['shipmentTrackingNumber'];
        $static->trackingUrl = $data['trackingUrl'];
        $static->documents = $data['documents'];
        $static->label = array_filter($data['documents'], function($row){
            return $row['typeCode'] == 'label';
        })[0]??[];

        return $static;
    }

    public function labelFormat(): ?string
    {
        return $this->label['imageFormat'] ?? null;
    }

    public function labelData()
    {
        if($this->labelFormat() == 'PDF'){
            return base64_decode($this->label['content']);
        }
    }
}
