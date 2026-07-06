<?php

namespace Booni3\DhlExpressRest\Tests\Support;

use Booni3\DhlExpressRest\DTO\Address;
use Booni3\DhlExpressRest\DTO\LineItem;
use Booni3\DhlExpressRest\DTO\Package;
use Booni3\DhlExpressRest\DTO\ShipmentCreator;
use Carbon\Carbon;

class ShipmentCreatorFactory
{
    public static function address(string $typeCode = 'business', string $provinceCode = ''): Address
    {
        return new Address(
            'Contact Name',
            'Line 1',
            'Line 2',
            'Line 3',
            'City',
            'POST',
            'gb',
            $typeCode,
            'Company',
            '+441234567890',
            'contact@example.test',
            'County',
            $provinceCode
        );
    }

    public static function ratesCreator(): ShipmentCreator
    {
        $creator = new ShipmentCreator();
        $creator->readyAt = Carbon::parse('2026-07-07 10:00:00', 'UTC');
        $creator->setShipper(self::address('business'));
        $creator->setReceiver(self::address('direct_consumer'));
        $creator->setShipperAccountNumber('123456789');
        $creator->addPackage(new Package(1.2, 10, 20, 30, 'Box', 'PKG1'));

        return $creator;
    }

    public static function customsCreator(): ShipmentCreator
    {
        $creator = self::ratesCreator();
        $creator->setPickupIsRequested(true);
        $creator->setProductCode('P');
        $creator->setShipper(self::address('business')->addIOSS('IM1234567890', 'GB'));
        $creator->setReceiver(self::address('direct_consumer', 'CA'));
        $creator->setTermsDDP('987654321');
        $creator->setCustomsDeclarable(true, true);
        $creator->setConsignmentDescription('Table legs');
        $creator->addReference('ORDER1');
        $creator->setExportDeclaration('sale', 'permanent', 'EUR', 250.0, 'City');
        $creator->addExportLineItem(new LineItem('Table leg', 250.0, 1, 830242, 'GB', 1.2, null, 'BOX', 'EUR'));
        $creator->setInvoice('INV-1', Carbon::parse('2026-07-06'), 'Adam Lambert');
        $creator->setFreightInvoiceCharge(12.5);

        return $creator;
    }
}
