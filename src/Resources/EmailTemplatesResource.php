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

class EmailTemplatesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/shipment-email-templates/'); }
    public function get(string $id): mixed { return $this->http->get($this->wsPath() . "/shipment-email-templates/{$id}"); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/shipment-email-templates/', $request); }
    public function update(string $id, array $request): mixed { return $this->http->put($this->wsPath() . "/shipment-email-templates/{$id}", $request); }
    public function delete(string $id): mixed { return $this->http->delete($this->wsPath() . "/shipment-email-templates/{$id}"); }
    public function preview(string $id, array $context = []): mixed { return $this->http->post($this->wsPath() . "/shipment-email-templates/{$id}/preview", $context); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
