<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-04-01
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use PHPUnit\Framework\TestCase;

class ShippingTest extends TestCase
{
    // ---------------------------------------------------------------
    // 1. getRates parses rate response
    // ---------------------------------------------------------------
    public function testGetRatesReturnsRates(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson([
            'currency' => 'USD',
            'rates' => [
                ['carrierCode' => 'USPS', 'serviceCode' => 'PRIORITY', 'rate' => 8.50, 'currency' => 'USD', 'estimatedDays' => 2],
                ['carrierCode' => 'UPS', 'serviceCode' => 'GROUND', 'rate' => 12.30, 'currency' => 'USD', 'estimatedDays' => 5],
            ],
        ]);

        $request = [
            'origin' => ['addressLine1' => '123 Main St', 'city' => 'New York', 'stateProvince' => 'NY', 'postalCode' => '10001'],
            'destination' => ['addressLine1' => '456 Oak Ave', 'city' => 'Los Angeles', 'stateProvince' => 'CA', 'postalCode' => '90210'],
            'package' => ['weight' => 16, 'weightUnit' => 'oz'],
        ];
        $result = $client->shipping->getRates($request);

        $this->assertCount(2, $result['rates']);
        $this->assertSame('USPS', $result['rates'][0]['carrierCode']);
        $this->assertSame(8.50, $result['rates'][0]['rate']);

        // Verify correct URL path
        $last = $mock->lastRequest();
        $this->assertStringContainsString('/api/shipping/rates', $last['url']);
        $this->assertSame('POST', $last['method']);
        $this->assertSame($request, $last['body']);
    }

    // ---------------------------------------------------------------
    // 2. createLabel parses label response
    // ---------------------------------------------------------------
    public function testCreateLabelReturnsLabel(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson([
            'success' => true,
            'data' => [
                'labelId' => 'lbl_abc123',
                'trackingNumber' => '9400111899223456789012',
                'carrier' => 'USPS',
                'service' => 'Priority Mail',
                'labelFormat' => 'PDF',
                'rate' => 8.50,
                'createdAt' => '2026-04-01T00:00:00Z',
            ],
        ]);

        $result = $client->shipping->createLabel([
            'carrier' => 'USPS',
            'service' => 'Priority Mail',
            'fromAddress' => ['name' => 'Test', 'street1' => '1 St', 'city' => 'NY', 'state' => 'NY', 'zip' => '10001', 'country' => 'US'],
            'toAddress' => ['name' => 'Recv', 'street1' => '2 St', 'city' => 'LA', 'state' => 'CA', 'zip' => '90210', 'country' => 'US'],
            'parcel' => ['weight' => 16],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('lbl_abc123', $result['data']['labelId']);
        $this->assertSame('9400111899223456789012', $result['data']['trackingNumber']);

        $last = $mock->lastRequest();
        $this->assertStringContainsString('/shipping/labels', $last['url']);
        $this->assertSame('POST', $last['method']);
    }

    // ---------------------------------------------------------------
    // 3. track parses tracking response
    // ---------------------------------------------------------------
    public function testTrackReturnsTrackingInfo(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson([
            'success' => true,
            'data' => [
                'trackingNumber' => '1Z999AA10123456784',
                'carrier' => 'UPS',
                'status' => 'delivered',
                'events' => [
                    ['timestamp' => '2026-03-30T12:00:00Z', 'status' => 'delivered', 'description' => 'Package delivered'],
                ],
            ],
        ]);

        $result = $client->shipping->track('1Z999AA10123456784');

        $this->assertTrue($result['success']);
        $this->assertSame('delivered', $result['data']['status']);
        $this->assertSame('UPS', $result['data']['carrier']);
        $this->assertCount(1, $result['data']['events']);

        $last = $mock->lastRequest();
        $this->assertStringContainsString('/shipping/track/1Z999AA10123456784', $last['url']);
        $this->assertSame('GET', $last['method']);
    }

    // ---------------------------------------------------------------
    // 4. getCheapestRate hits correct path
    // ---------------------------------------------------------------
    public function testGetCheapestRateUsesCorrectPath(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson([
            'success' => true,
            'data' => ['carrier' => 'USPS', 'service' => 'Ground Advantage', 'rate' => 5.25],
        ]);

        $result = $client->shipping->getCheapestRate(TestHelper::rateRequest());

        $this->assertSame(5.25, $result['data']['rate']);

        $last = $mock->lastRequest();
        $this->assertStringContainsString('/shipping/rates/cheapest', $last['url']);
    }
}
