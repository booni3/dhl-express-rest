<?php

namespace Booni3\DhlExpressRest\DTO;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\ShipmentException;
use Carbon\Carbon;

class ShipmentCreator
{
    public Carbon $readyAt;
    public string $timezone = 'GMT';
    public bool $pickupRequested = false;
    public string $productCode = '';
    public string $incoterm = 'DAP';
    public ?string $description = null;
    public bool $customsDeclarable = false;

    public Address $shipper;
    public Address $receiver;

    protected array $accounts = [];
    protected array $packages = [];
    protected array $references = [];
    protected array $valueAddedServices = [];
    protected array $exportLineItems = [];
    protected int $lineItemNumber = 1;
    protected array $invoice = [];
    protected ?float $declaredValue = null;
    protected string $declaredValueCurrency = 'GBP';
    protected string $exportReason = 'sale';
    protected bool $paperless = false;
    protected ?string $labelFormat = null;

    public function __construct()
    {
        $this->setCutOffTime();
    }

    public function setCutOffTime(string $time = '4pm', $weekdaysOnly = true)
    {
        if ($weekdaysOnly && today()->isWeekend()) {
            $time = 'weekday '.$time;
        }

        $this->readyAt = now()->next($time);
    }

    public function plannedShippingDateAndTime(): string
    {
        return sprintf('%s %s%s',
            $this->readyAt->format(DHL::TIME_FORMAT),
            $this->timezone,
            $this->readyAt->setTimezone($this->timezone)->getOffsetString()
        );
    }

    public function setPickupIsRequested(bool $pickup = true)
    {
        $this->pickupRequested = $pickup;
    }

    public function setProductCode(string $code)
    {
        $this->productCode = $code;
    }

    public function setShipper(Address $contact)
    {
        $this->shipper = $contact;
    }

    public function setReceiver(Address $contact)
    {
        $this->receiver = $contact;
    }

    public function addPackage(Package $package)
    {
        $this->packages[] = $package;
    }

    public function packages()
    {
        return array_map(function (Package $row) {
            return $row->package;
        }, $this->packages);
    }

    public function packageWeightAndDimensionsOnly()
    {
        return array_map(function (Package $row) {
            return [
                'weight' => $row->package['weight'],
                'dimensions' => $row->package['dimensions'],
            ];
        }, $this->packages);
    }

    public function addReference(?string $reference)
    {
        if ($reference) {
            $this->references[] = $reference;
        }
    }

    public function references()
    {
        return array_map(function ($row) {
            return ['value' => $row, 'typeCode' => 'CU'];
        }, $this->references);
    }

    public function setShipperAccountNumber(string $accountNumber)
    {
        $this->accounts['shipper'] = [
            'number' => $accountNumber,
            'typeCode' => 'shipper',
        ];
    }

    public function setDutyPayerAccountNumber(string $accountNumber)
    {
        $this->accounts['duties'] = [
            'number' => $accountNumber,
            'typeCode' => 'duties-taxes',
        ];
    }

    public function accounts(): array
    {
        return array_values($this->accounts);
    }

    /**
     * Set if the shipment is customs declarable and choose the terms of delivery.
     * - If a DDP payer number is set, then we will set the shipment to DDP terms.
     * - Paperless setting means that details will be provided electronically and no paper invoice is needed.
     *
     * @param bool $declarable
     * @param bool $paperless
     */
    public function setCustomsDeclarable(bool $declarable = true, bool $paperless = true)
    {
        if ($this->customsDeclarable = $declarable) {
            if ($paperless) {
                $this->setPaperlessTrade();
            }
        }
    }

    protected function setIncoterm(string $incoterm)
    {
        if (! in_array(strtoupper($incoterm), ['DDP', 'DAP'])) {
            throw ShipmentException::invalidIncoterm();
        }

        $this->incoterm = strtoupper($incoterm);

        if ($this->incoterm == 'DDP') {
            $this->addValueAddedService('DD');
        }
    }

    public function setTermsDDP(string $ddpPayerAccountNumber)
    {
        $this->setIncoterm('DDP');
        $this->setDutyPayerAccountNumber($ddpPayerAccountNumber);
    }

