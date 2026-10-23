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
    /** Statuses a GET retries on. */
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH'];
    /**
     * Statuses a keyed write retries on: the Gateway either never ran it or replays the stored
     * response for the same Idempotency-Key. Never 500, whose response the Gateway does not
     * cache, so a retry would run the write (and charge the wallet) again.
     */
    private const WRITE_RETRYABLE_STATUSES = [429, 502, 503, 504];
    /** The Gateway's 409 while the first request with the same Idempotency-Key is still running. */
    private const IDEMPOTENCY_CONFLICT = 'IDEMPOTENCY_CONFLICT';

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

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function get(string $path, ?array $query = null, array $headers = []): mixed
    {
        return $this->request('GET', $path, query: $query, headers: $headers);
    }

    public function post(string $path, mixed $body = null, ?array $query = null, array $headers = []): mixed
    {
        return $this->request('POST', $path, body: $body, query: $query, headers: $headers);
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

    /**
     * Sends a request, retrying only where that is safe.
     *
     * Every POST, PUT and PATCH carries an Idempotency-Key: the caller's (an `Idempotency-Key: ...`
     * entry in $headers), else one UUID generated here and reused on every retry of this call, so
     * the Gateway replays a write it already finished instead of running it again.
     * A GET retries on a network error, 429, 500, 502, 503 and 504; a write on a network error,
     * 429, 502, 503, 504 and 409 IDEMPOTENCY_CONFLICT; a DELETE, which the Gateway does not
     * dedupe, only on 429.
     *
     * A 2xx with no body (204, or an empty body) returns null.
     */
    private function request(string $method, string $path, mixed $body = null, ?array $query = null, array $headers = []): mixed
    {
        foreach ($headers as $header) {
            if (preg_match('/[\r\n]/', (string) $header)) {
                throw new \InvalidArgumentException('Invalid header value');
            }
        }
        if (in_array($method, self::WRITE_METHODS, true) && !self::hasHeader($headers, 'Idempotency-Key')) {
            $headers[] = 'Idempotency-Key: ' . self::uuid4();
        }

        $url = $this->buildUrl($path, $query);
        $lastError = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                $this->sleep($this->calculateBackoff($attempt));
            }

            $result = $this->executeRequest($method, $url, array_merge($this->buildHeaders(), $headers), $body);

            if ($result['error'] !== null) {
                $lastError = new FlexOpsError($result['error'], 0, 'NETWORK_ERROR');
                // The Gateway may have run a timed-out or dropped request. A DELETE is not
                // covered by its idempotency middleware, so it is not sent again.
                if ($method === 'DELETE') throw $lastError;
                continue;
            }

            $statusCode = $result['statusCode'];
            $responseBody = $result['body'];
            $responseHeaders = $result['headers'];

            if ($statusCode >= 200 && $statusCode < 300) {
                if ($statusCode === 204 || $responseBody === '') {
                    return null;
                }
                $contentType = $this->getHeader($responseHeaders, 'content-type');
                if (str_contains($contentType, 'application/json')) {
                    return json_decode($responseBody, true);
                }
                return $responseBody;
            }

            if ($statusCode === 401) {
                throw new FlexOpsAuthError();
            }

            // Rate limited: the request was refused before it ran, so any method may retry.
            if ($statusCode === 429) {
                $retryAfter = (int)$this->getHeader($responseHeaders, 'retry-after');
                $lastError = new FlexOpsRateLimitError($retryAfter);
                continue;
            }

            $errorBody = json_decode($responseBody, true);
            $errorBody = is_array($errorBody) ? $errorBody : [];
            $code = $errorBody['errorCode'] ?? $errorBody['code'] ?? null;
            $error = new FlexOpsError(
                $errorBody['message'] ?? "HTTP {$statusCode}",
                $statusCode,
                $code ?? ($statusCode === 403 ? 'FORBIDDEN' : null),
                $errorBody['errors'] ?? null,
                $errorBody['data'] ?? null
            );

            if ($this->isRetryable($method, $statusCode, $code)) {
                $lastError = $error;
                continue;
            }

            throw $error;
        }

        throw $lastError ?? new FlexOpsError('Request failed after retries', 0, 'RETRY_EXHAUSTED');
    }

    private function isRetryable(string $method, int $statusCode, ?string $code): bool
    {
        if ($method === 'GET') {
            return in_array($statusCode, self::RETRYABLE_STATUSES, true);
        }
        if (!in_array($method, self::WRITE_METHODS, true)) {
            return false;
        }
        if ($statusCode === 409) {
            return $code === self::IDEMPOTENCY_CONFLICT;
        }
        return in_array($statusCode, self::WRITE_RETRYABLE_STATUSES, true);
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

    /** Waits before a retry. Override in tests to skip the delay. */
    protected function sleep(float $seconds): void
    {
        usleep((int)($seconds * 1_000_000));
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

    private static function hasHeader(array $headers, string $name): bool
    {
        foreach ($headers as $header) {
            if (stripos((string) $header, $name . ':') === 0) {
                return true;
            }
        }
        return false;
    }

    /** A random (version 4) UUID from random_bytes(). */
    private static function uuid4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
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
