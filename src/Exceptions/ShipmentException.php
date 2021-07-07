<?php


namespace Booni3\DhlExpressRest;


class ShipmentException extends \Exception
{
    public static function invalidIncoterm()
    {
        throw new static('Incoterm must be either DDP or DAP');
    }

    public static function invalidLabelEncodingFormat()
    {
        throw new static('Label format must be \'pdf\', \'zpl\', \'lp2\' or \'epl\'');
    }

    public static function missingInformation($key)
    {
        throw new static('Required information missing: '.$key);
    }

    public static function shipperNotSet()
    {
        throw new static('A shipper must be set first');
    }
}
