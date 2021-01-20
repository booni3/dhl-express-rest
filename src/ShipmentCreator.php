<?php

namespace Booni3\DhlExpressRest;

use Carbon\Carbon;

class ShipmentCreator
{
    public Carbon $readyAt;
    public bool $pickupRequested = false;
    public string $productCode;
    public string $incoterm = 'DAP';
    public string $description = 'Test Description';

    public CustomerDetails $shipper;
    public CustomerDetails $receiver;

    public string $billingAccountNumber;
    public string $billingType = 'shipper';

    protected array $packages = [];
    protected array $references = [];
    protected array $valueAddedServices = [];
    protected array $exportLineItems = [];
    protected int $lineItemNumber = 1;
    protected array $invoice = [];
    protected ?bool $customsDeclarable = null;
    protected ?float $declaredValue = null;
    protected string $declaredValueCurrency = 'GBP';
    protected string $exportReason = 'sale';
    protected int $accountNumber;
    protected bool $paperless = false;

    public function __construct()
    {
        $this->readyAt = now()->endOfDay();
    }

    public function setReadyAt(Carbon $carbon)
    {
        $this->readyAt = $carbon;
    }

    public function setPickupIsRequested(bool $pickup = true)
    {
        $this->pickupRequested = $pickup;
    }

    public function setProductCode(string $code)
    {
        $this->productCode = $code;
    }

    public function setShipper(CustomerDetails $contact)
    {
        $this->shipper = $contact;
    }

    public function setReceiver(CustomerDetails $contact)
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

    public function addReference($reference)
    {
        $this->references[] = $reference;
    }

    public function references()
    {
        return array_map(function ($row) {
            return ["value" => $row, "typeCode" => "CU"];
        }, $this->references);
    }

    public function setAccountNumber($accountNumber)
    {
        $this->billingAccountNumber = $this->accountNumber = $accountNumber;
    }

    public function setIncoterm(string $incoterm)
    {
        $incoterm = strtoupper($incoterm);

        if (! in_array($incoterm, ['DDP', 'DAP'])) {
            throw ShipmentException::invalidIncoterm();
        }

        if ($incoterm == 'DDP') {
            $this->incoterm = 'DDP';
            $this->billingAccountNumber = $this->accountNumber;
//            $this->billingType = 'duties-taxes';
            $this->addValueAddedService('DD');
        }
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
                return ["serviceCode" => $val];
            }, $this->valueAddedServices)
        );
    }

    public function outputImage(): array
    {
        if ($this->paperless === false) {
            return [];
        }

        return [
            'outputImageProperties' => [
                "encodingFormat" => "pdf",
                "imageOptions" => [
                    [
                        "typeCode" => "invoice",
                        "isRequested" => true,
                        "invoiceType" => "commercial"
                    ],
                    [
                        "typeCode" => "label",
                        "templateName" => "ECOM26_A6_002"
                    ]
                ]
            ]
        ];
    }

    public function getIsCustomsDeclarable(): bool
    {
        if($this->paperless === false){
            return false;
        }

        if(is_null($this->customsDeclarable)){
            $this->exportDecliration();
        }

        return $this->customsDeclarable;
    }

    public function setExportDecliration($reason = 'sale', $declaredValueCurrency = 'GBP', $declaredValue = null)
    {
        $this->exportReason = $reason;
        $this->declaredValueCurrency = $declaredValueCurrency;
        $this->declaredValue = $declaredValue;
    }

    public function exportDecliration()
    {
        if ($this->paperless === false) {
            return [];
        }

        return [
            "isCustomsDeclarable" => $this->customsDeclarable = true,
            "declaredValue" => $this->declaredValue ?? $this->declaredValueFromItems($this->exportLineItems()),
            "declaredValueCurrency" => $this->declaredValueCurrency,
            "exportDeclaration" => [
                "lineItems" => $this->exportLineItems(),
                "invoice" => $this->invoice(),
                "exportReason" => $this->exportReason
            ]
        ];
    }

    public function addExportLineItem(LineItem $lineItem)
    {
        $this->exportLineItems[] = $lineItem;
    }

    protected function exportLineItems()
    {
        if(! $this->exportLineItems){
            throw ShipmentException::missingInformation('export line items');
        }

        return array_values(
            array_map(function(LineItem $lineItem){
                return array_merge(['number' => $this->lineItemNumber++], $lineItem->toArray());
            },$this->exportLineItems)
        );
    }

    public function setInvoice($number, Carbon $date, string $signatureName, string $signatureTitle = 'Mr.')
    {
        $this->invoice = [
            "number" => $number,
            "date" => $date->format('Y-m-d'),
            "signatureName" => $signatureName,
            "signatureTitle" => $signatureTitle
        ];
    }

    protected function invoice(): array
    {
        if(! $this->invoice){
            throw ShipmentException::missingInformation($invoice);
        }

        return $this->invoice;
    }

    protected function declaredValueFromItems($items): float
    {
        return array_reduce($items, function($i, $row){
            return ($row['price'] * $row['quantity']['value']) + $i;
        }, 0);
    }
}
