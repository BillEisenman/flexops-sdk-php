<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/** Shipping automation rules. Every response is the `{success, data, message, errorCode}` envelope. */
class RulesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/shipping-rules'); }
    public function get(string $ruleId): mixed { return $this->http->get($this->wsPath() . "/shipping-rules/{$ruleId}"); }
    /**
     * $rule is `['name' => ..., 'priority' => 0, 'isEnabled' => true, 'warehouseId' => ?,
     * 'conditions' => [['field' => ..., 'operator' => ..., 'value' => ..., 'group' => ?]],
     * 'actions' => [['type' => ..., 'value' => ...]]]`; at least one condition and one action.
     *
     * Signed-in users only: API keys get 403 `SIGNED_IN_USER_REQUIRED`, whatever their scopes.
     */
    public function create(array $rule): mixed { return $this->http->post($this->wsPath() . '/shipping-rules', $rule); }
    /**
     * Every field of $rule is optional: `name`, `priority`, `isEnabled`, `conditions`, `actions`.
     *
     * Signed-in users only: API keys get 403 `SIGNED_IN_USER_REQUIRED`, whatever their scopes.
     */
    public function update(string $ruleId, array $rule): mixed { return $this->http->put($this->wsPath() . "/shipping-rules/{$ruleId}", $rule); }
    /** Signed-in users only: API keys get 403 `SIGNED_IN_USER_REQUIRED`, whatever their scopes. */
    public function delete(string $ruleId): mixed { return $this->http->delete($this->wsPath() . "/shipping-rules/{$ruleId}"); }
    /**
     * $rules is a list of `['ruleId' => ..., 'priority' => 0]`.
     *
     * Signed-in users only: API keys get 403 `SIGNED_IN_USER_REQUIRED`, whatever their scopes.
     */
    public function reorder(array $rules): mixed { return $this->http->post($this->wsPath() . '/shipping-rules/reorder', ['rules' => array_values($rules)]); }
    /**
     * Dry-runs the workspace's rules against a sample shipment. $shipment is the Gateway
     * TestShippingRuleRequest: `weightOz`, `declaredValue`, `destinationCountry` (default US),
     * `destinationState`, `destinationPostalCode`, `orderSource`, `itemCount`, `shipMethod`,
     * `destinationZone` and `tags`.
     *
     * Changes nothing. API keys need `shipping:read`; the public demo keys are refused.
     */
    public function test(array $shipment): mixed { return $this->http->post($this->wsPath() . '/shipping-rules/test', $shipment ?: new \stdClass()); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
