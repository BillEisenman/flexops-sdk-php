<?php
namespace FlexOps\Resources;

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

    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
