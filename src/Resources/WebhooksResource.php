<?php
namespace FlexOps\Resources;

use FlexOps\FlexOpsWebhookSignatureError;
use FlexOps\HttpClient;

class WebhooksResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/webhooks'); }
    public function get(string $webhookId): mixed { return $this->http->get($this->wsPath() . "/webhooks/{$webhookId}"); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/webhooks', $request); }
    public function update(string $webhookId, array $data): mixed { return $this->http->put($this->wsPath() . "/webhooks/{$webhookId}", $data); }
    public function delete(string $webhookId): mixed { return $this->http->delete($this->wsPath() . "/webhooks/{$webhookId}"); }
    public function rotateSecret(string $webhookId): mixed { return $this->http->post($this->wsPath() . "/webhooks/{$webhookId}/rotate-secret"); }
    public function listDeliveryLogs(string $webhookId): mixed { return $this->http->get($this->wsPath() . "/webhooks/{$webhookId}/deliveries"); }

    /** Headers FlexOps sends with every webhook delivery. */
    public const EVENT_HEADER = 'X-FlexOps-Event';         // event name; "test" for a test event
    public const EVENT_ID_HEADER = 'X-FlexOps-Event-Id';
    public const TIMESTAMP_HEADER = 'X-FlexOps-Timestamp'; // Unix seconds, part of the signed string
    public const SIGNATURE_HEADER = 'X-FlexOps-Signature'; // "sha256=" + lowercase hex HMAC-SHA256
    public const DELIVERY_HEADER = 'X-FlexOps-Delivery';

    /** Seconds the X-FlexOps-Timestamp may be from now, either way. */
    public const DEFAULT_TOLERANCE = 300;

    /**
     * Returns true when a FlexOps webhook delivery is authentic and fresh, false otherwise.
     * See verifySignatureOrFail() for the arguments.
     */
    public static function verifySignature(
        string $payload,
        ?string $signature,
        ?string $timestamp,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): bool {
        try {
            self::verifySignatureOrFail($payload, $signature, $timestamp, $secret, $tolerance, $now);
            return true;
        } catch (FlexOpsWebhookSignatureError) {
            return false;
        }
    }

    /**
     * Verifies a FlexOps webhook delivery, or throws FlexOpsWebhookSignatureError saying why.
     *
     * FlexOps signs "{timestamp}.{body}" with HMAC-SHA256; the comparison uses hash_equals().
     *
     * @param string      $payload   The raw request body exactly as received (verify before decoding it).
     * @param string|null $signature The X-FlexOps-Signature value, "sha256=<hex>" (bare hex is accepted).
     * @param string|null $timestamp The X-FlexOps-Timestamp value, Unix seconds.
     * @param string      $secret    The subscription secret ("whsec_..."), used as-is.
     * @param int         $tolerance Seconds the timestamp may be from $now; non-positive keeps the default.
     * @param int|null    $now       The current Unix time, for tests; defaults to time().
     *
     * @throws FlexOpsWebhookSignatureError
     */
    public static function verifySignatureOrFail(
        string $payload,
        ?string $signature,
        ?string $timestamp,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): void {
        if ($secret === '') {
            throw new FlexOpsWebhookSignatureError('Webhook secret is empty', 'WEBHOOK_MISSING_SECRET');
        }
        if ($signature === null || $signature === '') {
            throw new FlexOpsWebhookSignatureError('Webhook signature header is missing', 'WEBHOOK_MISSING_SIGNATURE');
        }
        if ($timestamp === null || preg_match('/\A\d+\z/', $timestamp) !== 1) {
            throw new FlexOpsWebhookSignatureError('Webhook timestamp header is missing or not Unix seconds', 'WEBHOOK_INVALID_TIMESTAMP');
        }

        if ($tolerance <= 0) {
            $tolerance = self::DEFAULT_TOLERANCE;
        }
        if (abs(($now ?? time()) - (int) $timestamp) > $tolerance) {
            throw new FlexOpsWebhookSignatureError('Webhook timestamp is outside the tolerance', 'WEBHOOK_TIMESTAMP_OUTSIDE_TOLERANCE');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $received = strtolower(str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature);
        if (!hash_equals($expected, $received)) {
            throw new FlexOpsWebhookSignatureError('Webhook signature does not match', 'WEBHOOK_SIGNATURE_MISMATCH');
        }
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
