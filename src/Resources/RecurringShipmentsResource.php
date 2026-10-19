<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * Recurring shipments: a saved preset (package and service) shipped to a saved address on a
 * schedule. Responses are bare JSON (no envelope); delete, pause, resume and trigger answer 204
 * and return null.
 *
 * Reads work with any API key or a user token. Writes need a user token (`auth->login()`) or an
 * API key granted the `recurring-shipments:write` scope; other keys get 403
 * `API_KEY_SCOPE_REQUIRED` with `requiredScopes`.
 */
class RecurringShipmentsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath()); }
    public function get(string $id): mixed { return $this->http->get($this->wsPath() . '/' . rawurlencode($id)); }
    /**
     * Creates a schedule (201). $request is `['name' => ..., 'interval' => RecurrenceInterval::Weekly,
     * 'firstRunUtc' => '2026-11-02T15:00:00Z', 'presetId' => ?, 'addressId' => ?, 'cronExpression' => ?,
     * 'maxFailures' => ?]`. A run needs `addressId`; `cronExpression` is for `RecurrenceInterval::Custom`.
     */
    public function create(array $request): mixed { return $this->http->post($this->wsPath(), $request); }
    /** Every field is optional: `name`, `presetId`, `addressId`, `interval`, `cronExpression`, `maxFailures`. */
    public function update(string $id, array $request): mixed { return $this->http->put($this->wsPath() . '/' . rawurlencode($id), $request ?: new \stdClass()); }
    public function delete(string $id): mixed { return $this->http->delete($this->wsPath() . '/' . rawurlencode($id)); }
    public function pause(string $id): mixed { return $this->http->post($this->wsPath() . '/' . rawurlencode($id) . '/pause'); }
    public function resume(string $id): mixed { return $this->http->post($this->wsPath() . '/' . rawurlencode($id) . '/resume'); }
    /**
     * Runs the schedule now, which creates an order. The SDK sends one Idempotency-Key on every retry
     * of this call; pass your own $idempotencyKey (and reuse it) to make a trigger you repeat
     * yourself safe as well.
     */
    public function trigger(string $id, ?string $idempotencyKey = null): mixed
    {
        return $this->http->post($this->wsPath() . '/' . rawurlencode($id) . '/trigger',
            headers: $idempotencyKey === null ? [] : ["Idempotency-Key: {$idempotencyKey}"]);
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)() . '/recurring-shipments'; }
}
