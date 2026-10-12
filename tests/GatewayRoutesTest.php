<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-12
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The Gateway ShippingController lives at api/Shipping and resolves the workspace from the
 * API key, so these calls are not workspace-scoped.
 */
class GatewayRoutesTest extends TestCase
{
    public function testShippingCallsUseTheGatewayRoutes(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->shipping->track('9400 1');
        $client->shipping->cancelLabel('lbl-1', 'USPS');
        $client->shipping->getCarriers();
        $client->shipping->getRecommendations(['originPostalCode' => '10001']);
        $client->shipping->predictDelivery(['carrierCode' => 'USPS']);
        $client->shipping->getSavings();

        $this->assertSame([
            ['GET', 'http://localhost/api/shipping/track/9400%201'],
            ['DELETE', 'http://localhost/api/shipping/labels/lbl-1?carrierCode=USPS'],
            ['GET', 'http://localhost/api/shipping/carriers'],
            ['POST', 'http://localhost/api/shipping/recommendations'],
            ['POST', 'http://localhost/api/shipping/predictions/delivery'],
            ['GET', 'http://localhost/api/shipping/savings'],
        ], array_map(fn ($r) => [$r['method'], $r['url']], $mock->requests));
        $this->assertSame(['originPostalCode' => '10001'], $mock->requests[3]['body']);
    }

    public function testCancelLabelNeedsACarrierCode(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        try {
            $client->shipping->cancelLabel('lbl-1');
            $this->fail('Expected a missing carrier code to be refused');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('carrierCode', $e->getMessage());
        }
        $this->assertCount(0, $mock->requests);
    }
}
