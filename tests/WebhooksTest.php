<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-04-01
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\Resources\WebhooksResource;
use PHPUnit\Framework\TestCase;

class WebhooksTest extends TestCase
{
    // ---------------------------------------------------------------
    // 1. Valid HMAC-SHA256 signature passes verification
    // ---------------------------------------------------------------
    public function testVerifySignatureValidReturnsTrue(): void
    {
        $payload = '{"event":"label.created","labelId":"lbl_123"}';
        $secret = 'whsec_test_secret';
        $signature = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(WebhooksResource::verifySignature($payload, $signature, $secret));
    }

    // ---------------------------------------------------------------
    // 2. Wrong signature fails verification
    // ---------------------------------------------------------------
    public function testVerifySignatureWrongSignatureReturnsFalse(): void
    {
        $payload = '{"event":"label.created"}';
        $secret = 'whsec_test_secret';

        $this->assertFalse(WebhooksResource::verifySignature($payload, 'deadbeef', $secret));
    }

    // ---------------------------------------------------------------
    // 3. Wrong secret fails verification
    // ---------------------------------------------------------------
    public function testVerifySignatureWrongSecretReturnsFalse(): void
    {
        $payload = '{"event":"label.created"}';
        $correctSecret = 'correct_secret';
        $wrongSecret = 'wrong_secret';
        $signature = hash_hmac('sha256', $payload, $correctSecret);

        $this->assertFalse(WebhooksResource::verifySignature($payload, $signature, $wrongSecret));
    }

    // ---------------------------------------------------------------
    // 4. Create webhook calls correct endpoint
    // ---------------------------------------------------------------
    public function testCreateWebhookPostsToCorrectPath(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson([
            'success' => true,
            'data' => ['id' => 'wh-001', 'url' => 'https://example.com/hook', 'events' => ['label.created']],
        ]);

        $result = $client->webhooks->create([
            'url' => 'https://example.com/hook',
            'events' => ['label.created'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('wh-001', $result['data']['id']);

        $last = $mock->lastRequest();
        $this->assertStringContainsString('/api/workspaces/ws-test-123/webhooks', $last['url']);
        $this->assertSame('POST', $last['method']);
    }
}
