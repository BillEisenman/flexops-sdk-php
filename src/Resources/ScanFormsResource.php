<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ScanFormsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    /**
     * Creates a USPS SCAN form. API keys need `shipping:write`; the public demo keys are refused.
     *
     * $request is the Gateway CreateScanFormRequest: `trackingNumbers` (1 to 1,000) and `manifestDate`
     * (`YYYY-MM-DD`), plus optional `requestId`, `originPostalCode` (the origin ZIP group to manifest)
     * and `warehouseId` (limits package selection to one warehouse). Live creation needs `manifestDate`:
     * without it the Gateway answers 400 "Choose the label mailing date." The sandbox defaults it to
     * today (UTC).
     *
     * `requestId` identifies the operation. Repeating a request with the same ID returns the same form
     * instead of creating another; reusing it with different inputs is 409. When it is missing, one is
     * generated for this call and sent on every retry of it, so a retry cannot create a second form.
     * Pass your own to retry the call yourself after an unknown outcome.
     */
    public function create(array $request): mixed
    {
        if (empty($request['requestId'])) {
            $request['requestId'] = HttpClient::uuid4();
        }
        return $this->http->post($this->wsPath() . '/scan-forms', $request);
    }
    public function list(): mixed { return $this->http->get($this->wsPath() . '/scan-forms'); }
    public function get(string $scanFormId): mixed { return $this->http->get($this->wsPath() . "/scan-forms/{$scanFormId}"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
