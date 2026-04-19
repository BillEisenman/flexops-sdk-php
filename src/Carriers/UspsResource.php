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
    public function getDomesticRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchDomesticBaseRates', $body); }
    public function getDomesticProducts(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchEligibleDomesticProducts', $body); }
    public function getDomesticPrices(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchEligibleDomesticPrices', $body); }
    public function getInternationalRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchInternationalBaseRates', $body); }
    public function getInternationalPrices(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postUspsSearchEligibleInternationalPrices', $body); }
    public function createDomesticLabel(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postUspsGenerateDomesticShippingLabel', $body); }
    public function createReturnLabel(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postUspsGenerateDomesticReturnsShippingLabel', $body); }
    public function createInternationalLabel(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postUspsGenerateInternationalShippingLabel', $body); }
    public function cancelDomesticLabel(): mixed { return $this->http->delete(self::P . '/api/v3/Shipping/cancelUspsDomesticShipmentLabel'); }
    public function cancelInternationalLabel(): mixed { return $this->http->delete(self::P . '/api/v3/Shipping/cancelUspsInternationalShipmentLabel'); }
    public function trackSummary(array $params): mixed { return $this->http->get(self::P . '/api/v3/Tracking/getUspsTrackingSummaryInformation', $params); }
    public function trackDetail(array $params): mixed { return $this->http->get(self::P . '/api/v3/Tracking/getUspsTrackingDetailInformation', $params); }
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/CarrierPickup/postUspsCreateCarrierPickupSchedule', $body); }
    public function cancelPickup(): mixed { return $this->http->delete(self::P . '/api/v3/CarrierPickup/cancelUspsCarrierPickupSchedule'); }
    public function createScanForm(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/ScanForm/postUspsCreateScanFormLabelShipment', $body); }
    public function deliveryStandards(array $params): mixed { return $this->http->get(self::P . '/api/v3/ServiceStandards/getUspsGetDeliveryStandardsEstimates', $params); }
    public function findDropOffLocations(array $params): mixed { return $this->http->get(self::P . '/api/v3/LocationSearch/getUspsFindValidDropOffLocations', $params); }
    public function findPostOffices(array $params): mixed { return $this->http->get(self::P . '/api/v3/LocationSearch/getUspsFindValidPostOfficeLocations', $params); }
}
