<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-04-01
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\HttpClient;

/**
 * Test double that intercepts HTTP requests instead of hitting the network.
 * Returns pre-queued responses and records each request for assertions.
 */
class MockHttpClient extends HttpClient
{
    /** @var list<array{statusCode: int, headers: string, body: string, error: ?string}> */
    private array $queue = [];

    /** @var list<array{method: string, url: string, headers: array, body: mixed}> */
    public array $requests = [];

    public function __construct(
        string $baseUrl = 'http://localhost',
        ?string $apiKey = null,
        ?string $accessToken = null,
        int $timeout = 1
    ) {
        parent::__construct($baseUrl, $apiKey, $accessToken, $timeout);
    }

    /**
     * Enqueue a successful JSON response.
     */
    public function enqueueJson(mixed $data, int $statusCode = 200): self
    {
        $this->queue[] = [
            'statusCode' => $statusCode,
            'headers' => "HTTP/1.1 {$statusCode} OK\r\ncontent-type: application/json\r\n\r\n",
            'body' => json_encode($data),
            'error' => null,
        ];
        return $this;
    }

    /**
     * Enqueue an error response (non-JSON body with optional error message).
     */
    public function enqueueError(int $statusCode, ?string $message = null, ?array $errors = null, ?string $retryAfter = null): self
    {
        $body = json_encode(array_filter([
            'message' => $message,
            'errors' => $errors,
        ], fn($v) => $v !== null));

        $headerLines = "HTTP/1.1 {$statusCode} Error\r\ncontent-type: application/json\r\n";
        if ($retryAfter !== null) {
            $headerLines .= "retry-after: {$retryAfter}\r\n";
        }
        $headerLines .= "\r\n";

        $this->queue[] = [
            'statusCode' => $statusCode,
            'headers' => $headerLines,
            'body' => $body,
            'error' => null,
        ];
        return $this;
    }

    /**
     * Enqueue a network-level failure.
     */
    public function enqueueNetworkError(string $message = 'Connection refused'): self
    {
        $this->queue[] = [
            'statusCode' => 0,
            'headers' => '',
            'body' => '',
            'error' => $message,
        ];
        return $this;
    }

    /**
     * Get the last recorded request.
     */
    public function lastRequest(): ?array
    {
        return $this->requests ? $this->requests[array_key_last($this->requests)] : null;
    }

    /**
     * Override the cURL-based execution with queue-based responses.
     */
    protected function executeRequest(string $method, string $url, array $headers, mixed $body): array
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        ];

        if (empty($this->queue)) {
            return [
                'statusCode' => 200,
                'headers' => "HTTP/1.1 200 OK\r\ncontent-type: application/json\r\n\r\n",
                'body' => '{"success":true}',
                'error' => null,
            ];
        }

        return array_shift($this->queue);
    }
}
