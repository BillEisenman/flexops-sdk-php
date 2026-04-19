<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-03-08
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

use FlexOps\Resources\AuthResource;
use FlexOps\Resources\WorkspacesResource;
use FlexOps\Resources\ShippingResource;
use FlexOps\Resources\CarriersResource;
use FlexOps\Resources\WebhooksResource;
use FlexOps\Resources\WalletResource;
use FlexOps\Resources\InsuranceResource;
use FlexOps\Resources\ReturnsResource;
use FlexOps\Resources\ApiKeysResource;
use FlexOps\Resources\AnalyticsResource;
use FlexOps\Resources\OrdersResource;
use FlexOps\Resources\InventoryResource;
use FlexOps\Resources\PickupsResource;
use FlexOps\Resources\ScanFormsResource;
use FlexOps\Resources\RulesResource;
use FlexOps\Resources\OffsetResource;
use FlexOps\Resources\HsCodesResource;
use FlexOps\Resources\RecurringShipmentsResource;
use FlexOps\Resources\EmailTemplatesResource;
use FlexOps\Resources\ReportsResource;

class FlexOps
{
    private HttpClient $http;
    public ?string $workspaceId;

    public readonly AuthResource $auth;
    public readonly WorkspacesResource $workspaces;
    public readonly ShippingResource $shipping;
    public readonly CarriersResource $carriers;
    public readonly WebhooksResource $webhooks;
    public readonly WalletResource $wallet;
    public readonly InsuranceResource $insurance;
    public readonly ReturnsResource $returns;
    public readonly ApiKeysResource $apiKeys;
    public readonly AnalyticsResource $analytics;
    public readonly OrdersResource $orders;
    public readonly InventoryResource $inventory;
    public readonly PickupsResource $pickups;
    public readonly ScanFormsResource $scanForms;
    public readonly RulesResource $rules;
    public readonly OffsetResource $offsets;
    public readonly HsCodesResource $hsCodes;
    public readonly RecurringShipmentsResource $recurringShipments;
    public readonly EmailTemplatesResource $emailTemplates;
    public readonly ReportsResource $reports;

    public function __construct(array $config = [], ?HttpClient $httpClient = null)
    {
        $this->http = $httpClient ?? new HttpClient(
            baseUrl: $config['baseUrl'] ?? 'https://gateway.flexops.io',
            apiKey: $config['apiKey'] ?? null,
            accessToken: $config['accessToken'] ?? null,
            timeout: $config['timeout'] ?? 30
        );
        $this->workspaceId = $config['workspaceId'] ?? null;

        $getWsId = fn() => $this->workspaceId;

        $this->auth = new AuthResource($this->http);
        $this->workspaces = new WorkspacesResource($this->http, $getWsId);
        $this->shipping = new ShippingResource($this->http, $getWsId);
        $this->carriers = new CarriersResource($this->http);
        $this->webhooks = new WebhooksResource($this->http, $getWsId);
        $this->wallet = new WalletResource($this->http, $getWsId);
        $this->insurance = new InsuranceResource($this->http, $getWsId);
        $this->returns = new ReturnsResource($this->http, $getWsId);
        $this->apiKeys = new ApiKeysResource($this->http, $getWsId);
        $this->analytics = new AnalyticsResource($this->http);
        $this->orders = new OrdersResource($this->http);
        $this->inventory = new InventoryResource($this->http);
        $this->pickups = new PickupsResource($this->http, $getWsId);
        $this->scanForms = new ScanFormsResource($this->http, $getWsId);
        $this->rules = new RulesResource($this->http, $getWsId);
        $this->offsets = new OffsetResource($this->http, $getWsId);
        $this->hsCodes = new HsCodesResource($this->http, $getWsId);
        $this->recurringShipments = new RecurringShipmentsResource($this->http, $getWsId);
        $this->emailTemplates = new EmailTemplatesResource($this->http, $getWsId);
        $this->reports = new ReportsResource($this->http, $getWsId);
    }

    public function setAccessToken(string $token): void
    {
        $this->http->setAccessToken($token);
    }

    public function setApiKey(string $key): void
    {
        $this->http->setApiKey($key);
    }
}
