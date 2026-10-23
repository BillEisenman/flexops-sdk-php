<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-03-08
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

class FlexOpsError extends \Exception
{
    public readonly int $statusCode;
    public readonly ?string $errorCode;
    public readonly ?array $errors;
    /**
     * The envelope's `data` on an error reply, when the Gateway sends one. A multi-package 422
     * `AllPackagesFailed` or 409 `OutcomeUnknown` carries the per-box results here.
     */
    public readonly mixed $data;

    public function __construct(string $message, int $statusCode = 0, ?string $errorCode = null, ?array $errors = null, mixed $data = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
        $this->data = $data;
    }
}

class FlexOpsAuthError extends FlexOpsError
{
    public function __construct(string $message = 'Authentication required. Check your access token or API key.')
    {
        parent::__construct($message, 401, 'UNAUTHORIZED');
    }
}

/**
 * Thrown by WebhooksResource::verifySignatureOrFail() when a webhook delivery is not
 * authentic or not fresh. $errorCode says why, for example "WEBHOOK_SIGNATURE_MISMATCH"
 * or "WEBHOOK_TIMESTAMP_OUTSIDE_TOLERANCE".
 */
class FlexOpsWebhookSignatureError extends FlexOpsError
{
    public function __construct(string $message, string $errorCode)
    {
        parent::__construct($message, 0, $errorCode);
    }
}

class FlexOpsRateLimitError extends FlexOpsError
{
    public readonly int $retryAfter;

    public function __construct(int $retryAfter = 0)
    {
        parent::__construct("Rate limited. Retry after {$retryAfter}s", 429, 'RATE_LIMITED');
        $this->retryAfter = $retryAfter;
    }
}
