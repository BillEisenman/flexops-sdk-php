<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * HS code search and landed-cost estimates. The Gateway resolves the workspace from the
 * credentials, so these routes carry no workspace id. Responses are bare JSON (no envelope).
 */
class HsCodesResource
{
    public function __construct(private readonly HttpClient $http) {}

    /** Returns a bare list of matching HS codes. */
    public function search(string $query, ?string $destinationCountry = null, int $maxResults = 10): mixed
    {
        return $this->http->get('/api/shipping/hs-codes/search', array_filter(['query' => $query, 'destinationCountry' => $destinationCountry, 'maxResults' => $maxResults]));
    }

    public function lookup(string $code): mixed { return $this->http->get('/api/shipping/hs-codes/' . rawurlencode($code)); }

    /**
     * $request is `['destinationCountry' => 'CA', 'shippingCost' => 12.5, 'items' => [[
     * 'description' => ..., 'quantity' => 1, 'value' => 20.0, 'weightOz' => 8, 'originCountry' => 'US',
     * 'hsCode' => ?, 'skuOrProductId' => ?]]]`.
     */
    public function estimateLandedCost(array $request): mixed { return $this->http->post('/api/shipping/landed-cost', $request); }
}
