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

class OffsetResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function offset(string $labelId): mixed { return $this->http->post($this->wsPath() . "/shipping/labels/{$labelId}/offset"); }
    public function getEmissions(string $labelId): mixed { return $this->http->get($this->wsPath() . "/shipping/labels/{$labelId}/emissions"); }
    public function batchOffset(array $labelIds): mixed { return $this->http->post($this->wsPath() . '/shipping/labels/offset/batch', ['labelIds' => $labelIds]); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
