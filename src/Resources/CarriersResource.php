<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;
use FlexOps\Carriers\UspsResource;
use FlexOps\Carriers\UpsResource;
use FlexOps\Carriers\FedExResource;
use FlexOps\Carriers\DhlResource;

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
