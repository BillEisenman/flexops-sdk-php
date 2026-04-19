<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-04-01
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOpsAuthError;
use FlexOps\FlexOpsError;
use FlexOps\FlexOpsRateLimitError;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    // ---------------------------------------------------------------
    // 1. 401 throws FlexOpsAuthError
    // ---------------------------------------------------------------
    public function testUnauthorizedThrowsAuthError(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(401, 'Invalid token');

        $this->expectException(FlexOpsAuthError::class);
        $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210']);
    }

    // ---------------------------------------------------------------
    // 2. 400 throws FlexOpsError with validation details
    // ---------------------------------------------------------------
    public function testBadRequestThrowsFlexOpsError(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(400, 'Validation failed', ['weight is required']);

        try {
            $client->shipping->createLabel(['carrier' => 'USPS']);
            $this->fail('Expected FlexOpsError was not thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame(400, $e->statusCode);
            $this->assertSame('Validation failed', $e->getMessage());
            $this->assertContains('weight is required', $e->errors);
        }
    }

    // ---------------------------------------------------------------
    // 3. 429 retries and ultimately throws FlexOpsRateLimitError
    // ---------------------------------------------------------------
    public function testRateLimitRetriesAndThrows(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        // Enqueue 429 for all attempts (initial + 3 retries = 4 total)
        for ($i = 0; $i < 4; $i++) {
            $mock->enqueueError(429, 'Rate limited', retryAfter: '60');
        }

        try {
            $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210']);
            $this->fail('Expected FlexOpsRateLimitError was not thrown');
        } catch (FlexOpsRateLimitError $e) {
            $this->assertSame(429, $e->statusCode);
            $this->assertSame(60, $e->retryAfter);
            $this->assertSame('RATE_LIMITED', $e->errorCode);
            // Should have attempted 4 times (1 initial + 3 retries)
            $this->assertCount(4, $mock->requests);
        }
    }

    // ---------------------------------------------------------------
    // 4. 500 retries and succeeds on second attempt
    // ---------------------------------------------------------------
    public function testServerErrorRetriesAndSucceeds(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        // First attempt: 500, second attempt: 200
        $mock->enqueueError(500, 'Internal Server Error');
        $mock->enqueueJson(['success' => true, 'data' => []]);

        $result = $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210']);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $mock->requests, 'Should have made 2 requests (initial + 1 retry)');
    }

    // ---------------------------------------------------------------
    // 5. 403 throws FlexOpsError with FORBIDDEN code
    // ---------------------------------------------------------------
    public function testForbiddenThrowsFlexOpsError(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(403, 'Access denied');

        try {
            $client->shipping->getRecommendations(['originZip' => '10001']);
            $this->fail('Expected FlexOpsError was not thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame(403, $e->statusCode);
            $this->assertSame('FORBIDDEN', $e->errorCode);
        }
    }
}
