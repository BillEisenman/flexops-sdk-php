<?php
namespace FlexOps\Resources;

use FlexOps\FlexOpsWebhookSignatureError;
use FlexOps\HttpClient;
use FlexOps\WebhookEventType;

class WebhooksResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/webhooks'); }
    public function get(string $webhookId): mixed { return $this->http->get($this->wsPath() . "/webhooks/{$webhookId}"); }

    /**
     * Creates a webhook subscription.
     *
     * $request takes `url`, `description` and `events`, a list of WebhookEventType cases (or
     * their exact names) that is sent as the Gateway's comma-separated `eventTypes`. Leave
     * `events` out to receive every event.
     *
     * An explicit `eventTypes` string is sent as-is and is not checked.
     *
     * Returns `['success' => true, 'data' => $subscription]`, like the other webhook calls.
     * `$result['data']['secret']` is the `whsec_...` signing secret, returned only here; store it
     * to verify deliveries. The Gateway answers this route with the bare subscription (201, no
     * envelope), so the SDK wraps it, and also keeps the subscription fields (`id`, `secret`, ...)
     * at the top level, where earlier releases returned them. A body that is already wrapped is
     * returned unchanged.
     *
     * @param array{url: string, description?: string, events?: list<WebhookEventType|string>, eventTypes?: string} $request
     *
     * @throws \InvalidArgumentException `events` is empty, is not a list, names an event FlexOps never
     *                                   sends (such as `label.created`), or is given with `eventTypes`.
     *                                   No request is sent.
     */
    public function create(array $request): mixed
    {
        $body = $this->http->post($this->wsPath() . '/webhooks', self::toWireRequest($request));
        if (!is_array($body) || array_key_exists('success', $body) || array_key_exists('data', $body)) {
            return $body;
        }
        return ['success' => true, 'data' => $body] + $body;
    }

    /**
     * Updates a webhook subscription.
     *
     * $data takes `url`, `description`, `status` (`Active`, `Paused` or `Disabled`) and `events`,
     * a list of WebhookEventType cases (or their exact names) that replaces the subscribed events
     * and is sent as `eventTypes`. Leave `events` out to keep the current events.
     *
     * @param array{url?: string, description?: string, status?: string, events?: list<WebhookEventType|string>, eventTypes?: string} $data
     *
     * @throws \InvalidArgumentException As for create(). No request is sent.
     */
    public function update(string $webhookId, array $data): mixed { return $this->http->put($this->wsPath() . "/webhooks/{$webhookId}", self::toWireRequest($data)); }

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

    /**
     * The Gateway reads a comma-separated `eventTypes` string, ignores an `events` field (so the
     * subscription receives every event), and matches each name exactly. A name it never sends
     * would leave the subscription silent, so it is refused here instead.
     */
    private static function toWireRequest(array $request): array
    {
        if (!array_key_exists('events', $request)) {
            return $request;
        }
        if (array_key_exists('eventTypes', $request)) {
            throw new \InvalidArgumentException('Pass events or eventTypes, not both.');
        }

        $events = $request['events'];
        if (!is_array($events) || !array_is_list($events)) {
            throw new \InvalidArgumentException('events must be a list of WebhookEventType cases or event names.');
        }
        if ($events === []) {
            throw new \InvalidArgumentException('events must name at least one event; leave it out to receive every event.');
        }

        $names = [];
        $unknown = [];
        foreach ($events as $event) {
            $name = $event instanceof WebhookEventType ? $event->value : $event;
            if (!is_string($name) || WebhookEventType::tryFrom($name) === null) {
                $unknown[] = is_string($name) ? $name : get_debug_type($name);
            } elseif (!in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        if ($unknown !== []) {
            $known = implode(', ', array_map(fn (WebhookEventType $e) => $e->value, WebhookEventType::cases()));
            throw new \InvalidArgumentException('Unknown webhook event: ' . implode(', ', $unknown) . ". FlexOps sends {$known}.");
        }

        $wire = [];
        foreach ($request as $key => $value) {
            if ($key === 'events') {
                $wire['eventTypes'] = implode(',', $names);
            } else {
                $wire[$key] = $value;
            }
        }
        return $wire;
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
