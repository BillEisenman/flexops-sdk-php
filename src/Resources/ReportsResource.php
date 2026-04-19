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

class ReportsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath() . '/report-schedules/'); }
    public function get(string $id): mixed { return $this->http->get($this->wsPath() . "/report-schedules/{$id}"); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/report-schedules/', $request); }
    public function update(string $id, array $request): mixed { return $this->http->put($this->wsPath() . "/report-schedules/{$id}", $request); }
    public function delete(string $id): mixed { return $this->http->delete($this->wsPath() . "/report-schedules/{$id}"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
