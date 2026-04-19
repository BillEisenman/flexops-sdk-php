<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class WalletResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function getBalance(): mixed { return $this->http->get($this->wsPath() . '/wallet/balance'); }
    public function addFunds(float $amount): mixed { return $this->http->post($this->wsPath() . '/wallet/add-funds', ['amount' => $amount]); }
    public function listTransactions(?int $page = null, ?int $pageSize = null, ?string $startDate = null, ?string $endDate = null): mixed
    {
        return $this->http->get($this->wsPath() . '/wallet/transactions', array_filter(['page' => $page, 'pageSize' => $pageSize, 'startDate' => $startDate, 'endDate' => $endDate]) ?: null);
    }
    public function configureAutoReload(bool $enabled, ?float $threshold = null, ?float $amount = null): mixed
    {
        return $this->http->put($this->wsPath() . '/wallet/auto-reload', array_filter(['enabled' => $enabled, 'threshold' => $threshold, 'amount' => $amount], fn($v) => $v !== null));
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
