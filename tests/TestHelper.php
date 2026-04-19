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

class TestHelper
{
    /**
     * Create a FlexOps client backed by a MockHttpClient.
     *
     * @return array{client: FlexOps, mock: MockHttpClient}
     */
    public static function createClient(
        ?string $apiKey = 'sk_test_key',
        ?string $accessToken = null,
        string $workspaceId = 'ws-test-123'
    ): array {
        $mock = new MockHttpClient(
            baseUrl: 'http://localhost',
            apiKey: $apiKey,
            accessToken: $accessToken,
        );

        $client = new FlexOps(
            config: ['workspaceId' => $workspaceId],
            httpClient: $mock,
        );

        return ['client' => $client, 'mock' => $mock];
    }
}
