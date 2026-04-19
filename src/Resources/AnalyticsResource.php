<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class AnalyticsResource
{
    private const BASE = '/api/ApiProxy/api/v4/Analytics';

    public function __construct(private readonly HttpClient $http) {}

    public function shipmentsTrend(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ShipmentsTrend', $this->dateQ($startDate, $endDate)); }
    public function carrierSummary(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/CarrierSummary', $this->dateQ($startDate, $endDate)); }
    public function topDestinations(?string $startDate = null, ?string $endDate = null, ?int $limit = null): mixed { return $this->http->get(self::BASE . '/TopDestinations', array_filter(array_merge($this->dateQ($startDate, $endDate) ?? [], ['limit' => $limit])) ?: null); }
    public function inventoryMetrics(): mixed { return $this->http->get(self::BASE . '/InventoryMetrics'); }
    public function stockByWarehouse(): mixed { return $this->http->get(self::BASE . '/StockByWarehouse'); }
    public function orderMetrics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/OrderMetrics', $this->dateQ($startDate, $endDate)); }
    public function orderTrend(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/OrderTrend', $this->dateQ($startDate, $endDate)); }
    public function topSellingProducts(?string $startDate = null, ?string $endDate = null, ?int $limit = null): mixed { return $this->http->get(self::BASE . '/TopSellingProducts', array_filter(array_merge($this->dateQ($startDate, $endDate) ?? [], ['limit' => $limit])) ?: null); }
    public function returnsMetrics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ReturnsMetrics', $this->dateQ($startDate, $endDate)); }
    public function returnsTrend(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ReturnsTrend', $this->dateQ($startDate, $endDate)); }
    public function returnReasons(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ReturnReasons', $this->dateQ($startDate, $endDate)); }
    public function performanceMetrics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/PerformanceMetrics', $this->dateQ($startDate, $endDate)); }
    public function carrierPerformance(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/CarrierPerformance', $this->dateQ($startDate, $endDate)); }
    public function shippingCostAnalytics(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/ShippingCostAnalytics', $this->dateQ($startDate, $endDate)); }
    public function deliveryPerformance(?string $startDate = null, ?string $endDate = null): mixed { return $this->http->get(self::BASE . '/DeliveryPerformance', $this->dateQ($startDate, $endDate)); }

    private function dateQ(?string $startDate, ?string $endDate): ?array
    {
        $q = array_filter(['startDate' => $startDate, 'endDate' => $endDate]);
        return empty($q) ? null : $q;
    }
}
