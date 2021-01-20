<?php

namespace Booni3\DhlExpressRest;

class CustomerDetails
{
    protected $customer = [];
    protected $registrationNumbers = [];

    public function __construct(
        $name,
        $address1,
        $address2,
        $address3,
        $city,
        $postcode,
        $countrycode,
        $company = '-',
        $phone = '-',
        $email = 'a@b.com'
    ) {
        $this->customer = [
            "postalAddress" => [
                "cityName" => $city,
                "countryCode" => strtoupper($countrycode),
                "postalCode" => $postcode,
                "addressLine1" => $address1,
                "addressLine2" => $address2,
                "addressLine3" => $address3
            ],
            "contactInformation" => [
                "phone" => $phone,
                "companyName" => $company,
                "fullName" => $name,
                "email" => $email
            ]
        ];
    }

    public function addVat($number, $issueCountry = 'GB')
    {
        $this->registrationNumbers['vat'] = [
            'number' => $number,
            'issuerCountryCode' => $issueCountry,
            'typeCode' => 'VAT',
        ];

        return $this;
    }

    public function addEORI($number, $issueCountry = 'GB')
    {
        $this->registrationNumbers['eor'] = [
            'number' => $number,
            'issuerCountryCode' => $issueCountry,
            'typeCode' => 'EOR',
        ];

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
            'registrationNumbers' => array_values($this->registrationNumbers)
        ];
    }

    public function getCountryCode()
    {
        return $this->customer['postalAddress']['countryCode'];
    }
}
