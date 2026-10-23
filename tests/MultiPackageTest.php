<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-22
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOpsError;
use FlexOps\HazmatClassification;
use PHPUnit\Framework\TestCase;

/**
 * Multi-package labels (Gateway MultiPackageController, POST /api/shipping/multi-package) and the
 * RMA reads (ReturnsController GET returns/rma and returns/rma/{rmaNumber}).
 */
class MultiPackageTest extends TestCase
{
    private static function request(): array
    {
        return [
            'orderId' => 15563627,
            'carrierCode' => 'USPS',
            'serviceCode' => 'PRIORITY',
            'hazmatClassification' => HazmatClassification::None,
            'packages' => [
                ['weightInOunces' => 16, 'lengthInInches' => 10, 'widthInInches' => 8, 'heightInInches' => 4, 'packageReference' => 'Box 1 of 2'],
                ['weightInOunces' => 8, 'lengthInInches' => 6, 'widthInInches' => 6, 'heightInInches' => 6],
            ],
        ];
    }

    public function testPostsTheOrderAndBoxesWithTheCallerKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(['success' => true, 'data' => ['orderId' => 15563627, 'successfulPackages' => 2, 'failedPackages' => 0,
            'labels' => [['packageIndex' => 0, 'success' => true, 'trackingNumber' => '9400-1'], ['packageIndex' => 1, 'success' => true, 'trackingNumber' => '9400-2']]]]);

        $result = $client->shipping->createMultiPackage(self::request(), 'order-15563627');

        $request = $mock->lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame('http://localhost/api/shipping/multi-package', $request['url']);
        $this->assertSame('order-15563627', MockHttpClient::header($request, 'Idempotency-Key'));
        $sent = json_decode(json_encode($request['body']), true);
        $this->assertSame(0, $sent['hazmatClassification']);
        $this->assertSame(15563627, $sent['orderId']);
        $this->assertCount(2, $sent['packages']);
        $this->assertSame('9400-2', $result['data']['labels'][1]['trackingNumber']);
    }

    public function testGeneratesAKeyWhenNoneIsGiven(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(['success' => true, 'data' => ['labels' => []]]);

        $client->shipping->createMultiPackage(self::request());

        $this->assertNotEmpty(MockHttpClient::header($mock->lastRequest(), 'Idempotency-Key'));
    }

    public function testUnknownOutcomeKeepsThePerBoxResultsAndIsNotRetried(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $data = ['orderId' => 1, 'totalPackages' => 1, 'successfulPackages' => 0, 'failedPackages' => 1,
            'labels' => [['packageIndex' => 0, 'success' => false, 'errorCode' => 'OutcomeUnknown']]];
        $mock->enqueueJson(['success' => false, 'message' => 'No label was confirmed', 'errorCode' => 'OutcomeUnknown', 'data' => $data], 409);

        try {
            $client->shipping->createMultiPackage(self::request());
            $this->fail('Expected a FlexOpsError');
        } catch (FlexOpsError $e) {
            $this->assertSame(409, $e->statusCode);
            $this->assertSame('OutcomeUnknown', $e->errorCode);
            $this->assertSame($data, $e->data);
        }
        $this->assertCount(1, $mock->requests);
    }

    public function testRefusesAKeyWithALineBreak(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $this->expectException(\InvalidArgumentException::class);
        $client->shipping->createMultiPackage(self::request(), "key\r\nX-Injected: 1");
    }

    public function testRmaReadsUseTheRmaRoutes(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->returns->getRma('RMA-1001');
        $client->returns->listRmas(2, 50);
        $client->returns->listRmas();
        $client->returns->get(42);

        $base = 'http://localhost/api/workspaces/ws-test-123/returns';
        $this->assertSame([
            ['GET', "{$base}/rma/RMA-1001"],
            ['GET', "{$base}/rma?page=2&pageSize=50"],
            ['GET', "{$base}/rma"],
            ['GET', "{$base}/42"],
        ], array_map(fn ($r) => [$r['method'], $r['url']], $mock->requests));
    }
}
