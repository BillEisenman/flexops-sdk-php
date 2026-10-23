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
     * Returns a raw preview or label. Confirm explicitly. The SDK sends one Idempotency-Key on
     * every retry of this call; pass your own $idempotencyKey (and reuse it) to make a purchase
     * you repeat yourself, for example after a crash, safe as well.
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
    /**
     * Cancel (void) a label. The Gateway requires the carrier code (for example `USPS`); it is
     * optional here only so older call sites fail with a clear error before any request.
     */
    public function cancelLabel(string $labelId, ?string $carrierCode = null): mixed
    {
        if ($carrierCode === null || $carrierCode === '') {
            throw new \InvalidArgumentException('carrierCode is required to cancel a label (for example USPS).');
        }
        return $this->http->delete('/api/shipping/labels/' . rawurlencode($labelId) . '?carrierCode=' . rawurlencode($carrierCode));
    }
    public function track(string $trackingNumber): mixed { return $this->http->get('/api/shipping/track/' . rawurlencode($trackingNumber)); }
    /**
     * Validates an address with the documented POST /api/addresses/validate. Sends the flat
     * `['name' => ?, 'line1' => ..., 'line2' => ?, 'city' => ..., 'state' => ?, 'postalCode' => ...,
     * 'country' => 'US']`; a legacy `street1`/`zip` address or a Gateway ShippingAddress
     * (`addressLine1`/`stateProvince`/`countryCode`) is mapped to those names.
     *
     * Returns the bare AddressValidationResult: `isValid`, `suggestedAddress`, `isResidential`,
     * `warnings`, `validatedBy`, `validationUnavailable` and `checkedAt`.
     */
    public function validateAddress(array $address): mixed
    {
        return $this->http->post('/api/addresses/validate', self::toValidationRequest($address));
    }
    /** The payload is sent as given. Set `hazmatClassification` on an item only when you have a declaration. */
    /**
     * Buy labels for one order shipped in 1 to 99 boxes with the same carrier and service
     * (`POST /api/shipping/multi-package`, the Gateway MultiPackageShipmentRequest):
     * `['orderId' => 12345, 'carrierCode' => 'USPS', 'serviceCode' => ?, 'labelFormat' => ?, 'shipDate' => ?,
     * 'hazmatClassification' => ?, 'packages' => [['weightInOunces' => 16, 'lengthInInches' => 10,
     * 'widthInInches' => 8, 'heightInInches' => 4, 'deliveryConfirmationRequired' => ?,
     * 'signatureConfirmationRequired' => ?, 'insuranceCoverageRequested' => ?, 'insuredValue' => ?,
     * 'packageReference' => ?], ...]]`. Weight is in ounces and every dimension in inches; all four are required.
     *
     * Multi-package labels cannot carry HAZMAT yet: send `HazmatClassification::None` or leave
     * `hazmatClassification` out. The Gateway refuses any other class with 400 `HazmatNotSupported`.
     *
     * Returns the envelope when at least one label was bought (check `data.failedPackages` and each
     * box in `data.labels`). When none was, the Gateway answers 422 `AllPackagesFailed`, or 409
     * `OutcomeUnknown` when a box's carrier outcome is unknown and a label may exist: both throw a
     * FlexOpsError whose `data` holds the per-box results. After `OutcomeUnknown`, check the order
     * before buying again. Pass your own $idempotencyKey (and reuse it) to make a retry you run
     * yourself safe.
     */
    public function createMultiPackage(array $request, ?string $idempotencyKey = null): mixed
    {
        if ($idempotencyKey !== null && preg_match('/[\r\n]/', $idempotencyKey)) {
            throw new \InvalidArgumentException('Invalid idempotency key');
        }
        return $this->http->post('/api/shipping/multi-package', $request,
            headers: $idempotencyKey === null ? [] : ["Idempotency-Key: {$idempotencyKey}"]);
    }
    public function createBatch(array $request): mixed { return $this->http->post($this->wsPath() . '/labels/batch', $request); }
    public function previewBatch(array $request): mixed { return $this->http->post($this->wsPath() . '/labels/batch/preview', $request); }
    public function getBatchStatus(string $jobId): mixed { return $this->http->get($this->wsPath() . "/labels/batch/{$jobId}"); }
    public function downloadBatchLabel(string $jobId, string $itemId): mixed { return $this->http->get($this->wsPath() . "/labels/batch/{$jobId}/items/{$itemId}/label"); }
    public function getCarriers(): mixed { return $this->http->get('/api/shipping/carriers'); }

    public function getRecommendations(array $request): mixed { return $this->http->post('/api/shipping/recommendations', $request); }
    public function predictDelivery(array $request): mixed { return $this->http->post('/api/shipping/predictions/delivery', $request); }
    public function getSavings(): mixed { return $this->http->get('/api/shipping/savings'); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }

    private const ADDRESS_FIELD_ALIASES = [
        'street1' => 'line1',
        'addressLine1' => 'line1',
        'street2' => 'line2',
        'addressLine2' => 'line2',
        'stateProvince' => 'state',
        'zip' => 'postalCode',
        'countryCode' => 'country',
    ];

    /** Maps an address to the Gateway AddressValidationRequest; its own field names pass through. */
    private static function toValidationRequest(array $address): array
    {
        $mapped = [];
        foreach ($address as $key => $value) {
            if ($value !== null) {
                $mapped[self::ADDRESS_FIELD_ALIASES[$key] ?? $key] = $value;
            }
        }
        return $mapped;
    }
}
