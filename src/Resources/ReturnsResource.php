<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ReturnsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(?int $page = null, ?int $pageSize = null, ?string $status = null): mixed
    {
        return $this->http->get($this->wsPath() . '/returns', array_filter(['page' => $page, 'pageSize' => $pageSize, 'status' => $status]) ?: null);
    }
    public function get(string $returnId): mixed { return $this->http->get($this->wsPath() . "/returns/{$returnId}"); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/returns', $request); }
    public function approve(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/approve"); }
    public function reject(string $returnId, string $reason): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/reject", ['reason' => $reason]); }
    public function cancel(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/cancel"); }
    public function generateLabel(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/label"); }
    public function markReceived(string $returnId, array $items): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/receive", ['items' => $items]); }
    public function processRefund(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/refund"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
