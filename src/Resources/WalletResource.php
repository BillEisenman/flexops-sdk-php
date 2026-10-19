<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class WalletResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    /**
     * The workspace wallet balance: a bare `['hasWallet' => bool, 'balanceCents' => int, 'balance' => float]`
     * (no `success`/`data` envelope). Sends the configured workspace as `X-Workspace-Id`.
     */
    public function getBalance(): mixed
    {
        $workspaceId = ($this->getWsId)();
        return $this->http->get('/api/wallet/my-balance', headers: $workspaceId === null ? [] : ["X-Workspace-Id: {$workspaceId}"]);
    }
}
