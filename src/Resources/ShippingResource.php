<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ShippingResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function getRates(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/rates', $request); }
    public function getCheapestRate(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/rates/cheapest', $request); }
    public function getFastestRate(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/rates/fastest', $request); }
    public function createLabel(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/labels', $request); }
    public function cancelLabel(string $labelId): mixed { return $this->http->delete($this->wsPath() . "/shipping/labels/{$labelId}"); }
    public function track(string $trackingNumber): mixed { return $this->http->get($this->wsPath() . "/shipping/track/{$trackingNumber}"); }
    public function validateAddress(array $address): mixed { return $this->http->post($this->wsPath() . '/shipping/addresses/validate', $address); }
    public function createBatch(array $request): mixed { return $this->http->post($this->wsPath() . '/labels/batch', $request); }
    public function previewBatch(array $request): mixed { return $this->http->post($this->wsPath() . '/labels/batch/preview', $request); }
    public function getBatchStatus(string $jobId): mixed { return $this->http->get($this->wsPath() . "/labels/batch/{$jobId}"); }
    public function downloadBatchLabel(string $jobId, string $itemId): mixed { return $this->http->get($this->wsPath() . "/labels/batch/{$jobId}/items/{$itemId}/label"); }
    public function getCarriers(): mixed { return $this->http->get($this->wsPath() . '/shipping/carriers'); }

    public function getRecommendations(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/recommendations', $request); }
    public function predictDelivery(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/predictions/delivery', $request); }
    public function getSavings(): mixed { return $this->http->get($this->wsPath() . '/shipping/savings'); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
