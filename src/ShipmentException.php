<?php


namespace Booni3\DhlExpressRest;


class ShipmentException extends \Exception
{
    public static function duplicateCommodityCodeDirection()
    {
        return new static('Only one inbound and one outbound commodity code may be supplied');
    }

    public static function invalidCommodityCodeCount()
    {
        return new static('One or two commodity codes must be supplied');
    }

    public static function invalidCommodityCodeDirection()
    {
        return new static('Commodity code direction must be inbound or outbound');
    }

    public static function invalidCommodityCodeType()
    {
        return new static('Commodity codes must be CommodityCode objects');
    }

    public static function invalidCommodityCodeValue()
    {
        return new static('Commodity code value must contain between 2 and 18 characters');
    }

    public static function invalidInvoiceCharge()
    {
        return new static('Invoice charge type or value is invalid');
    }

    public static function invalidInvoiceTotals()
    {
        return new static('Pre-calculated invoice totals must not be negative');
    }

    public static function invalidLineItemNumber()
    {
        return new static('Line item number must be between 1 and 999');
    }

    public static function invalidLineItemQuantity()
    {
        return new static('Line item quantity must be positive');
    }

    public static function invalidLineItemPrice()
    {
        return new static('Line item unit price must be positive');
    }

    public static function invalidLineItemTotal()
    {
        return new static('Pre-calculated line item total must be positive');
    }

    public static function invalidLineItemWeight()
    {
        return new static('Line item total weights must be positive');
    }

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
