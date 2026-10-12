<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-12
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\HazmatClassification;
use PHPUnit\Framework\TestCase;

/**
 * The HAZMAT declaration is sent only when the caller sets it (0 = None is an answer);
 * the SDK never defaults it. Bodies are checked as the JSON that goes on the wire.
 */
class HazmatTest extends TestCase
{
    private static function wire(?array $request): mixed
    {
        return $request === null ? null : json_decode(json_encode($request['body']), true);
    }

    private static function label(array $extra = []): array
    {
        return ['carrierCode' => 'USPS', 'serviceCode' => 'PRIORITY'] + TestHelper::rateRequest() + $extra;
    }

    public function testValuesMatchTheGatewayEnum(): void
    {
        $this->assertSame([
            'None' => 0, 'LimitedQuantity' => 1, 'LithiumBatteryContainedInEquipment' => 2,
            'LithiumBatteryPackedWithEquipment' => 3, 'LithiumBatteryStandalone' => 4,
            'OtherRestricted' => 5, 'LithiumBatteryUsedDevice' => 6,
        ], array_column(array_map(fn ($c) => ['n' => $c->name, 'v' => $c->value], HazmatClassification::cases()), 'v', 'n'));
    }

    public function testLabelSendsHazmatOnlyWhenSetToTheGatewayRoute(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->shipping->createLabel(self::label());
        $client->shipping->createLabel(self::label(['hazmatClassification' => HazmatClassification::None]));
        $client->shipping->createLabel(self::label(['hazmatClassification' => HazmatClassification::LimitedQuantity]));

        foreach ($mock->requests as $call) {
            $this->assertSame('http://localhost/api/shipping/labels', $call['url']);
        }
        $this->assertArrayNotHasKey('hazmatClassification', self::wire($mock->requests[0]));
        $this->assertSame(0, self::wire($mock->requests[1])['hazmatClassification']);
        $this->assertSame(1, self::wire($mock->requests[2])['hazmatClassification']);
    }

    public function testRatesSendHazmatAsTheGatewayNumber(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->shipping->getRates(TestHelper::rateRequest() + ['hazmatClassification' => HazmatClassification::None]);
        $client->shipping->getRates(TestHelper::rateRequest());

        $this->assertSame(0, self::wire($mock->requests[0])['hazmatClassification']);
        $this->assertArrayNotHasKey('hazmatClassification', self::wire($mock->requests[1]));
    }

    public function testReturnLabelSendsHazmatOnlyWhenSet(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->returns->generateLabel('RMA-1');
        $client->returns->generateLabel('RMA-1', HazmatClassification::None);

        $this->assertSame('http://localhost/api/workspaces/ws-test-123/returns/RMA-1/label', $mock->requests[1]['url']);
        // The Gateway binds a required GenerateReturnLabelRequest body; {} takes its defaults.
        $this->assertSame('{}', json_encode($mock->requests[0]['body']));
        $this->assertSame(['hazmatClassification' => 0], self::wire($mock->requests[1]));
    }

    public function testValidateAddressSendsTheGatewayRequest(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $canonical = ['addressLine1' => '123 Main St', 'city' => 'New York', 'stateProvince' => 'NY',
            'postalCode' => '10001', 'countryCode' => 'US'];
        $client->shipping->validateAddress(['name' => 'J', 'street1' => '123 Main St', 'city' => 'New York',
            'state' => 'NY', 'zip' => '10001', 'country' => 'US']);
        $client->shipping->validateAddress($canonical);

        foreach ($mock->requests as $call) {
            $this->assertSame('http://localhost/api/shipping/addresses/validate', $call['url']);
        }
        $this->assertEquals(['address' => ['name' => 'J'] + $canonical], self::wire($mock->requests[0]));
        $this->assertSame(['address' => $canonical], self::wire($mock->requests[1]));
    }
}
