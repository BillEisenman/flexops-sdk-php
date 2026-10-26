<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * Analytics through the legacy ApiProxy route (`/api/ApiProxy/api/v4/Analytics/...`).
 *
 * With an API key, each method requires the permission for the data it reads, named on the
 * method: `shipping:read` for shipment, carrier and delivery metrics, `inventory:read` for
 * inventory metrics, `orders:read` for order metrics and `returns:read` for returns metrics. A key
 * without it gets 403 API_KEY_SCOPE_REQUIRED. Keys created with the default permissions have all
 * four.
 */
class AnalyticsResource
{
    private const BASE = '/api/ApiProxy/api/v4/Analytics';

    public function __construct(private readonly HttpClient $http) {}

    /** Shipments trend over time. With an API key, requires `shipping:read`. */
    public function shipmentsTrend(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ShipmentsTrend', $this->dateQ($startDate, $endDate)); }
    /** Carrier usage summary. With an API key, requires `shipping:read`. */
    public function carrierSummary(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/CarrierSummary', $this->dateQ($startDate, $endDate)); }
    /** Top shipping destinations. With an API key, requires `shipping:read`. */
    public function topDestinations(?string $startDate = null, ?string $endDate = null, ?int $limit = null): mixed { return $this->http->get(self::BASE . '/TopDestinations', array_filter(array_merge($this->dateQ($startDate, $endDate) ?? [], ['limit' => $limit])) ?: null); }
    /** Inventory metrics (stock levels, low-stock alerts). With an API key, requires `inventory:read`. */
    public function inventoryMetrics(): mixed { return $this->http->get(self::BASE . '/InventoryMetrics'); }
    /** Stock levels by warehouse. With an API key, requires `inventory:read`. */
    public function stockByWarehouse(): mixed { return $this->http->get(self::BASE . '/StockByWarehouse'); }
    /** Order metrics (volume, revenue). With an API key, requires `orders:read`. */
    public function orderMetrics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/OrderMetrics', $this->dateQ($startDate, $endDate)); }
    /** Order trend over time. With an API key, requires `orders:read`. */
    public function orderTrend(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/OrderTrend', $this->dateQ($startDate, $endDate)); }
    /** Top selling products. With an API key, requires `orders:read`. */
    public function topSellingProducts(?string $startDate = null, ?string $endDate = null, ?int $limit = null): mixed { return $this->http->get(self::BASE . '/TopSellingProducts', array_filter(array_merge($this->dateQ($startDate, $endDate) ?? [], ['limit' => $limit])) ?: null); }
    /** Returns metrics. With an API key, requires `returns:read`. */
    public function returnsMetrics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ReturnsMetrics', $this->dateQ($startDate, $endDate)); }
    /** Returns trend over time. With an API key, requires `returns:read`. */
    public function returnsTrend(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ReturnsTrend', $this->dateQ($startDate, $endDate)); }
    /** Return reasons breakdown. With an API key, requires `returns:read`. */
    public function returnReasons(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ReturnReasons', $this->dateQ($startDate, $endDate)); }
    /** Fulfillment performance metrics. With an API key, requires `shipping:read`. */
    public function performanceMetrics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/PerformanceMetrics', $this->dateQ($startDate, $endDate)); }
    /** Carrier delivery performance. With an API key, requires `shipping:read`. */
    public function carrierPerformance(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/CarrierPerformance', $this->dateQ($startDate, $endDate)); }
    /** Shipping cost analytics. With an API key, requires `shipping:read`. */
    public function shippingCostAnalytics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ShippingCostAnalytics', $this->dateQ($startDate, $endDate)); }
    /** Delivery performance (on-time percentage). With an API key, requires `shipping:read`. */
    public function deliveryPerformance(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/DeliveryPerformance', $this->dateQ($startDate, $endDate)); }

    private function dateQ(?string $startDate, ?string $endDate): ?array
    {
        $q = array_filter(['startDate' => $startDate, 'endDate' => $endDate]);
        return empty($q) ? null : $q;
    }
}
