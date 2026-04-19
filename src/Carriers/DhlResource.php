<?php
namespace FlexOps\Carriers;

use FlexOps\HttpClient;

class DhlResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function validateAddress(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlValidateAddress', $params); }
    public function getRates(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlRates', $params); }
    public function getMultiPieceRates(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlMultiPieceRates', $body); }
    public function getProducts(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlProducts', $params); }
    public function createShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlCreateShipment', $body); }
    public function track(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlTrackSingleShipment', $params); }
    public function trackMultiple(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlTrackMultipleShipments', $params); }
    public function createPickup(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlCreatePickup', $body); }
    public function updatePickup(mixed $body): mixed { return $this->http->patch(self::P . '/api/v2/ShippingLabel/patchDhlUpdatePickup', $body); }
    public function cancelPickup(): mixed { return $this->http->delete(self::P . '/api/v2/ShippingLabel/deleteDhlPickup'); }
    public function calculateLandedCost(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlCalculateLandedCost', $body); }
    public function screenShipment(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlScreenShipment', $body); }
    public function uploadInvoice(mixed $body): mixed { return $this->http->post(self::P . '/api/v2/ShippingLabel/postDhlUploadInvoice', $body); }
    public function getProofOfDelivery(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlElectronicProofOfDelivery', $params); }
    public function getReferenceData(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlReferenceData', $params); }
    public function findServicePoints(array $params): mixed { return $this->http->get(self::P . '/api/v2/ShippingLabel/getDhlServicePoints', $params); }
}
