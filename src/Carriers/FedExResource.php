<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

class FedExResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function validateAddress(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/AddressValidation/postFedExValidateAndCorrectDomesticAddress', $body); }
    public function validatePostalCode(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/AddressValidation/postFedExValidatePostalCode', $body); }
    public function getRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/RateCalculator/postRetrieveFedExRateAndTransitTimesAsync', $body); }
    public function createShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postFedExCreateNewShipment', $body); }
    public function cancelShipment(mixed $body): mixed { return $this->http->put(self::P . '/api/v3/Shipping/putFedExCancelShipment', $body); }
    public function validateShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postFedExValidateShipment', $body); }
    public function createReturnShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Shipping/postFedExCreateNewReturnShipment', $body); }
    public function track(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Tracking/postFedExRetrieveTrackingInfoByTrackingNumber', $body); }
    public function trackMultiPiece(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Tracking/postFedExRetrieveTrackingInfoForMultiPieceShipment', $body); }
    public function registerTrackingNotification(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Tracking/postFedExRegisterForTrackingNotification', $body); }
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/CarrierPickup/postFedExCreateCarrierPickupRequest', $body); }
    public function cancelPickup(mixed $body): mixed { return $this->http->put(self::P . '/api/v3/CarrierPickup/putFedExCancelCarrierPickupRequest', $body); }
    public function searchLocations(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/LocationSearch/postFedExSearchValidLocations', $body); }
    public function getServiceStandards(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/ServiceStandards/postFedExRetrieveServicesAndTransitTimes', $body); }
    public function getFreightRate(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Freight/postFedExGetFreightRateQuote', $body); }
    public function createFreightShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Freight/postFedExCreateFreightShipment', $body); }
    public function groundClose(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/GroundClose/postFedExCloseWithDocuments', $body); }
    public function uploadTradeDocuments(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/Trade/postFedExUploadTradeDocuments', $body); }
    public function createOpenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/OpenShip/postFedExCreateOpenShipment', $body); }
    public function addPackagesToOpenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/OpenShip/postFedExAddPackagesToOpenShipment', $body); }
    public function confirmOpenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v3/OpenShip/postFedExConfirmOpenShipment', $body); }
}
