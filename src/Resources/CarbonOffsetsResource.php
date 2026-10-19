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
    /** Buys an offset for a shipment and debits the workspace wallet (201). */
    public function purchase(string $trackingNumber, float $weightLbs, float $distanceMiles): mixed
    {
        return $this->http->post($this->wsPath() . '/carbon-offsets/purchase', ['trackingNumber' => $trackingNumber, 'weightLbs' => $weightLbs, 'distanceMiles' => $distanceMiles]);
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
