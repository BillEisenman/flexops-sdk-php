<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class InsuranceResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function getProviders(): mixed { return $this->http->get($this->wsPath() . '/insurance/providers'); }
    public function getQuote(string $carrier, float $declaredValue, ?string $provider = null): mixed
    {
        return $this->http->post($this->wsPath() . '/insurance/quote', array_filter(['carrier' => $carrier, 'declaredValue' => $declaredValue, 'provider' => $provider]));
    }
    public function purchase(string $trackingNumber, string $carrier, float $declaredValue, ?string $provider = null): mixed
    {
        return $this->http->post($this->wsPath() . '/insurance/purchase', array_filter(['trackingNumber' => $trackingNumber, 'carrier' => $carrier, 'declaredValue' => $declaredValue, 'provider' => $provider]));
    }
    public function void(string $policyId): mixed { return $this->http->delete($this->wsPath() . "/insurance/policies/{$policyId}"); }
    public function fileClaim(string $policyId, string $description, float $claimAmount): mixed
    {
        return $this->http->post($this->wsPath() . "/insurance/policies/{$policyId}/claims", ['description' => $description, 'claimAmount' => $claimAmount]);
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
