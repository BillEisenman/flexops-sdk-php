<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class InventoryResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function getWarehouseSnapshot(?array $params = null): mixed { return $this->http->get(self::P . '/api/v1/Inventory/getWarehouseInventorySnapshot', $params); }
    public function getCompleteSnapshot(?array $params = null): mixed { return $this->http->get(self::P . '/api/v1/Inventory/getCompleteInventorySnapshot', $params); }
    public function getPartNumbers(?array $params = null): mixed { return $this->http->get(self::P . '/api/v1/Inventory/getPartNumberList', $params); }
}
