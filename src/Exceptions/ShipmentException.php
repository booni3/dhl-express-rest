<?php


namespace Booni3\DhlExpressRest;


class ShipmentException extends \Exception
{
    public static function invalidIncoterm()
    {
        throw new static('Incoterm must be either DDP or DAP');
    }

    public static function missingInformation($key)
    {
        throw new static('Required information missing: '.$key);
    }
}
