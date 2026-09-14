# DHL Express REST

[![Latest Version on Packagist](https://img.shields.io/packagist/v/booni3/dhl-express-rest.svg?style=flat-square)](https://packagist.org/packages/booni3/dhl-express-rest)

PHP package for the MyDHL Express REST API. The package is hand-written around the existing DTO surface and is tested against the bundled MyDHL API 3.3.1 OpenAPI spec.

## Installation

```bash
composer require booni3/dhl-express-rest:^0.7
```

## Client

```php
use Booni3\DhlExpressRest\DHL;

$dhl = DHL::make([
    'user' => 'DHLUSER',
    'pass' => 'DHLPASS',
    'sandbox' => true, // false or omit for production
]);
```

The package sends the required `x-version: 3.3.1` header on every request.

- Sandbox base URI: `https://express.api.dhl.com/mydhlapi/test/`
- Production base URI: `https://express.api.dhl.com/mydhlapi/`

## Creating a Shipment

```php
use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\DTO\Address;
use Booni3\DhlExpressRest\DTO\Package;
use Booni3\DhlExpressRest\DTO\ShipmentCreator;

$shipment = new ShipmentCreator();
$shipment->setShipperAccountNumber('123456789');
$shipment->setProductCode('N');
$shipment->setShipper(
    (new Address(
        'John Smith',
        '21 Apple Drive',
        '',
        '',
        'Malmesbury',
        'SN16 4TB',
        'GB',
        'business',
        'My Awesome Company',
        '07111111111',
        'sender@example.com'
    ))
        ->addVat('GB1234')
        ->addEORI('GB1234')
);
$shipment->setReceiver(new Address(
    'Helen Jones',
    '4 Example Drive',
    '',
    '',
    'London',
    'E14 8DW',
    'GB',
    'direct_consumer',
    '-',
    '07111111112',
    'receiver@example.com'
));
$shipment->addReference('123456-custom-ref');
$shipment->addPackage(new Package(12.5, 20, 10, 10, 'Jumpers', 'order-ref-1244'));

$response = $dhl->shipments()->create($shipment);

$response->trackingNumber; // shipment tracking number
$response->trackingUrl;    // DHL tracking URL
$response->labelData();    // decoded label data
```

`Address` requires a DHL `typeCode`: `business`, `direct_consumer`, `government`, `other`, `private`, or `reseller`. The optional trailing constructor arguments are `countyName` and `provinceCode`.

## Customs, DDP, and IOSS

For customs-declarable shipments, set the customs flag, description, invoice, export declaration, and line items. DDP and IOSS are set explicitly.

```php
use Booni3\DhlExpressRest\DTO\LineItem;
use Booni3\DhlExpressRest\DTO\CommodityCode;
use Carbon\Carbon;

$shipment->setConsignmentDescription('Table legs');
$shipment->setCustomsDeclarable(true, true); // customs declarable, paperless trade

// DDP: adds value-added service DD and the duties/taxes billing account.
$shipment->setTermsDDP('123456789');

// IOSS: defaults to DAP unless you pass a different incoterm as the third argument.
// $shipment->setTermsIOSS('IM1234567890', 'GB');

$shipment->setInvoice('PS-1234', Carbon::now(), 'Adam Lambert');
$shipment->setExportDeclaration('sale', 'permanent', 'GBP');
$shipment->addExportLineItem(new LineItem(
    'Black steel table legs',
    84.95,
    1,
    9403999045,
    'GB',
    9.1
));
```

For commercial invoices that need directed string commodity codes, stable line
numbers, and pre-calculated values, use the typed factory. Its weights are total
line weights; the package does not multiply unit weights or calculate invoice
values.

```php
$shipment->setExporter($exporterAddress);
$shipment->addExportLineItem(LineItem::forCustomsInvoice(
    7,
    'Steel table legs',
    6.67,
    3,
    [
        CommodityCode::outbound('012345'),
        CommodityCode::inbound('0012345678'),
    ],
    'PL',
    1.5,
    1.8,
    20.01,
    false
));
$shipment->setInvoicePreCalculatedTotals(20.01, 25.02);
$shipment->addInvoiceCharge('freight', 5.01, 'Freight');
```

The typed factory uses `PCS`. The existing positional `LineItem` constructor
and its `BOX` default remain available for existing callers. Invoice charges
must be positive and use a MyDHL v3.3.1 charge type. Confirmed zero freight is
represented by adding no freight charge.

## Rates

```php
$rates = $dhl->rates()->retrieve($shipment);

$rates->cheapestProduct();
$rates->fastestProduct();
$rates->shortestTransitDaysAndCheapest();
```

When a declared value is set on the `ShipmentCreator`, rates use that value and currency. Existing callers that do not set a declared value keep the previous default of `100 GBP`.

## Landed Cost

The landed-cost endpoint accepts the MyDHL landed-cost request payload as an array and returns a `LandedCostResponse`.

```php
$landedCost = $dhl->landedCost()->retrieve($payload);

$landedCost->warnings;
$landedCost->landedCostProducts();
$landedCost->firstLandedCostProduct();
```

`firstLandedCostProduct()` summarises the selected DHL product with `totalPrice`, `priceCurrency`, `duty`, `tax`, `fee`, the Duty Tax Paid service row when DHL returns one, and the raw item breakdowns.

## Tracking

```php
$tracking = $dhl->tracking()->single('1234567890');

$multiTracking = $dhl->tracking()->multi([
    '1234567890',
    '0987654321',
]);
```

`Tracking::multi()` accepts 1 to 200 tracking numbers and sends repeated `shipmentTrackingNumber` query parameters as required by the MyDHL spec.

## Testing

```bash
composer test
```

The test suite uses Guzzle mock handlers only; it does not make live DHL calls. Contract tests validate built request payloads against `docs/dpdhl-express-api-3.3.1.yaml`, after sanitising DHL's tab-containing YAML through `bin/build-spec.php`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email adam@profilestudio.com instead of using the issue tracker.

## Credits

- [Adam](https://github.com/booni3)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Other Resources

- [Postman Collection](https://www.getpostman.com/collections/60a3a325988c8b0fcc17)
- [Brexit Export Guide](https://dhlguide.co.uk/wp-content/uploads/2020/09/DHL_BREXIT-BREXIT_ESS_GUIDE-1.pdf)
- [Supermodel Docs](https://supermodel.io/logistics/express)
