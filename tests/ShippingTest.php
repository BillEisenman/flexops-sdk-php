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
            'success' => true,
            'data' => [
                ['carrier' => 'USPS', 'service' => 'Priority Mail', 'rate' => 8.50, 'currency' => 'USD', 'estimatedDays' => 2],
                ['carrier' => 'UPS', 'service' => 'Ground', 'rate' => 12.30, 'currency' => 'USD', 'estimatedDays' => 5],
            ],
        ]);

        $result = $client->shipping->getRates([
            'fromZip' => '10001',
            'toZip' => '90210',
            'weight' => 16,
            'weightUnit' => 'oz',
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('USPS', $result['data'][0]['carrier']);
        $this->assertSame(8.50, $result['data'][0]['rate']);

        // Verify correct URL path
        $last = $mock->lastRequest();
        $this->assertStringContainsString('/api/workspaces/ws-test-123/shipping/rates', $last['url']);
        $this->assertSame('POST', $last['method']);
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

        $result = $client->shipping->getCheapestRate(['fromZip' => '10001', 'toZip' => '90210', 'weight' => 8]);

        $this->assertSame(5.25, $result['data']['rate']);

        $last = $mock->lastRequest();
        $this->assertStringContainsString('/shipping/rates/cheapest', $last['url']);
    }
}
