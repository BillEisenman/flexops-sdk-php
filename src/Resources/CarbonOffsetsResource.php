<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/** Carbon offsets. Every response is the `{success, data, message, errorCode}` envelope. */
class CarbonOffsetsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function quote(float $weightLbs, float $distanceMiles): mixed
    {
        return $this->http->post($this->wsPath() . '/carbon-offsets/quote', ['weightLbs' => $weightLbs, 'distanceMiles' => $distanceMiles]);
    }
    /**
     * Buys an offset for a shipment and debits the workspace wallet (201).
     *
     * Pass an `$idempotencyKey` you store with the purchase to make your own retry safe: after an
     * error or a timeout, retry with the same key and the Gateway returns the first result instead
     * of buying again. Without one, the SDK's own retries still share a key, but a new call gets a new key.
     */
    public function purchase(string $trackingNumber, float $weightLbs, float $distanceMiles, ?string $idempotencyKey = null): mixed
    {
        return $this->http->post($this->wsPath() . '/carbon-offsets/purchase', ['trackingNumber' => $trackingNumber, 'weightLbs' => $weightLbs, 'distanceMiles' => $distanceMiles],
            headers: $idempotencyKey === null ? [] : ["Idempotency-Key: {$idempotencyKey}"]);
    }
    public function list(?int $page = null, ?int $pageSize = null): mixed
    {
        return $this->http->get($this->wsPath() . '/carbon-offsets', array_filter(['page' => $page, 'pageSize' => $pageSize], fn($v) => $v !== null) ?: null);
    }
    public function get(string $offsetId): mixed { return $this->http->get($this->wsPath() . '/carbon-offsets/' . rawurlencode($offsetId)); }
    public function summary(): mixed { return $this->http->get($this->wsPath() . '/carbon-offsets/summary'); }
    public function getSettings(): mixed { return $this->http->get($this->wsPath() . '/carbon-offsets/settings'); }
    public function updateSettings(bool $autoOffsetEnabled): mixed
    {
        return $this->http->put($this->wsPath() . '/carbon-offsets/settings', ['autoOffsetEnabled' => $autoOffsetEnabled]);
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
