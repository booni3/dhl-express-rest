<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use Booni3\DhlExpressRest\Tests\Support\ShipmentCreatorFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ShipmentPayloadTest extends TestCase
{
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
                            'number' => 0,
                            'description' => 'Table leg',
                            'price' => 250,
                            'priceCurrency' => 'EUR',
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
