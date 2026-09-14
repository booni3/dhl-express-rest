<?php

namespace Booni3\DhlExpressRest\Tests\Contract;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\DTO\LineItem;
use Booni3\DhlExpressRest\DTO\Package;
use Booni3\DhlExpressRest\DTO\ShipmentCreator;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use Booni3\DhlExpressRest\Tests\Support\ShipmentCreatorFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\RequestValidator;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class OpenApiRequestContractTest extends TestCase
{
    /** @var RequestValidator|null */
    private static $requestValidator;

    /** @var ServerRequestValidator|null */
    private static $serverRequestValidator;

    /** @test */
    public function shipment_request_matrix_matches_the_openapi_contract()
    {
        foreach ($this->shipmentCreators() as $case => $creator) {
            $request = $this->captureRequest(function (DHL $dhl) use ($creator) {
                $dhl->shipments()->create($creator);
            }, [
                new Response(201, ['Content-Type' => 'application/json'], '{}'),
            ]);

            try {
                $matched = $this->requestValidator()->validate($this->rewind($request));
            } catch (ValidationFailed $e) {
                $messages = [];

                do {
                    $messages[] = $e->getMessage();
                } while ($e = $e->getPrevious());

                $this->fail($case.' shipment payload failed OpenAPI validation: '.implode(' | ', $messages));
            }

            $this->assertSame('/shipments', $matched->path(), $case);
            $this->assertSame('post', $matched->method(), $case);
        }
    }

    /** @test */
    public function rates_request_matches_the_openapi_contract()
    {
        $request = $this->captureRequest(function (DHL $dhl) {
            $dhl->rates()->retrieve(ShipmentCreatorFactory::ratesCreator());
        }, [
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ]);

        $matched = $this->requestValidator()->validate($this->rewind($request));

        $this->assertSame('/rates', $matched->path());
        $this->assertSame('post', $matched->method());
    }

    /** @test */
    public function landed_cost_request_matches_the_openapi_contract()
    {
        $request = $this->captureRequest(function (DHL $dhl) {
            $dhl->landedCost()->retrieve($this->landedCostPayload());
        }, [
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ]);

        $matched = $this->requestValidator()->validate($this->rewind($request));

        $this->assertSame('/landed-cost', $matched->path());
        $this->assertSame('post', $matched->method());
    }

    /** @test */
    public function single_tracking_request_matches_the_openapi_contract()
    {
        $request = $this->captureRequest(function (DHL $dhl) {
            $dhl->tracking()->single('1234567890');
        });

        $matched = $this->requestValidator()->validate($this->rewind($request));

        $this->assertSame('/shipments/{shipmentTrackingNumber}/tracking', $matched->path());
        $this->assertSame('get', $matched->method());
    }

    /** @test */
    public function multi_tracking_request_matches_the_openapi_contract()
    {
        $request = $this->captureRequest(function (DHL $dhl) {
            $dhl->tracking()->multi(
                ['1234567890', '2345678901'],
                Carbon::parse('2026-07-01'),
                Carbon::parse('2026-07-06')
            );
        });

        $matched = $this->serverRequestValidator()->validate($this->asServerRequestWithExplodedQuery($request));

        $this->assertSame('/tracking', $matched->path());
        $this->assertSame('get', $matched->method());
    }

    /** @test */
    public function required_version_header_matches_the_openapi_contract()
    {
        $request = $this->captureRequest(function (DHL $dhl) {
            $dhl->rates()->retrieve(ShipmentCreatorFactory::ratesCreator());
        }, [
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ]);

        $this->assertSame(DHL::API_VERSION, $request->getHeaderLine('x-version'));
        $this->requestValidator()->validate($this->rewind($request));
    }

    /** @test */
    public function contract_validation_rejects_corrupt_payloads()
    {
        $request = $this->captureRequest(function (DHL $dhl) {
            $dhl->rates()->retrieve(ShipmentCreatorFactory::ratesCreator());
        }, [
            new Response(200, ['Content-Type' => 'application/json'], '{"products":[]}'),
        ]);
        $body = json_decode((string) $request->getBody(), true);
        unset($body['customerDetails']);

        $corruptRequest = $request->withBody(Utils::streamFor(json_encode($body)));

        $this->expectException(ValidationFailed::class);
        $this->requestValidator()->validate($this->rewind($corruptRequest));
    }

    private function captureRequest(callable $callback, ?array $responses = null): RequestInterface
    {
        $history = [];
        $client = MockClientFactory::create($responses ?? [
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ], $history, DHL::URI_SANDBOX);

        $callback(DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client));

        return $history[0]['request'];
    }

    private function requestValidator(): RequestValidator
    {
        if (! self::$requestValidator) {
            self::$requestValidator = (new ValidatorBuilder())
                ->fromYamlFile(dirname(__DIR__, 2).'/build/spec.yaml')
                ->getRequestValidator();
        }

        return self::$requestValidator;
    }

    private function serverRequestValidator(): ServerRequestValidator
    {
        if (! self::$serverRequestValidator) {
            self::$serverRequestValidator = (new ValidatorBuilder())
                ->fromYamlFile(dirname(__DIR__, 2).'/build/spec.yaml')
                ->getServerRequestValidator();
        }

        return self::$serverRequestValidator;
    }

    private function rewind(RequestInterface $request): RequestInterface
    {
        $request->getBody()->rewind();

        return $request;
    }

    private function asServerRequestWithExplodedQuery(RequestInterface $request): ServerRequest
    {
        $request = $this->rewind($request);

        return (new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody()
        ))->withQueryParams($this->explodeQuery($request->getUri()->getQuery()));
    }

    private function explodeQuery(string $query): array
    {
        $params = [];

        foreach (explode('&', $query) as $part) {
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $key = urldecode($key);
            $value = urldecode($value);

            if (! array_key_exists($key, $params)) {
                $params[$key] = $value;
                continue;
            }

            if (! is_array($params[$key])) {
                $params[$key] = [$params[$key]];
            }

            $params[$key][] = $value;
        }

        return $params;
    }

    /**
     * @return array<string, ShipmentCreator>
     */
    private function shipmentCreators(): array
    {
        return [
            'domestic minimal' => $this->domesticShipment(),
            'customs DAP paperless' => $this->customsDapPaperlessShipment(),
            'DDP duty payer' => ShipmentCreatorFactory::customsCreator(),
            'IOSS DAP' => $this->iossDapShipment(),
            'typed commercial invoice' => ShipmentCreatorFactory::commercialInvoiceCreator(),
            'ZPL label' => $this->zplLabelShipment(),
        ];
    }

    private function domesticShipment(): ShipmentCreator
    {
        $creator = ShipmentCreatorFactory::ratesCreator();
        $creator->setProductCode('P');
        $creator->setConsignmentDescription('Domestic shipment');

        return $creator;
    }

    private function customsDapPaperlessShipment(): ShipmentCreator
    {
        $creator = ShipmentCreatorFactory::ratesCreator();
        $creator->setProductCode('P');
        $creator->setCustomsDeclarable(true, true);
        $creator->setConsignmentDescription('Two line customs shipment');
        $creator->setExportDeclaration('sale', 'permanent', 'GBP', null, 'City');
        $creator->addExportLineItem(new LineItem('Table leg', 20.0, 1, 830242, 'GB', 1.0, null, 'BOX', 'GBP'));
        $creator->addExportLineItem(new LineItem('Bracket', 15.5, 2, 830242, 'GB', 0.5, null, 'BOX', 'GBP'));
        $creator->setInvoice('INV-2', Carbon::parse('2026-07-06'), 'Adam Lambert');

        return $creator;
    }

    private function iossDapShipment(): ShipmentCreator
    {
        $creator = ShipmentCreatorFactory::ratesCreator();
        $creator->setProductCode('P');
        $creator->setShipper(ShipmentCreatorFactory::address('business')->addIOSS('IM1234567890', 'GB'));
        $creator->setTermsIOSS('IM1234567890', 'GB');
        $creator->setCustomsDeclarable(true, true);
        $creator->setConsignmentDescription('IOSS shipment');
        $creator->setExportDeclaration('sale', 'permanent', 'GBP', 25.0, 'City');
        $creator->addExportLineItem(new LineItem('Table leg', 25.0, 1, 830242, 'GB', 1.0, null, 'BOX', 'GBP'));
        $creator->setInvoice('INV-3', Carbon::parse('2026-07-06'), 'Adam Lambert');

        return $creator;
    }

    private function landedCostPayload(): array
    {
        $creator = ShipmentCreatorFactory::customsCreator();

        return [
            'customerDetails' => [
                'shipperDetails' => $creator->shipper->toArray()['postalAddress'],
                'receiverDetails' => $creator->receiver->toArray()['postalAddress'],
            ],
            'accounts' => $creator->accounts(),
            'productCode' => 'P',
            'localProductCode' => 'P',
            'unitOfMeasurement' => 'metric',
            'currencyCode' => 'GBP',
            'isCustomsDeclarable' => true,
            'isDTPRequested' => true,
            'isInsuranceRequested' => false,
            'getCostBreakdown' => true,
            'charges' => [
                [
                    'typeCode' => 'freight',
                    'amount' => 12.5,
                    'currencyCode' => 'GBP',
                ],
            ],
            'shipmentPurpose' => 'personal',
            'transportationMode' => 'air',
            'merchantSelectedCarrierName' => 'DHL',
            'packages' => $creator->packageWeightAndDimensionsOnly(),
            'items' => [
                [
                    'number' => 1,
                    'name' => 'Table legs',
                    'description' => 'Table legs',
                    'manufacturerCountry' => 'GB',
                    'partNumber' => 'TEST-SKU',
                    'quantity' => 1,
                    'quantityType' => 'prt',
                    'unitPrice' => 84.95,
                    'unitPriceCurrencyCode' => 'GBP',
                    'commodityCode' => '9403999040',
                    'weight' => 1.2,
                    'weightUnitOfMeasurement' => 'metric',
                    'estimatedTariffRateType' => 'default_rate',
                ],
            ],
            'getTariffFormula' => true,
            'getQuotationID' => true,
        ];
    }

    private function zplLabelShipment(): ShipmentCreator
    {
        $creator = $this->domesticShipment();
        $creator->setLabelFormat('zpl');
        $creator->addPackage(new Package(0.5, 5, 5, 5, 'Second box', 'PKG2'));

        return $creator;
    }
}
