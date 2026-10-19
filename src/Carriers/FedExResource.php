<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

class FedExResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function validateAddress(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/AddressValidation/postFedExValidateAndCorrectDomesticAddress', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function validatePostalCode(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/AddressValidation/postFedExValidatePostalCode', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postRetrieveFedExRateAndTransitTimesAsync', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelShipment(mixed $body): mixed { return $this->http->put(self::P . '/api/v3/Shipping/putFedExCancelShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function validateShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postFedExValidateShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function track(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Tracking/postFedExRetrieveTrackingInfoByTrackingNumber', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function trackMultiPiece(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Tracking/postFedExRetrieveTrackingInfoForMultiPieceShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function registerTrackingNotification(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Tracking/postFedExRegisterForTrackingNotification', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/CarrierPickup/postFedExCreateCarrierPickupRequest', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelPickup(mixed $body): mixed { return $this->http->put(self::P . '/api/v3/CarrierPickup/putFedExCancelCarrierPickupRequest', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function searchLocations(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/LocationSearch/postFedExSearchValidLocations', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getServiceStandards(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/ServiceStandards/postFedExRetrieveServicesAndTransitTimes', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getFreightRate(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Freight/postFedExGetFreightRateQuote', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createFreightShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Freight/postFedExCreateFreightShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function groundClose(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/GroundClose/postFedExCloseWithDocuments', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function uploadTradeDocuments(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Trade/postFedExUploadTradeDocuments', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createOpenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/OpenShip/postFedExCreateOpenShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function addPackagesToOpenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/OpenShip/postFedExAddPackagesToOpenShipment', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function confirmOpenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/OpenShip/postFedExConfirmOpenShipment', $body); }
}
