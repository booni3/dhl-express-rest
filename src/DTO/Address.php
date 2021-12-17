<?php

namespace Booni3\DhlExpressRest\DTO;

use Booni3\DhlExpressRest\AddressException;

class Address
{
    protected $customer = [];
    protected $registrationNumbers = [];

    public function __construct(
        string $name,
        string $address1,
        string $address2,
        string $address3,
        string $city,
        string $postcode,
        string $countryCode,
        string $typeCode,
        string $company = '-',
        string $phone = '-',
        string $email = 'a@b.com',
        string $countyName = ''
    ) {
        $this->customer = [
            'postalAddress' => [
                'cityName' => $city,
                'countryCode' => strtoupper($countryCode),
                'postalCode' => $postcode,
                'addressLine1' => $address1,
                'addressLine2' => $address2,
                'addressLine3' => $address3,
                'countyName' => $countyName,
            ],
            'contactInformation' => [
                'phone' => $phone,
                'companyName' => $company,
                'fullName' => $name,
                'email' => $email,
            ],
            'typeCode' => $this->validateTypeCode($typeCode)
        ];
    }

    public function addVat($number, $issueCountry = 'GB')
    {
        if ($number) {
            $this->registrationNumbers['vat'] = [
                'number' => $number,
                'issuerCountryCode' => $issueCountry,
                'typeCode' => 'VAT',
            ];
        }

        return $this;
    }

    public function addEORI($number, $issueCountry = 'GB')
    {
        if ($number) {
            $this->registrationNumbers['eor'] = [
                'number' => $number,
                'issuerCountryCode' => $issueCountry,
                'typeCode' => 'EOR',
            ];
        }

        return $this;
    }

    public function addIOSS($number, $issueCountry = 'GB')
    {
        if ($number) {
            $this->registrationNumbers['sdt'] = [
                'number' => $number,
                'issuerCountryCode' => $issueCountry,
                'typeCode' => 'SDT',
            ];
        }

        return $this;
    }

    public function hasIOSS(): bool
    {
        return isset($this->registrationNumbers['sdt']['number']);
    }

    public function toArray()
    {
        return $this->customer + $this->registrationNumbers();
    }

    protected function registrationNumbers()
    {
        if (! $this->registrationNumbers) {
            return [];
        }

        return [
            'registrationNumbers' => array_values($this->registrationNumbers),
        ];
    }

    public function getCityName()
    {
        return $this->customer['postalAddress']['cityName'];
    }

    public function getCountryCode()
    {
        return $this->customer['postalAddress']['countryCode'];
    }

    protected function validateTypeCode(string $typeCode){
        if(! in_array($typeCode, ['business', 'direct_consumer'])){
            throw AddressException::validationException('typeCode');
        }

        return $typeCode;
    }
}
