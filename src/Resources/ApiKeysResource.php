<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ApiKeysResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/api-keys'); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/api-keys', $request); }
    public function revoke(string $keyId): mixed { return $this->http->delete($this->wsPath() . "/api-keys/{$keyId}"); }
    public function rotate(string $keyId): mixed { return $this->http->post($this->wsPath() . "/api-keys/{$keyId}/rotate"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
