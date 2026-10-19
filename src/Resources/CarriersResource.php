<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;
use FlexOps\Carriers\UspsResource;
use FlexOps\Carriers\UpsResource;
use FlexOps\Carriers\FedExResource;
use FlexOps\Carriers\DhlResource;

/**
 * Carrier-specific calls through the Gateway's legacy ApiProxy route (sunset 2027-06-30).
 * Lookups (GET) work with an API key. The writes are deprecated and need a user token
 * (`auth->login()`): API keys get 403 API_KEY_SCOPE_REQUIRED. Use `shipping`, `pickups` and
 * `scanForms` where they cover the call. The proxy's label-generation routes are closed (410).
 */
class CarriersResource
{
    public readonly UspsResource $usps;
    public readonly UpsResource $ups;
    public readonly FedExResource $fedex;
    public readonly DhlResource $dhl;

    public function __construct(HttpClient $http)
    {
        $this->usps = new UspsResource($http);
        $this->ups = new UpsResource($http);
        $this->fedex = new FedExResource($http);
        $this->dhl = new DhlResource($http);
    }
}
