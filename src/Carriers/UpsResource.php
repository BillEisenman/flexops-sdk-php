<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

class UpsResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function validateAddress(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsVerifyAddress', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsRateCheck', $body); }
    public function track(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getSingleUpsTrackingDetail', $params); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsCreatePickup', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelPickup(): mixed { return $this->http->delete(self::P . '/api/v2/ShippingLabel/deleteUpsPickup'); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getTransitTimes(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsGetTransitTimes', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getLandedCost(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsGetLandedCostQuote', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function searchLocations(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsSearchLocations', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function uploadDocument(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsUploadPaperlessDocument', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createFreightShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsCreateFreightShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getFreightRate(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsGetFreightRate', $body); }
}
