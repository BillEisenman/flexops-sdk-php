<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

/**
 * DHL through the legacy ApiProxy route. With an API key, address validation, service points and
 * reference data require `shipping:read`; tracking and proof of delivery require `tracking:read`;
 * rates and products require `rates:read`.
 */
class DhlResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function validateAddress(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlValidateAddress', $params); }
    public function getRates(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlRates', $params); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getMultiPieceRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlMultiPieceRates', $body); }
    public function getProducts(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlProducts', $params); }
    public function track(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlTrackSingleShipment', $params); }
    public function trackMultiple(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlTrackMultipleShipments', $params); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlCreatePickup', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelPickup(): mixed { return $this->http->delete(self::P . '/api/v2/ShippingLabel/deleteDhlPickup'); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function calculateLandedCost(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlCalculateLandedCost', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function screenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlScreenShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function uploadInvoice(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlUploadInvoice', $body); }
    public function getProofOfDelivery(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlElectronicProofOfDelivery', $params); }
    public function getReferenceData(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlReferenceData', $params); }
    public function findServicePoints(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlServicePoints', $params); }
}
