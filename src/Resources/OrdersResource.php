<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class OrdersResource
{
    private const BASE = '/api/ApiProxy/api/v1/Order';

    public function __construct(private readonly HttpClient $http) {}

    public function create(mixed $order): mixed { return $this->http->post(self::BASE . '/postNewOrder', $order); }
    public function getNewOrders(?array $params = null): mixed { return $this->http->get(self::BASE . '/getNewOrderList', $params); }
    public function getByStatus(?array $params = null): mixed { return $this->http->get(self::BASE . '/getAllOrderListByStatus', $params); }
    public function getDetails(string $orderNumber): mixed { return $this->http->get(self::BASE . '/getCompleteOrderDetailsByOrderNumber', ['orderNumber' => $orderNumber]); }
    public function getExtendedDetails(string $orderNumber): mixed { return $this->http->get(self::BASE . '/getExtendedOrderDetailsByOrderNumber', ['orderNumber' => $orderNumber]); }
    public function getStatus(string $orderNumber): mixed { return $this->http->get(self::BASE . '/getIndividualOrderStatusByOrderNumber', ['orderNumber' => $orderNumber]); }
    public function cancel(string $orderNumber): mixed { return $this->http->post(self::BASE . '/cancelOrderByOrderNumber', ['orderNumber' => $orderNumber]); }
    public function getItems(string $orderNumber): mixed { return $this->http->get(self::BASE . '/getAllOrderItemsByOrderNumber', ['orderNumber' => $orderNumber]); }
    public function getShipMethods(): mixed { return $this->http->get(self::BASE . '/getAvailableShipMethodsList'); }
    public function getWarehouses(): mixed { return $this->http->get(self::BASE . '/getActiveWarehouseList'); }
    public function getCountryCodes(): mixed { return $this->http->get(self::BASE . '/getCountryNameCodeList'); }
    public function getStatusTypes(): mixed { return $this->http->get(self::BASE . '/getOrderStatusTypesList'); }
}
