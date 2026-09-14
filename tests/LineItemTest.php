<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DTO\CommodityCode;
use Booni3\DhlExpressRest\DTO\LineItem;
use Booni3\DhlExpressRest\ShipmentException;
use PHPUnit\Framework\TestCase;

class LineItemTest extends TestCase
{
    /** @test */
    public function it_preserves_the_legacy_constructor_shape_and_box_default()
    {
        $lineItem = new LineItem('Legacy item', 10.0, 2, 123456, 'GB', 1.2);

        $this->assertSame('BOX', $lineItem->toArray()['quantity']['unitOfMeasurement']);
        $this->assertSame([
            ['typeCode' => 'outbound', 'value' => '123456'],
        ], $lineItem->toArray()['commodityCodes']);
        $this->assertArrayNotHasKey('number', $lineItem->toArray());
        $this->assertArrayNotHasKey('preCalculatedLineItemTotalValue', $lineItem->toArray());
    }

    /** @test */
    public function it_rejects_duplicate_directed_codes()
    {
        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Only one inbound and one outbound');

        $this->typedLineItem([
            CommodityCode::inbound('0012345678'),
            CommodityCode::inbound('0098765432'),
        ]);
    }

    /** @test */
    public function it_rejects_raw_commodity_code_arrays()
    {
        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Commodity codes must be CommodityCode objects');

        $this->typedLineItem([
            ['typeCode' => 'inbound', 'value' => '0012345678'],
        ]);
    }

    /** @test */
    public function it_does_not_invent_a_gross_weight_for_the_typed_path()
    {
        $lineItem = LineItem::forCustomsInvoice(
            1,
            'Typed item',
            10.0,
            2,
            [CommodityCode::inbound('0012345678')],
            'GB',
            1.2,
            null,
            20.0
        );

        $this->assertSame(['netValue' => 1.2], $lineItem->toArray()['weight']);
    }

    /**
     * @test
     * @dataProvider nonPositiveValues
     */
    public function it_rejects_non_positive_unit_prices(float $unitPrice)
    {
        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Line item unit price must be positive');

        $this->typedLineItem([CommodityCode::inbound('0012345678')], $unitPrice);
    }

    /**
     * @test
     * @dataProvider nonPositiveValues
     */
    public function it_rejects_non_positive_pre_calculated_totals(float $total)
    {
        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Pre-calculated line item total must be positive');

        $this->typedLineItem([CommodityCode::inbound('0012345678')], 10.0, $total);
    }

    public function nonPositiveValues(): array
    {
        return [
            'negative' => [-0.001],
            'zero' => [0.0],
        ];
    }

    private function typedLineItem(
        array $commodityCodes,
        float $unitPrice = 10.0,
        float $preCalculatedTotalValue = 20.0
    ): LineItem
    {
        return LineItem::forCustomsInvoice(
            1,
            'Typed item',
            $unitPrice,
            2,
            $commodityCodes,
            'GB',
            1.2,
            1.4,
            $preCalculatedTotalValue
        );
    }
}
