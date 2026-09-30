<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use Booni3\DhlExpressRest\Tests\Support\ShipmentCreatorFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ShipmentPayloadTest extends TestCase
{
    public function testCanonicalShipmentArrayIsTheExactWirePayload(): void
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, [], json_encode(['shipmentTrackingNumber' => '123'])),
        ], $history);
        $creator = ShipmentCreatorFactory::commercialInvoiceCreator();
        $expected = $creator->toShipmentRequestArray();

        DHL::make(['user' => 'test', 'pass' => 'test'], $client)
            ->shipments()
            ->create($creator);

        $actual = json_decode((string) $history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $actual);
        $this->assertSame($expected, $creator->toShipmentRequestArray());
    }

    /** @test */
    public function it_preserves_the_customs_ddp_ioss_shipment_payload_shape()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(201, ['Content-Type' => 'application/json'], '{}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->shipments()
            ->create(ShipmentCreatorFactory::customsCreator());

        $this->assertSame($this->expectedShipmentPayload(), json_decode((string) $history[0]['request']->getBody(), true));
    }

    /** @test */
    public function it_serializes_the_typed_commercial_invoice_fields_exactly()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(201, ['Content-Type' => 'application/json'], '{}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->shipments()
            ->create(ShipmentCreatorFactory::commercialInvoiceCreator());

        $payload = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame([
            'postalAddress' => [
                'cityName' => 'Malmesbury',
                'countryCode' => 'GB',
                'postalCode' => 'SN16 9AA',
                'addressLine1' => 'Exporter Line 1',
            ],
            'contactInformation' => [
                'phone' => '+441666000000',
                'companyName' => 'Exporter Company',
                'fullName' => 'Export Contact',
                'email' => 'exporter@example.test',
            ],
            'typeCode' => 'business',
            'registrationNumbers' => [
                [
                    'number' => 'GB123456789',
                    'issuerCountryCode' => 'GB',
                    'typeCode' => 'VAT',
                ],
                [
                    'number' => 'GB123456789000',
                    'issuerCountryCode' => 'GB',
                    'typeCode' => 'EOR',
                ],
            ],
        ], $payload['customerDetails']['exporterDetails']);

        $declaration = $payload['content']['exportDeclaration'];

        $this->assertSame([
            'number' => 7,
            'description' => 'Steel table legs',
            'price' => 6.67,
            'quantity' => [
                'value' => 3,
                'unitOfMeasurement' => 'PCS',
            ],
            'commodityCodes' => [
                ['typeCode' => 'outbound', 'value' => '012345'],
                ['typeCode' => 'inbound', 'value' => '0012345678'],
            ],
            'exportReasonType' => 'permanent',
            'manufacturerCountry' => 'PL',
            'weight' => [
                'netValue' => 1.5,
                'grossValue' => 1.8,
            ],
            'preCalculatedLineItemTotalValue' => 20.01,
            'isTaxesPaid' => false,
        ], $declaration['lineItems'][0]);
        $this->assertSame([
            'preCalculatedTotalGoodsValue' => 20.01,
            'preCalculatedTotalInvoiceValue' => 25.02,
        ], $declaration['invoice']['preCalculatedTotalValues']);
        $this->assertSame([
            ['value' => 5.01, 'typeCode' => 'freight', 'caption' => 'Freight'],
        ], $declaration['additionalCharges']);
        $this->assertSame(20.01, $payload['content']['declaredValue']);
    }

    private function expectedShipmentPayload(): array
    {
        return [
            'plannedShippingDateAndTime' => '2026-07-07T10:00:00 GMT+00:00',
            'pickup' => [
                'isRequested' => true,
            ],
            'productCode' => 'P',
            'accounts' => [
                [
                    'number' => '123456789',
                    'typeCode' => 'shipper',
                ],
                [
                    'number' => '987654321',
                    'typeCode' => 'duties-taxes',
                ],
            ],
            'valueAddedServices' => [
                [
                    'serviceCode' => 'DD',
                ],
                [
                    'serviceCode' => 'WY',
                ],
            ],
            'customerDetails' => [
                'shipperDetails' => [
                    'postalAddress' => [
                        'cityName' => 'City',
                        'countryCode' => 'GB',
                        'postalCode' => 'POST',
                        'addressLine1' => 'Line 1',
                        'addressLine2' => 'Line 2',
                        'addressLine3' => 'Line 3',
                        'countyName' => 'County',
                    ],
                    'contactInformation' => [
                        'phone' => '+441234567890',
                        'companyName' => 'Company',
                        'fullName' => 'Contact Name',
                        'email' => 'contact@example.test',
                    ],
                    'typeCode' => 'business',
                    'registrationNumbers' => [
                        [
                            'number' => 'IM1234567890',
                            'issuerCountryCode' => 'GB',
                            'typeCode' => 'SDT',
                        ],
                    ],
                ],
                'receiverDetails' => [
                    'postalAddress' => [
                        'cityName' => 'City',
                        'countryCode' => 'GB',
                        'postalCode' => 'POST',
                        'addressLine1' => 'Line 1',
                        'addressLine2' => 'Line 2',
                        'addressLine3' => 'Line 3',
                        'countyName' => 'County',
                        'provinceCode' => 'CA',
                    ],
                    'contactInformation' => [
                        'phone' => '+441234567890',
                        'companyName' => 'Company',
                        'fullName' => 'Contact Name',
                        'email' => 'contact@example.test',
                    ],
                    'typeCode' => 'direct_consumer',
                ],
            ],
            'customerReferences' => [
                [
                    'value' => 'ORDER1',
                    'typeCode' => 'CU',
                ],
            ],
            'content' => [
                'unitOfMeasurement' => 'metric',
                'isCustomsDeclarable' => true,
                'incoterm' => 'DDP',
                'description' => 'Table legs',
                'packages' => [
                    [
                        'customerReferences' => [
                            [
                                'value' => 'PKG1',
                                'typeCode' => 'CU',
                            ],
                        ],
                        'weight' => 1.2,
                        'description' => 'Box',
                        'dimensions' => [
                            'length' => 10,
                            'width' => 20,
                            'height' => 30,
                        ],
                    ],
                ],
                'declaredValue' => 250,
                'declaredValueCurrency' => 'EUR',
                'exportDeclaration' => [
                    'lineItems' => [
                        [
                            'number' => 1,
                            'description' => 'Table leg',
                            'price' => 250,
                            'quantity' => [
                                'value' => 1,
                                'unitOfMeasurement' => 'BOX',
                            ],
                            'commodityCodes' => [
                                [
                                    'typeCode' => 'outbound',
                                    'value' => '830242',
                                ],
                            ],
                            'exportReasonType' => 'permanent',
                            'manufacturerCountry' => 'GB',
                            'weight' => [
                                'netValue' => 1.2,
                                'grossValue' => 1.2,
                            ],
                        ],
                    ],
                    'invoice' => [
                        'number' => 'INV-1',
                        'date' => '2026-07-06',
                        'signatureName' => 'Adam Lambert',
                        'signatureTitle' => 'Mr.',
                    ],
                    'additionalCharges' => [
                        [
                            'value' => 12.5,
                            'typeCode' => 'freight',
                        ],
                    ],
                    'exportReason' => 'sale',
                    'exportReasonType' => 'permanent',
                    'placeOfIncoterm' => 'City',
                ],
            ],
            'outputImageProperties' => [
                'encodingFormat' => 'pdf',
                'imageOptions' => [
                    [
                        'typeCode' => 'invoice',
                        'isRequested' => true,
                        'invoiceType' => 'commercial',
                        'templateName' => 'COMMERCIAL_INVOICE_P_10',
                    ],
                    [
                        'typeCode' => 'label',
                        'templateName' => 'ECOM26_A6_002',
                    ],
                ],
            ],
        ];
    }
}
