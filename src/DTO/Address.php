<?php

namespace Booni3\DhlExpressRest\DTO;

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
        string $countrycode,
        string $company = '-',
        string $phone = '-',
        string $email = 'a@b.com'
    ) {
        $this->customer = [
            'postalAddress' => [
                'cityName' => $city,
                'countryCode' => strtoupper($countrycode),
                'postalCode' => $postcode,
                'addressLine1' => $address1,
                'addressLine2' => $address2,
                'addressLine3' => $address3,
            ],
            'contactInformation' => [
                'phone' => $phone,
                'companyName' => $company,
                'fullName' => $name,
                'email' => $email,
            ],
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

    public function getCountryCode()
    {
        return $this->customer['postalAddress']['countryCode'];
    }
}
