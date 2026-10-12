<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ShippingResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function getRates(array $request): mixed { return $this->http->post('/api/shipping/rates', $request); }
    public function getCheapestRate(array $request): mixed { return $this->http->post('/api/shipping/rates/cheapest', $request); }
    public function getFastestRate(array $request): mixed { return $this->http->post('/api/shipping/rates/fastest', $request); }
    /**
     * Returns a raw preview or label. Confirm explicitly and reuse the purchase key on retries.
     * A label without an `orderId` must declare `hazmatClassification` (a HazmatClassification;
     * `HazmatClassification::None` for an ordinary parcel). The SDK never fills it in.
     */
    public function createLabel(array $request, ?string $idempotencyKey = null): mixed
    {
        if ($idempotencyKey !== null && preg_match('/[\r\n]/', $idempotencyKey)) {
            throw new \InvalidArgumentException('Invalid idempotency key');
        }
        return $this->http->post('/api/shipping/labels', $request,
            headers: $idempotencyKey === null ? [] : ["Idempotency-Key: {$idempotencyKey}"]);
    }
    public function cancelLabel(string $labelId): mixed { return $this->http->delete($this->wsPath() . "/shipping/labels/{$labelId}"); }
    public function track(string $trackingNumber): mixed { return $this->http->get($this->wsPath() . "/shipping/track/{$trackingNumber}"); }
    /**
     * Sends the Gateway AddressValidationRequest (`['address' => [...]]`). A legacy
     * `street1`/`state`/`zip` address is mapped to the Gateway field names.
     */
    public function validateAddress(array $address): mixed
    {
        return $this->http->post('/api/shipping/addresses/validate', ['address' => self::toShippingAddress($address)]);
    }
    /** The payload is sent as given. Set `hazmatClassification` on an item only when you have a declaration. */
    public function createBatch(array $request): mixed { return $this->http->post($this->wsPath() . '/labels/batch', $request); }
    public function previewBatch(array $request): mixed { return $this->http->post($this->wsPath() . '/labels/batch/preview', $request); }
    public function getBatchStatus(string $jobId): mixed { return $this->http->get($this->wsPath() . "/labels/batch/{$jobId}"); }
    public function downloadBatchLabel(string $jobId, string $itemId): mixed { return $this->http->get($this->wsPath() . "/labels/batch/{$jobId}/items/{$itemId}/label"); }
    public function getCarriers(): mixed { return $this->http->get($this->wsPath() . '/shipping/carriers'); }

    public function getRecommendations(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/recommendations', $request); }
    public function predictDelivery(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/predictions/delivery', $request); }
    public function getSavings(): mixed { return $this->http->get($this->wsPath() . '/shipping/savings'); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }

    private const LEGACY_ADDRESS_FIELDS = [
        'street1' => 'addressLine1',
        'street2' => 'addressLine2',
        'state' => 'stateProvince',
        'zip' => 'postalCode',
        'country' => 'countryCode',
    ];

    /** Maps an address to the Gateway ShippingAddress; canonical input passes through. */
    private static function toShippingAddress(array $address): array
    {
        if (!array_key_exists('street1', $address)) {
            return $address;
        }
        $mapped = [];
        foreach ($address as $key => $value) {
            if ($value !== null) {
                $mapped[self::LEGACY_ADDRESS_FIELDS[$key] ?? $key] = $value;
            }
        }
        return $mapped;
    }
}
