<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\ShipmentException;
use Booni3\DhlExpressRest\Tests\Support\ShipmentCreatorFactory;
use PHPUnit\Framework\TestCase;

class InvoiceChargeTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidChargeValues
     */
    public function it_rejects_invalid_invoice_charge_values(float $value)
    {
        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Invoice charge type or value is invalid');

        ShipmentCreatorFactory::ratesCreator()->addInvoiceCharge('freight', $value);
    }

    /** @test */
    public function it_rejects_unknown_invoice_charge_types()
    {
        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Invoice charge type or value is invalid');

        ShipmentCreatorFactory::ratesCreator()->addInvoiceCharge('margin', 1.0);
    }

    /** @test */
    public function it_accepts_the_minimum_invoice_charge_value()
    {
        $creator = ShipmentCreatorFactory::ratesCreator();

        $creator->addInvoiceCharge('freight', 0.001);

        $this->addToAssertionCount(1);
    }

    public function invalidChargeValues(): array
    {
        return [
            'negative' => [-0.001],
            'zero' => [0.0],
            'below minimum' => [0.0009],
            'more than three decimal places' => [0.0011],
        ];
    }
}
