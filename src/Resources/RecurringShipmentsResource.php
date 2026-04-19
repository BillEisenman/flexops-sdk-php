<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-03-31
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Resources;

use FlexOps\HttpClient;

class RecurringShipmentsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(?int $page = null, ?int $pageSize = null, ?bool $isActive = null): mixed
    {
        return $this->http->get($this->wsPath() . '/recurring-shipments/', array_filter(['page' => $page, 'pageSize' => $pageSize, 'isActive' => $isActive]) ?: null);
    }
    public function get(string $id): mixed { return $this->http->get($this->wsPath() . "/recurring-shipments/{$id}"); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/recurring-shipments/', $request); }
    public function update(string $id, array $request): mixed { return $this->http->put($this->wsPath() . "/recurring-shipments/{$id}", $request); }
    public function delete(string $id): mixed { return $this->http->delete($this->wsPath() . "/recurring-shipments/{$id}"); }
    public function pause(string $id): mixed { return $this->http->post($this->wsPath() . "/recurring-shipments/{$id}/pause"); }
    public function resume(string $id): mixed { return $this->http->post($this->wsPath() . "/recurring-shipments/{$id}/resume"); }
    public function trigger(string $id): mixed { return $this->http->post($this->wsPath() . "/recurring-shipments/{$id}/trigger"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
