<?php

namespace Booni3\DhlExpressRest\Tests;

use Booni3\DhlExpressRest\DHL;
use Booni3\DhlExpressRest\ShipmentException;
use Booni3\DhlExpressRest\Tests\Support\MockClientFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class TrackingTest extends TestCase
{
    /** @test */
    public function multi_tracking_uses_repeated_tracking_number_query_params()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->multi(['A', 'B']);

        $this->assertSame(
            'shipmentTrackingNumber=A&shipmentTrackingNumber=B&trackingView=all-checkpoints&levelOfDetail=all',
            $history[0]['request']->getUri()->getQuery()
        );
    }

    /** @test */
    public function multi_tracking_includes_date_filters()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->multi(['A'], Carbon::parse('2026-07-01'), Carbon::parse('2026-07-06'));

        $this->assertSame(
            'shipmentTrackingNumber=A&dateRangeFrom=2026-07-01&dateRangeTo=2026-07-06&trackingView=all-checkpoints&levelOfDetail=all',
            $history[0]['request']->getUri()->getQuery()
        );
    }

    /** @test */
    public function single_tracking_query_shape_is_unchanged()
    {
        $history = [];
        $client = MockClientFactory::create([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ], $history);

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->single('123');

        $this->assertSame('/shipments/123/tracking', $history[0]['request']->getUri()->getPath());
        $this->assertSame(
            'trackingView=all-checkpoints&levelOfDetail=all',
            $history[0]['request']->getUri()->getQuery()
        );
    }

    /** @test */
    public function multi_tracking_requires_at_least_one_tracking_number()
    {
        $history = [];
        $client = MockClientFactory::create([], $history);

        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Required information missing: tracking numbers');

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->multi([]);
    }

    /** @test */
    public function multi_tracking_accepts_at_most_200_tracking_numbers()
    {
        $history = [];
        $client = MockClientFactory::create([], $history);

        $this->expectException(ShipmentException::class);
        $this->expectExceptionMessage('Required information missing: tracking numbers');

        DHL::make(['user' => 'u', 'pass' => 'p', 'sandbox' => true], $client)
            ->tracking()
            ->multi(range(1, 201));
    }
}
