<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

class UspsResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function validateAddress(array $params): mixed { return $this->http->get(self::P . '/api/v3/AddressValidation/getUspsValidateAndCorrectAddress', $params); }
    public function cityStateLookup(string $zipCode): mixed { return $this->http->get(self::P . '/api/v3/AddressValidation/getUspsCityStateLookupByZipCode', ['zipCode' => $zipCode]); }
    public function zipCodeLookup(array $params): mixed { return $this->http->get(self::P . '/api/v3/AddressValidation/getUspsZipCodeLookupByAddress', $params); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getDomesticRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchDomesticBaseRates', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getDomesticProducts(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchEligibleDomesticProducts', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getDomesticPrices(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchEligibleDomesticPrices', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getInternationalRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchInternationalBaseRates', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function getInternationalPrices(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchEligibleInternationalPrices', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelDomesticLabel(): mixed { return $this->http->delete(self::P . '/api/v3/Shipping/cancelUspsDomesticShipmentLabel'); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelInternationalLabel(): mixed { return $this->http->delete(self::P . '/api/v3/Shipping/cancelUspsInternationalShipmentLabel'); }
    public function trackSummary(array $params): mixed { return $this->http->get(self::P . '/api/v3/Tracking/getUspsTrackingSummaryInformation', $params); }
    public function trackDetail(array $params): mixed { return $this->http->get(self::P . '/api/v3/Tracking/getUspsTrackingDetailInformation', $params); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/CarrierPickup/postUspsCreateCarrierPickupSchedule', $body); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function cancelPickup(): mixed { return $this->http->delete(self::P . '/api/v3/CarrierPickup/cancelUspsCarrierPickupSchedule'); }
    /**
     * @deprecated Legacy carrier proxy (sunset 2027-06-30). Needs a user token: API keys get 403
     *             API_KEY_SCOPE_REQUIRED. Use $client->shipping / $client->pickups / $client->scanForms
     *             where they cover this.
     */
    public function createScanForm(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/ScanForm/postUspsCreateScanFormLabelShipment', $body); }
    public function deliveryStandards(array $params): mixed { return $this->http->get(self::P . '/api/v3/ServiceStandards/getUspsGetDeliveryStandardsEstimates', $params); }
    public function findDropOffLocations(array $params): mixed { return $this->http->get(self::P . '/api/v3/LocationSearch/getUspsFindValidDropOffLocations', $params); }
    public function findPostOffices(array $params): mixed { return $this->http->get(self::P . '/api/v3/LocationSearch/getUspsFindValidPostOfficeLocations', $params); }
}
