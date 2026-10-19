<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/** Shipment insurance. Every response is the `{success, data, message, errorCode}` envelope. */
class InsuranceResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function getProviders(): mixed { return $this->http->get($this->wsPath() . '/insurance/providers'); }
    public function getQuote(string $carrierCode, float $declaredValue, ?string $provider = null, ?string $trackingNumber = null): mixed
    {
        return $this->http->post($this->wsPath() . '/insurance/quote', self::withoutNulls(['declaredValue' => $declaredValue, 'carrierCode' => $carrierCode, 'trackingNumber' => $trackingNumber, 'provider' => $provider]));
    }
    /**
     * Buys a policy and charges the premium to the workspace wallet. A shipment that already has a
     * policy in force is refused with 409 ALREADY_EXISTS.
     *
     * Pass an `$idempotencyKey` you store with the purchase to make your own retry safe: after an
     * error or a timeout, retry with the same key and the Gateway returns the first result instead
     * of buying again. Without one, the SDK's own retries still share a key, but a new call gets a new key.
     */
    public function purchase(string $trackingNumber, string $carrierCode, float $declaredValue, ?string $provider = null, ?string $coverageTier = null, ?string $idempotencyKey = null): mixed
    {
        return $this->http->post($this->wsPath() . '/insurance/policies', self::withoutNulls(['trackingNumber' => $trackingNumber, 'carrierCode' => $carrierCode, 'declaredValue' => $declaredValue, 'coverageTier' => $coverageTier, 'provider' => $provider]),
            headers: $idempotencyKey === null ? [] : ["Idempotency-Key: {$idempotencyKey}"]);
    }
    public function void(string $policyId): mixed { return $this->http->delete($this->wsPath() . "/insurance/policies/{$policyId}"); }
    public function fileClaim(string $policyId, string $claimReason, float $claimAmount, ?string $evidenceJson = null): mixed
    {
        return $this->http->post($this->wsPath() . '/insurance/claims', self::withoutNulls(['policyId' => $policyId, 'claimReason' => $claimReason, 'claimAmount' => $claimAmount, 'evidenceJson' => $evidenceJson]));
    }

    private static function withoutNulls(array $body): array { return array_filter($body, fn($v) => $v !== null); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
