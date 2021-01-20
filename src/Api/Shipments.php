<?php


namespace Booni3\DhlExpressRest\Api;


class Shipments extends Client
{
    public function create()
    {
        return $this->post('shipments', []);
    }
}
