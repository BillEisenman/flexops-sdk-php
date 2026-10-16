<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-04-01
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOpsError;
use FlexOps\FlexOpsWebhookSignatureError;
use FlexOps\Resources\WebhooksResource;
use FlexOps\WebhookEventType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WebhooksTest extends TestCase
{
    // Fixed vector computed outside this SDK with the Gateway algorithm
    // (WebhookDeliveryService): lowercase hex HMAC-SHA256 of the UTF-8 string
    // "{timestamp}.{raw body}", keyed with the UTF-8 bytes of the whole secret
    // string ("whsec_" prefix included, nothing base64-decoded).
    // Cross-checked with Python hmac and .NET HMACSHA256.
    private const SECRET = 'whsec_AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8=';
    private const TIMESTAMP = '1760600000';
    private const BODY = "{\"trackingNumber\":\"9400111899223456789012\",\"carrierCode\":\"USPS\",\"status\":\"Delivered\",\"note\":\"caf\u{e9}\"}";
    private const SIGNATURE = 'sha256=47e071c489ace7e261e373daa4a358801f8d9b1d4c7d075d29e11c4fd1cd80f7';
    private const NOW = 1760600000;

    private static function verify(
        string $payload = self::BODY,
        ?string $signature = self::SIGNATURE,
        ?string $timestamp = self::TIMESTAMP,
        string $secret = self::SECRET,
        int $now = self::NOW,
        int $tolerance = WebhooksResource::DEFAULT_TOLERANCE,
    ): bool {
        return WebhooksResource::verifySignature($payload, $signature, $timestamp, $secret, $tolerance, $now);
    }

    // ---------------------------------------------------------------
    // 1. The server vector verifies
    // ---------------------------------------------------------------
    public function testServerVectorVerifies(): void
    {
        $this->assertTrue(self::verify());
    }

    // ---------------------------------------------------------------
    // 2. The sha256= prefix is optional; hex case does not matter
    // ---------------------------------------------------------------
    public function testSignaturePrefixHandling(): void
    {
        $bareHex = substr(self::SIGNATURE, strlen('sha256='));
        $this->assertTrue(self::verify(signature: $bareHex));
        $this->assertTrue(self::verify(signature: strtoupper($bareHex)));
        $this->assertFalse(self::verify(signature: 'sha1=' . $bareHex));
    }

    // ---------------------------------------------------------------
    // 3. Wrong secret, tampered body, changed timestamp, old algorithm
    // ---------------------------------------------------------------
    public function testRejectsWrongSecret(): void
    {
        $this->assertFalse(self::verify(secret: 'whsec_wrong'));
    }

    public function testRejectsTamperedBody(): void
    {
        $this->assertFalse(self::verify(payload: str_replace('Delivered', 'Returned', self::BODY)));
    }

    public function testRejectsChangedTimestamp(): void
    {
        $this->assertFalse(self::verify(timestamp: '1760600001'));
    }

    public function testRejectsBodyOnlySignature(): void
    {
        $old = 'sha256=4511bbc1123d6aaf6d2125b2043b8202385d5b35fdd4d180356499df7f18956d';
        $this->assertFalse(self::verify(signature: $old));
    }

    // ---------------------------------------------------------------
    // 4. Signature length mismatch and missing values
    // ---------------------------------------------------------------
    public function testRejectsSignatureOfWrongLength(): void
    {
        $this->assertFalse(self::verify(signature: substr(self::SIGNATURE, 0, -2)));
        $this->assertFalse(self::verify(signature: self::SIGNATURE . '00'));
        $this->assertFalse(self::verify(signature: 'sha256='));
    }

    public function testRejectsMissingSignatureOrSecret(): void
    {
        $this->assertFalse(self::verify(signature: null));
        $this->assertFalse(self::verify(signature: ''));
        $this->assertFalse(self::verify(secret: ''));
    }

    // ---------------------------------------------------------------
    // 5. Timestamps: missing, non-numeric, stale, future, tolerance
    // ---------------------------------------------------------------
    public function testRejectsMissingOrNonNumericTimestamp(): void
    {
        $this->assertFalse(self::verify(timestamp: null));
        $this->assertFalse(self::verify(timestamp: ''));
        $this->assertFalse(self::verify(timestamp: '17606OOOOO'));
        $this->assertFalse(self::verify(timestamp: '+1760600000'));
        $this->assertFalse(self::verify(timestamp: "1760600000\n"));
    }

    public function testRejectsStaleAndFutureTimestamps(): void
    {
        $this->assertTrue(self::verify(now: self::NOW + 300));
        $this->assertTrue(self::verify(now: self::NOW - 300));
        $this->assertFalse(self::verify(now: self::NOW + 301));
        $this->assertFalse(self::verify(now: self::NOW - 301));
    }

    public function testCustomToleranceAndNonPositiveFallsBackToDefault(): void
    {
        $this->assertTrue(self::verify(now: self::NOW + 600, tolerance: 900));
        $this->assertFalse(self::verify(now: self::NOW + 600, tolerance: 0));
    }

    public function testUsesRealClockByDefault(): void
    {
        $this->assertFalse(WebhooksResource::verifySignature(self::BODY, self::SIGNATURE, self::TIMESTAMP, self::SECRET));

        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . self::BODY, self::SECRET);
        $this->assertTrue(WebhooksResource::verifySignature(self::BODY, $signature, $timestamp, self::SECRET));
    }

    // ---------------------------------------------------------------
    // 6. verifySignatureOrFail throws with a reason code
    // ---------------------------------------------------------------
    public function testVerifySignatureOrFailPassesForServerVector(): void
    {
        WebhooksResource::verifySignatureOrFail(self::BODY, self::SIGNATURE, self::TIMESTAMP, self::SECRET, now: self::NOW);
        $this->addToAssertionCount(1);
    }

    public function testVerifySignatureOrFailThrowsWithCode(): void
    {
        $cases = [
            'WEBHOOK_SIGNATURE_MISMATCH' => [self::BODY, self::SIGNATURE, self::TIMESTAMP, 'whsec_wrong', self::NOW],
            'WEBHOOK_TIMESTAMP_OUTSIDE_TOLERANCE' => [self::BODY, self::SIGNATURE, self::TIMESTAMP, self::SECRET, self::NOW + 301],
            'WEBHOOK_INVALID_TIMESTAMP' => [self::BODY, self::SIGNATURE, 'abc', self::SECRET, self::NOW],
            'WEBHOOK_MISSING_SIGNATURE' => [self::BODY, null, self::TIMESTAMP, self::SECRET, self::NOW],
            'WEBHOOK_MISSING_SECRET' => [self::BODY, self::SIGNATURE, self::TIMESTAMP, '', self::NOW],
        ];
        foreach ($cases as $code => [$payload, $signature, $timestamp, $secret, $now]) {
            try {
                WebhooksResource::verifySignatureOrFail($payload, $signature, $timestamp, $secret, now: $now);
                $this->fail("expected {$code}");
            } catch (FlexOpsWebhookSignatureError $e) {
                $this->assertSame($code, $e->errorCode);
                $this->assertInstanceOf(FlexOpsError::class, $e);
            }
        }
    }

    // ---------------------------------------------------------------
    // 7. Subscriptions send events as the Gateway's comma-separated
    //    eventTypes (no spaces); the Gateway ignores an `events` field
    //    and subscribes to every event.
    // ---------------------------------------------------------------
    private static function wireJson(?array $request): string
    {
        return json_encode($request['body']);
    }

    public function testCreateSendsEventsAsCommaSeparatedEventTypes(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson([
            'success' => true,
            'data' => ['id' => 'wh-001', 'url' => 'https://example.com/hook', 'eventTypes' => 'LabelCreated,BatchCompleted'],
        ]);

        $result = $client->webhooks->create([
            'url' => 'https://example.com/hook',
            'events' => [WebhookEventType::LabelCreated, 'BatchCompleted', WebhookEventType::LabelCreated],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('wh-001', $result['data']['id']);

        $last = $mock->lastRequest();
        $this->assertSame('http://localhost/api/workspaces/ws-test-123/webhooks', $last['url']);
        $this->assertSame('POST', $last['method']);
        $this->assertSame('{"url":"https:\/\/example.com\/hook","eventTypes":"LabelCreated,BatchCompleted"}', self::wireJson($last));
    }

    public function testCreateWithoutEventsReceivesEveryEvent(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        $client->webhooks->create(['url' => 'https://example.com/hook', 'description' => 'orders']);

        $this->assertSame('{"url":"https:\/\/example.com\/hook","description":"orders"}', self::wireJson($mock->lastRequest()));
    }

    public function testCreateSendsAnExplicitEventTypesStringAsIs(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        $client->webhooks->create(['url' => 'https://example.com/hook', 'eventTypes' => 'LabelCreated,TrackingUpdated']);

        $this->assertSame('{"url":"https:\/\/example.com\/hook","eventTypes":"LabelCreated,TrackingUpdated"}', self::wireJson($mock->lastRequest()));
    }

    public function testUpdateSendsEventsAsEventTypes(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        $client->webhooks->update('wh-001', ['events' => [WebhookEventType::LabelCanceled], 'status' => 'Active']);

        $last = $mock->lastRequest();
        $this->assertSame('http://localhost/api/workspaces/ws-test-123/webhooks/wh-001', $last['url']);
        $this->assertSame('PUT', $last['method']);
        $this->assertSame('{"eventTypes":"LabelCanceled","status":"Active"}', self::wireJson($last));
    }

    public function testUpdateWithoutEventsLeavesTheSubscribedEventsAlone(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        $client->webhooks->update('wh-001', ['status' => 'Paused']);

        $this->assertSame('{"status":"Paused"}', self::wireJson($mock->lastRequest()));
    }

    /** @return array<string, array{mixed}> */
    public static function invalidEvents(): array
    {
        return [
            'dotted name' => [['label.created']],
            'name FlexOps never sends' => [['LabelCreated', 'ShipmentCreated']],
            'wrong case' => [['labelcreated']],
            'empty list' => [[]],
            'single string' => ['LabelCreated'],
            'comma-joined string' => ['LabelCreated,BatchCompleted'],
            'non-string element' => [[42]],
        ];
    }

    #[DataProvider('invalidEvents')]
    public function testCreateRefusesInvalidEventsWithoutCallingTheApi(mixed $events): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        try {
            $client->webhooks->create(['url' => 'https://example.com/hook', 'events' => $events]);
            $this->fail('expected InvalidArgumentException');
        } catch (\InvalidArgumentException) {
            $this->assertSame([], $mock->requests);
        }
    }

    #[DataProvider('invalidEvents')]
    public function testUpdateRefusesInvalidEventsWithoutCallingTheApi(mixed $events): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        try {
            $client->webhooks->update('wh-001', ['events' => $events]);
            $this->fail('expected InvalidArgumentException');
        } catch (\InvalidArgumentException) {
            $this->assertSame([], $mock->requests);
        }
    }

    public function testUnknownEventMessageNamesTheEventsFlexOpsSends(): void
    {
        ['client' => $client] = TestHelper::createClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown webhook event: label.created. FlexOps sends LabelCreated, LabelCanceled, BatchCompleted, TrackingUpdated, TrackingDelivered.');
        $client->webhooks->create(['url' => 'https://example.com/hook', 'events' => ['label.created']]);
    }

    public function testEventsAndEventTypesTogetherAreRefused(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        try {
            $client->webhooks->create(['url' => 'https://example.com/hook', 'events' => ['LabelCreated'], 'eventTypes' => '*']);
            $this->fail('expected InvalidArgumentException');
        } catch (\InvalidArgumentException) {
            $this->assertSame([], $mock->requests);
        }
    }

    public function testEventTypeValuesAreTheNamesTheGatewaySends(): void
    {
        $this->assertSame(
            ['LabelCreated', 'LabelCanceled', 'BatchCompleted', 'TrackingUpdated', 'TrackingDelivered'],
            array_map(fn (WebhookEventType $e) => $e->value, WebhookEventType::cases()),
        );
        foreach (WebhookEventType::cases() as $case) {
            $this->assertSame($case->name, $case->value);
        }
    }
}
