<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/** Carrier pickups. Every response is the `{success, data, message, errorCode}` envelope. */
class PickupsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    /**
     * Schedules a carrier pickup. API keys need `shipping:write`; the public demo keys are refused.
     *
     * $request is the Gateway SchedulePickupRequest: `carrierCode`, `pickupDate`, `street`, `city`,
     * `state`, `postalCode` and `packageCount` (required), plus optional `country` (default US),
     * `totalWeightOz`, `specialInstructions`, `readyTime`, `closeTime`, `contactName` and `contactPhone`.
     */
    public function schedule(array $request): mixed { return $this->http->post($this->wsPath() . '/pickups', $request); }
    /** The workspace's scheduled pickups. */
    public function list(): mixed { return $this->http->get($this->wsPath() . '/pickups/scheduled'); }
    public function get(string $pickupId, string $carrierCode = 'usps'): mixed
    {
        return $this->http->get($this->wsPath() . '/pickups/' . rawurlencode($pickupId), ['carrierCode' => $carrierCode]);
    }
    /** Cancels a scheduled pickup. API keys need `shipping:write`; the public demo keys are refused. */
    public function cancel(string $pickupId, string $carrierCode = 'usps'): mixed
    {
        return $this->http->delete($this->wsPath() . '/pickups/' . rawurlencode($pickupId) . '?carrierCode=' . rawurlencode($carrierCode));
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
