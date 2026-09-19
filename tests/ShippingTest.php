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
        $request = ['carrierCode' => 'USPS', 'serviceCode' => 'GROUND_ADVANTAGE',
            'origin' => ['addressLine1' => '1 St'], 'destination' => ['addressLine1' => '2 St'],
            'package' => ['weight' => 16], 'maximumPostageAmount' => 10.25];
        $mock->enqueueJson(['status' => 'Preview', 'confirmationToken' => 'approval',
            'quotedPostageAmount' => 8.5, 'maximumPostageAmount' => 10.25,
            'currency' => 'USD', 'expiresAt' => '2026-09-19T00:05:00Z']);
        $preview = $client->shipping->createLabel($request);
        $this->assertSame('Preview', $preview['status']);
        $this->assertCount(1, $mock->requests);
        $this->assertSame($request, $mock->lastRequest()['body']);
        $request['confirmationToken'] = $preview['confirmationToken'];
        $mock->enqueueJson(['message' => 'temporary'], 503);
        $mock->enqueueJson(['labelId' => 'lbl-001', 'carrierCode' => 'USPS'], 201);
        $label = $client->shipping->createLabel($request, 'purchase-001');
        $this->assertSame('lbl-001', $label['labelId']);
        $this->assertCount(3, $mock->requests);
        foreach (array_slice($mock->requests, 1) as $call) {
            $this->assertContains('Idempotency-Key: purchase-001', $call['headers']);
            $this->assertSame($request, $call['body']);
        }
        unset($request['confirmationToken']);
        $mock->enqueueJson(['status' => 'Preview']);
        $client->shipping->createLabel($request);
        $this->assertNotContains('Idempotency-Key: purchase-001', $mock->lastRequest()['headers']);
    }

    public function testLabelKeyCannotInjectHeaders(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $this->expectException(\InvalidArgumentException::class);
        $client->shipping->createLabel([], "key\r\nInjected: header");
    }

    public function testLabelApprovalErrors(): void
    {
        foreach ([[400, 'ApprovalRequired'], [409, 'ApprovalExpired']] as [$status, $code]) {
            ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
            $mock->enqueueJson(['errorCode' => $code, 'message' => $code], $status);
            try {
                $client->shipping->createLabel([]);
                $this->fail('Expected approval error');
            } catch (\FlexOps\FlexOpsError $error) {
                $this->assertSame($status, $error->statusCode);
                $this->assertSame($code, $error->errorCode);
            }
            $this->assertCount(1, $mock->requests);
        }
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
