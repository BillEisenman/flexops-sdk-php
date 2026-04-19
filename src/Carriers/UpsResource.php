<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

class UpsResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function validateAddress(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsVerifyAddress', $body); }
    public function getRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsRateCheck', $body); }
    public function createLabel(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/generateNewUpsShipLabel', $body); }
    public function track(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getSingleUpsTrackingDetail', $params); }
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsCreatePickup', $body); }
    public function cancelPickup(): mixed { return $this->http->delete(self::P . '/api/v2/ShippingLabel/deleteUpsPickup'); }
    public function getTransitTimes(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsGetTransitTimes', $body); }
    public function getLandedCost(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsGetLandedCostQuote', $body); }
    public function searchLocations(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsSearchLocations', $body); }
    public function uploadDocument(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsUploadPaperlessDocument', $body); }
    public function createFreightShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsCreateFreightShipment', $body); }
    public function getFreightRate(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postUpsGetFreightRate', $body); }
}
