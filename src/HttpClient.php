<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-03-08
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

class HttpClient
{
    private const DEFAULT_BASE_URL = 'https://gateway.flexops.io';
    private const MAX_RETRIES = 3;
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    private string $baseUrl;
    private int $timeout;
    private ?string $apiKey;
    private ?string $accessToken;

    public function __construct(
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $apiKey = null,
        ?string $accessToken = null,
        int $timeout = 30
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->accessToken = $accessToken;
        $this->timeout = $timeout;
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
        $this->apiKey = null;
    }

    public function setApiKey(string $key): void
    {
        $this->apiKey = $key;
        $this->accessToken = null;
    }

    public function get(string $path, ?array $query = null): mixed
    {
        return $this->request('GET', $path, query: $query);
    }

    public function post(string $path, mixed $body = null, ?array $query = null): mixed
    {
        return $this->request('POST', $path, body: $body, query: $query);
    }

    public function put(string $path, mixed $body = null): mixed
    {
        return $this->request('PUT', $path, body: $body);
    }

    public function patch(string $path, mixed $body = null): mixed
    {
        return $this->request('PATCH', $path, body: $body);
    }

    public function delete(string $path): mixed
    {
        return $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, mixed $body = null, ?array $query = null): mixed
    {
        $url = $this->buildUrl($path, $query);
        $lastError = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                usleep((int)($this->calculateBackoff($attempt) * 1_000_000));
            }

            $result = $this->executeRequest($method, $url, $this->buildHeaders(), $body);

            if ($result['error'] !== null) {
                $lastError = new FlexOpsError($result['error'], 0, 'NETWORK_ERROR');
                continue;
            }

            $statusCode = $result['statusCode'];
            $responseBody = $result['body'];
            $responseHeaders = $result['headers'];

            if ($statusCode >= 200 && $statusCode < 300) {
                $contentType = $this->getHeader($responseHeaders, 'content-type');
                if (str_contains($contentType, 'application/json')) {
                    return json_decode($responseBody, true);
                }
                return $responseBody;
            }

            if ($statusCode === 401) {
                throw new FlexOpsAuthError();
            }

            if ($statusCode === 403) {
                throw new FlexOpsError('Access denied. Check your plan tier and feature entitlements.', 403, 'FORBIDDEN');
            }

            if ($statusCode === 429) {
                $retryAfter = (int)$this->getHeader($responseHeaders, 'retry-after');
                $lastError = new FlexOpsRateLimitError($retryAfter);
                if (in_array(429, self::RETRYABLE_STATUSES)) continue;
                throw $lastError;
            }

            $errorBody = json_decode($responseBody, true) ?? [];
            $error = new FlexOpsError(
                $errorBody['message'] ?? "HTTP {$statusCode}",
                $statusCode,
                null,
                $errorBody['errors'] ?? null
            );

            if (in_array($statusCode, self::RETRYABLE_STATUSES)) {
                $lastError = $error;
                continue;
            }

            throw $error;
        }

        throw $lastError ?? new FlexOpsError('Request failed after retries', 0, 'RETRY_EXHAUSTED');
    }

    /**
     * Execute a raw HTTP request. Override in tests to avoid cURL.
     *
     * @return array{statusCode: int, headers: string, body: string, error: ?string}
     */
    protected function executeRequest(string $method, string $url, array $headers, mixed $body): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['statusCode' => 0, 'headers' => '', 'body' => '', 'error' => $error];
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseBody = substr($response, $headerSize);
        $responseHeaders = substr($response, 0, $headerSize);
        curl_close($ch);

        return ['statusCode' => $statusCode, 'headers' => $responseHeaders, 'body' => $responseBody, 'error' => null];
    }

    private function buildUrl(string $path, ?array $query): string
    {
        $url = $this->baseUrl . (str_starts_with($path, '/') ? $path : "/{$path}");
        if ($query) {
            $filtered = array_filter($query, fn($v) => $v !== null);
            if (!empty($filtered)) {
                $url .= '?' . http_build_query($filtered);
            }
        }
        return $url;
    }

    private function buildHeaders(): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->apiKey) {
            $headers[] = "X-Api-Key: {$this->apiKey}";
        } elseif ($this->accessToken) {
            $headers[] = "Authorization: Bearer {$this->accessToken}";
        }

        return $headers;
    }

    private function getHeader(string $headers, string $name): string
    {
        if (preg_match("/^{$name}:\s*(.+)$/mi", $headers, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    private function calculateBackoff(int $attempt): float
    {
        $jitter = 0.85 + (mt_rand() / mt_getrandmax()) * 0.3;
        return min(1.0 * pow(2, $attempt - 1) * $jitter, 30.0);
    }
}
