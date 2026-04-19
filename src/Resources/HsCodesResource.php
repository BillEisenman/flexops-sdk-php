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

class HsCodesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function search(string $query, ?string $destinationCountry = null, int $maxResults = 10): mixed
    {
        return $this->http->get($this->wsPath() . '/shipping/hs-codes/search', array_filter(['query' => $query, 'destinationCountry' => $destinationCountry, 'maxResults' => $maxResults]));
    }
    public function lookup(string $code): mixed { return $this->http->get($this->wsPath() . "/shipping/hs-codes/{$code}"); }
    public function estimateLandedCost(array $request): mixed { return $this->http->post($this->wsPath() . '/shipping/landed-cost', $request); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
