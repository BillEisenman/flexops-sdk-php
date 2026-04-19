<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-04-01
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOps;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    // ---------------------------------------------------------------
    // 1. API key auth sends X-Api-Key header
    // ---------------------------------------------------------------
    public function testApiKeyAuthSendsXApiKeyHeader(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient(apiKey: 'sk_test_key');
        $mock->enqueueJson(['success' => true, 'data' => []]);

        $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210', 'weight' => 16]);

        $last = $mock->lastRequest();
        $this->assertNotNull($last);
        $this->assertContains('X-Api-Key: sk_test_key', $last['headers']);
    }

    // ---------------------------------------------------------------
    // 2. Bearer token auth sends Authorization header
    // ---------------------------------------------------------------
    public function testBearerTokenAuthSendsAuthorizationHeader(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient(
            apiKey: null,
            accessToken: 'jwt_test_token'
        );
        $mock->enqueueJson(['success' => true, 'data' => []]);

        $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210']);

        $last = $mock->lastRequest();
        $this->assertNotNull($last);
        $this->assertContains('Authorization: Bearer jwt_test_token', $last['headers']);
    }

    // ---------------------------------------------------------------
    // 3. setAccessToken switches from API key to Bearer
    // ---------------------------------------------------------------
    public function testSetAccessTokenSwitchesAuthMode(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient(apiKey: 'sk_test_key');

        $client->setAccessToken('new_jwt_token');
        $mock->enqueueJson(['success' => true, 'data' => []]);

        $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210']);

        $last = $mock->lastRequest();
        $this->assertContains('Authorization: Bearer new_jwt_token', $last['headers']);
        // API key header should not be present after switching
        $hasApiKey = false;
        foreach ($last['headers'] as $header) {
            if (str_starts_with($header, 'X-Api-Key:')) {
                $hasApiKey = true;
            }
        }
        $this->assertFalse($hasApiKey, 'X-Api-Key header should be removed after setAccessToken');
    }

    // ---------------------------------------------------------------
    // 4. setApiKey switches from Bearer to API key
    // ---------------------------------------------------------------
    public function testSetApiKeySwitchesFromBearerToApiKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient(
            apiKey: null,
            accessToken: 'jwt_token'
        );

        $client->setApiKey('sk_new_key');
        $mock->enqueueJson(['success' => true, 'data' => []]);

        $client->shipping->getRates(['fromZip' => '10001', 'toZip' => '90210']);

        $last = $mock->lastRequest();
        $this->assertContains('X-Api-Key: sk_new_key', $last['headers']);
        $hasBearer = false;
        foreach ($last['headers'] as $header) {
            if (str_starts_with($header, 'Authorization:')) {
                $hasBearer = true;
            }
        }
        $this->assertFalse($hasBearer, 'Authorization header should be removed after setApiKey');
    }

    // ---------------------------------------------------------------
    // 5. All 20 resource properties are accessible
    // ---------------------------------------------------------------
    public function testAllResourcePropertiesExist(): void
    {
        ['client' => $client] = TestHelper::createClient();

        $this->assertNotNull($client->auth);
        $this->assertNotNull($client->workspaces);
        $this->assertNotNull($client->shipping);
        $this->assertNotNull($client->carriers);
        $this->assertNotNull($client->webhooks);
        $this->assertNotNull($client->wallet);
        $this->assertNotNull($client->insurance);
        $this->assertNotNull($client->returns);
        $this->assertNotNull($client->apiKeys);
        $this->assertNotNull($client->analytics);
        $this->assertNotNull($client->orders);
        $this->assertNotNull($client->inventory);
        $this->assertNotNull($client->pickups);
        $this->assertNotNull($client->scanForms);
        $this->assertNotNull($client->rules);
        $this->assertNotNull($client->offsets);
        $this->assertNotNull($client->hsCodes);
        $this->assertNotNull($client->recurringShipments);
        $this->assertNotNull($client->emailTemplates);
        $this->assertNotNull($client->reports);
    }
}
