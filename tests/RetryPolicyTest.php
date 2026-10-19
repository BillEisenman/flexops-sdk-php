<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOpsError;
use FlexOps\FlexOpsRateLimitError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Gateway replays a POST/PUT/PATCH that repeats an Idempotency-Key, but does not cache a
 * response >= 500 and does not dedupe DELETE. A retried purchase must never charge twice.
 */
class RetryPolicyTest extends TestCase
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private static function keys(MockHttpClient $mock): array
    {
        return array_map(fn ($r) => MockHttpClient::header($r, 'Idempotency-Key'), $mock->requests);
    }

    public function testAWriteSendsOneGeneratedKeyOnEveryAttempt(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(503, 'Unavailable')->enqueueJson(['success' => true], 201);

        $client->carbonOffsets->purchase('9400 1', 2.0, 300.0);

        $keys = self::keys($mock);
        $this->assertCount(2, $keys);
        $this->assertMatchesRegularExpression(self::UUID_V4, $keys[0]);
        $this->assertSame($keys[0], $keys[1]);
    }

    public function testPurchasesSendTheCallersKeyOnEveryAttempt(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(503, 'Unavailable')->enqueueJson(['id' => 'pol-1'], 201);

        $client->insurance->purchase('9400 1', 'USPS', 100.0, idempotencyKey: 'order-42-insurance');
        $client->carbonOffsets->purchase('9400 1', 2.0, 300.0, 'order-42-offset');
        $client->carbonOffsets->purchase('9400 1', 2.0, 300.0, 'order-42-offset');

        $this->assertSame(['order-42-insurance', 'order-42-insurance', 'order-42-offset', 'order-42-offset'], self::keys($mock));
    }

    public function testEachSeparateWriteGetsANewKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->webhooks->test('wh-1');
        $client->webhooks->test('wh-1');

        $keys = self::keys($mock);
        $this->assertNotNull($keys[0]);
        $this->assertNotSame($keys[0], $keys[1]);
    }

    public function testPutAndPatchSendAKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->carbonOffsets->updateSettings(true);
        $mock->patch('/api/anything', ['a' => 1]);

        foreach (self::keys($mock) as $key) {
            $this->assertMatchesRegularExpression(self::UUID_V4, $key);
        }
    }

    public function testACallersKeyIsSentOnceAndReusedOnRetry(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueNetworkError()->enqueueJson(['success' => true]);

        $client->shipping->createLabel(['carrierCode' => 'USPS'], 'purchase-42');

        $this->assertSame(['purchase-42', 'purchase-42'], self::keys($mock));
        foreach ($mock->requests as $request) {
            $sent = array_filter($request['headers'], fn ($h) => stripos($h, 'Idempotency-Key:') === 0);
            $this->assertCount(1, $sent);
        }
    }

    public function testACallersKeyCannotInjectHeaders(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        try {
            $client->shipping->createLabel(['carrierCode' => 'USPS'], "k\r\nX-Evil: 1");
            $this->fail('Expected the key to be refused');
        } catch (\InvalidArgumentException) {
        }
        $this->assertCount(0, $mock->requests);
    }

    public function testAWriteIsNotRetriedAfterA500(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(500, 'Internal error')->enqueueJson(['success' => true]);

        try {
            $client->carbonOffsets->purchase('9400 1', 2.0, 300.0);
            $this->fail('Expected the 500 to be thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame(500, $e->statusCode);
        }
        $this->assertCount(1, $mock->requests);
    }

    /** @return array<string, array{int}> */
    public static function writeRetryableStatuses(): array
    {
        return ['502' => [502], '503' => [503], '504' => [504], '429' => [429]];
    }

    #[DataProvider('writeRetryableStatuses')]
    public function testAWriteRetriesWhereTheGatewayDidNotRunItOrWillReplayIt(int $status): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError($status, 'Try again')->enqueueJson(['success' => true]);

        $this->assertSame(['success' => true], $client->shipping->getRates(TestHelper::rateRequest()));
        $this->assertCount(2, $mock->requests);
        $this->assertSame(self::keys($mock)[0], self::keys($mock)[1]);
    }

    public function testAWriteRetriesADroppedConnectionWithTheSameKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueNetworkError('Operation timed out')->enqueueJson(['success' => true]);

        $client->insurance->purchase('9400 1', 'USPS', 100.0);

        $this->assertCount(2, $mock->requests);
        $this->assertSame(self::keys($mock)[0], self::keys($mock)[1]);
    }

    public function testAWriteWaitsOutAnInFlightDuplicate(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(409, 'In progress', errorCode: 'IDEMPOTENCY_CONFLICT')
            ->enqueueJson(['success' => true, 'data' => ['id' => 'pol-1']]);

        $result = $client->insurance->purchase('9400 1', 'USPS', 100.0);

        $this->assertSame('pol-1', $result['data']['id']);
        $this->assertCount(2, $mock->requests);
    }

    /** Gateway #736: a key reused with a different request is refused with 422 and must not be retried. */
    public function testAReusedKeyWithADifferentRequestIsSentOnce(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(422, 'Key reused', errorCode: 'IDEMPOTENCY_KEY_REUSED')->enqueueJson(['success' => true]);

        try {
            $client->shipping->createLabel(['carrierCode' => 'USPS'], 'purchase-42');
            $this->fail('Expected the 422 to be thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame(422, $e->statusCode);
            $this->assertSame('IDEMPOTENCY_KEY_REUSED', $e->errorCode);
        }
        $this->assertCount(1, $mock->requests);
    }

    /** Label purchase and managed returns answer altered inputs with a PascalCase 409; codes match exactly. */
    public function testThePascalCaseIdempotencyConflictIsNotRetried(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(409, 'Inputs changed', errorCode: 'IdempotencyConflict')->enqueueJson(['success' => true]);

        try {
            $client->shipping->createLabel(['carrierCode' => 'USPS'], 'purchase-42');
            $this->fail('Expected the 409 to be thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame('IdempotencyConflict', $e->errorCode);
        }
        $this->assertCount(1, $mock->requests);
    }

    public function testAnyOther409IsNotRetried(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(409, 'Already a member', errorCode: 'AlreadyMember');

        try {
            $client->workspaces->inviteMember('a@example.com', 'Member');
            $this->fail('Expected the 409 to be thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame(409, $e->statusCode);
            $this->assertSame('AlreadyMember', $e->errorCode);
        }
        $this->assertCount(1, $mock->requests);
    }

    public function testADeleteIsNotResentAfterADroppedConnectionOrA503(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueNetworkError();
        try {
            $client->webhooks->delete('wh-1');
            $this->fail('Expected the network error to be thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame('NETWORK_ERROR', $e->errorCode);
        }
        $this->assertCount(1, $mock->requests);

        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(503, 'Unavailable')->enqueueJson(['success' => true]);
        try {
            $client->webhooks->delete('wh-1');
            $this->fail('Expected the 503 to be thrown');
        } catch (FlexOpsError $e) {
            $this->assertSame(503, $e->statusCode);
        }
        $this->assertCount(1, $mock->requests);
        $this->assertNull(self::keys($mock)[0]);
    }

    public function testADeleteRetriesARateLimit(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(429, 'Slow down', retryAfter: '1')->enqueueEmpty();

        $this->assertNull($client->webhooks->delete('wh-1'));
        $this->assertCount(2, $mock->requests);
    }

    public function testAGetRetriesA500AndSendsNoKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueError(500, 'Internal error')->enqueueJson(['success' => true]);

        $this->assertSame(['success' => true], $client->webhooks->list());
        $this->assertCount(2, $mock->requests);
        $this->assertSame([null, null], self::keys($mock));
        $this->assertCount(1, $mock->sleeps);
    }

    public function testRateLimitsAreRetriedThenThrown(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        for ($i = 0; $i < 4; $i++) {
            $mock->enqueueError(429, 'Rate limited', retryAfter: '60');
        }

        try {
            $client->webhooks->test('wh-1');
            $this->fail('Expected FlexOpsRateLimitError');
        } catch (FlexOpsRateLimitError $e) {
            $this->assertSame(60, $e->retryAfter);
        }
        $this->assertCount(4, $mock->requests);
        $this->assertCount(1, array_unique(self::keys($mock)));
    }

    public function testA2xxWithNoBodyReturnsNull(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueEmpty(204)->enqueueEmpty(200);

        $this->assertNull($client->rules->delete('rule-1'));
        $this->assertNull($client->webhooks->test('wh-1'));
    }
}