    public function setTermsIOSS(string $importerTaxId, string $countryCode)
    {
        if(! $this->shipper){
            throw ShipmentException::shipperNotSet();
        }

        $this->setIncoterm('DAP');
        $this->shipper->addIOSS($importerTaxId, $countryCode);
    }

    public function setPaperlessTrade(bool $bool = true)
    {
        if ($bool) {
            $this->paperless = true;
            $this->addValueAddedService('WY');
        }
    }

    public function addValueAddedService($serviceCode)
    {
        $this->valueAddedServices[$serviceCode] = $serviceCode;
    }

    public function valueAddedServices(): array
    {
        return array_values(
            array_map(function ($val) {
                return ['serviceCode' => $val];
            }, $this->valueAddedServices)
        );
    }

    public function content(): array
    {
        return array_merge([
            'unitOfMeasurement' => 'metric',
            'isCustomsDeclarable' => $this->customsDeclarable,
            'incoterm' => $this->incoterm,
            'description' => $this->description(),
            'packages' => $this->packages(),
        ], $this->exportDeclaration());
    }

    public function outputImage(): array
    {
        return [
            'outputImageProperties' => [
                'encodingFormat' => $this->labelFormat(),
                'imageOptions' => [
                    [
                        'typeCode' => 'invoice',
                        'isRequested' => $this->customsDeclarable,
                        'invoiceType' => 'commercial',
                    ],
                    [
                        'typeCode' => 'label',
                        'templateName' => 'ECOM26_A6_002',
                    ],
                ],
            ],
        ];
    }

    public function setExportDeclaration($reason = 'sale', $declaredValueCurrency = 'GBP', $declaredValue = null)
    {
        $this->exportReason = $reason;
        $this->declaredValueCurrency = $declaredValueCurrency;
        $this->declaredValue = $declaredValue;
    }

    public function exportDeclaration()
    {
        if ($this->customsDeclarable === false) {
            return [];
        }

        return [
            'isCustomsDeclarable' => true,
            'declaredValue' => round($this->declaredValue ?? $this->declaredValueFromItems($this->exportLineItems()), 2),
            'declaredValueCurrency' => $this->declaredValueCurrency,
            'exportDeclaration' => [
                'lineItems' => $this->exportLineItems(),
                'invoice' => $this->invoice(),
                'exportReason' => $this->exportReason,
            ],
        ];
    }

    public function addExportLineItem(LineItem $lineItem)
    {
        $this->exportLineItems[] = $lineItem;
    }

    protected function exportLineItems()
    {
        if (! $this->exportLineItems) {
            throw ShipmentException::missingInformation('export line items');
        }

        return array_values(
            array_map(function (LineItem $lineItem) {
                return array_merge(['number' => $this->lineItemNumber++], $lineItem->toArray());
            }, $this->exportLineItems)
        );
    }

    public function setInvoice(string $number, Carbon $date, string $signatureName, string $signatureTitle = 'Mr.')
    {
        $this->invoice = [
            'number' => $number,
            'date' => $date->format('Y-m-d'),
            'signatureName' => $signatureName,
            'signatureTitle' => $signatureTitle,
        ];
    }

    protected function invoice(): array
    {
        if (! $this->invoice) {
            throw ShipmentException::missingInformation('invoice');
        }

        return $this->invoice;
    }

    protected function declaredValueFromItems($items): float
    {
        return array_reduce($items, function ($i, $row) {
            return ($row['price'] * $row['quantity']['value']) + $i;
        }, 0);
    }

    public function setConsignmentDescription(string $description)
    {
        $this->description = $description;
    }

    protected function description()
    {
        if (! $this->description && $this->customsDeclarable) {
            throw ShipmentException::missingInformation('description');
        }

        return $this->description;
    }

    public function setLabelFormat(string $format)
    {
        $format = strtolower($format);

        if (! in_array($format, ['pdf', 'zpl', 'lp2', 'epl'])) {
            throw ShipmentException::invalidLabelEncodingFormat();
        }

        $this->labelFormat = $format;
    }

    protected function labelFormat(): string
    {
        if ($this->labelFormat) {
            return $this->labelFormat;
        }

        return 'pdf';
    }

    /**
     * Sets the label to DHL data staging mode, which remains active for 3 months
     */
    public function setLongExpiration()
    {
        $this->addValueAddedService('PT');
    }
}
