<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class RulesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/shipping-rules'); }
    public function get(string $ruleId): mixed { return $this->http->get($this->wsPath() . "/shipping-rules/{$ruleId}"); }
    public function create(array $rule): mixed { return $this->http->post($this->wsPath() . '/shipping-rules', $rule); }
    public function update(string $ruleId, array $rule): mixed { return $this->http->put($this->wsPath() . "/shipping-rules/{$ruleId}", $rule); }
    public function delete(string $ruleId): mixed { return $this->http->delete($this->wsPath() . "/shipping-rules/{$ruleId}"); }
    public function reorder(array $ruleIds): mixed { return $this->http->put($this->wsPath() . '/shipping-rules/reorder', $ruleIds); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
