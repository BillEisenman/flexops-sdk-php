<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * Read-only inventory lookups through the legacy ApiProxy route
 * (`/api/ApiProxy/api/v1/Inventory/...`).
 *
 * With an API key, every method requires the `inventory:read` permission; a key without it gets
 * 403 API_KEY_SCOPE_REQUIRED. Keys created with the default permissions include `inventory:read`.
 * The proxy refuses writes from API keys.
 */
class InventoryResource
{
    private const P = '/api/ApiProxy';

    public function __construct(private readonly HttpClient $http) {}

    public function getWarehouseSnapshot(?array $params = null): mixed { return $this->http->get(self::P . '/api/v1/Inventory/getWarehouseInventorySnapshot', $params); }
    public function getCompleteSnapshot(?array $params = null): mixed { return $this->http->get(self::P . '/api/v1/Inventory/getCompleteInventorySnapshot', $params); }
    public function getPartNumbers(?array $params = null): mixed { return $this->http->get(self::P . '/api/v1/Inventory/getPartNumberList', $params); }
}
