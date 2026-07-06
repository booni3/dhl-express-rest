<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\AddressException;
use Booni3\DhlExpressRest\DTO\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    /** @test */
    public function it_accepts_all_spec_type_codes()
    {
        foreach (['business', 'direct_consumer', 'government', 'other', 'private', 'reseller'] as $typeCode) {
            $this->assertSame($typeCode, $this->address($typeCode)->toArray()['typeCode']);
        }
    }

    /** @test */
    public function it_rejects_unknown_type_codes()
    {
        $this->expectException(AddressException::class);
        $this->expectExceptionMessage('Validation Exception For Address: typeCode');

        $this->address('garbage');
    }

    /** @test */
    public function it_includes_province_code_only_when_provided()
    {
        $withoutProvince = $this->address('business')->toArray();
        $withProvince = $this->address('business', 'CA')->toArray();

        $this->assertArrayNotHasKey('provinceCode', $withoutProvince['postalAddress']);
        $this->assertSame('CA', $withProvince['postalAddress']['provinceCode']);
    }

    private function address(string $typeCode = 'business', string $provinceCode = ''): Address
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
}
