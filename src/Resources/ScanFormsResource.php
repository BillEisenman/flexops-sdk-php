<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * USPS SCAN forms. Replies are the Gateway envelope as an array:
 * `['success' => true, 'message' => null, 'data' => $form, 'errors' => null, 'errorCode' => null]`.
 * A form (the Gateway's ScanFormDto) has:
 *
 * - `scanFormId`: 32 hexadecimal digits, no dashes; pass it to get().
 * - `state`: `ScanFormState::Created->value` or `ScanFormState::OutcomeUnknown->value`.
 * - `originPostalCode`: the origin ZIP, or null.
 * - `trackingNumbers`: the packages on the form.
 * - `createdAtUtc`: ISO 8601 in UTC, for example `2026-09-08T21:14:03.512Z`.
 * - `scanFormImage`: the PS Form 5630 PDF, base64; null for sandbox forms and unresolved
 *   operations. `ScanFormState::pdfBytes()` decodes it.
 * - `manifestNumber`: the USPS manifest number; `SBOX...` in the sandbox, `''` while unresolved.
 * - `manifestDate`: the mailing date, for example `2026-09-08T00:00:00`.
 * - `packageCount`, `statusMessage`, `success` (true when `state` is `Created`), `errorMessage`
 *   (not set by the current Gateway; refusals throw FlexOpsError) and `isSandbox`.
 *
 * @phpstan-type ScanForm array{scanFormId: string, state: string, originPostalCode: ?string,
 *     trackingNumbers: list<string>, createdAtUtc: string, scanFormImage: ?string, manifestNumber: ?string,
 *     manifestDate: ?string, packageCount: int, statusMessage: ?string, success: bool, errorMessage: ?string,
 *     isSandbox: bool}
 */
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
     *
     * Returns the envelope whose `data` is the created form, also when the same `requestId` and inputs
     * are sent again. Throws FlexOpsError with status 409 when the `requestId` was used with different
     * inputs, when the packages already belong to a manifest, or when this or an earlier operation
     * needs review (its message names the operation; do not resubmit those packages), and 400 when the
     * request or package selection is refused. A 409 is not retried.
     *
     * @return array{success: bool, data: ScanForm, message: ?string}
     */
    public function create(array $request): mixed
    {
        if (empty($request['requestId'])) {
            $request['requestId'] = HttpClient::uuid4();
        }
        return $this->http->post($this->wsPath() . '/scan-forms', $request);
    }
    /**
     * Lists the workspace's saved scan forms, newest mailing date first, including any whose outcome
     * is unknown.
     *
     * @return array{success: bool, data: list<ScanForm>, message: ?string}
     */
    public function list(): mixed { return $this->http->get($this->wsPath() . '/scan-forms'); }

    /**
     * Gets a saved scan form by its `scanFormId`. A form that does not exist throws FlexOpsError with
     * status 404.
     *
     * @return array{success: bool, data: ScanForm, message: ?string}
     */
    public function get(string $scanFormId): mixed { return $this->http->get($this->wsPath() . "/scan-forms/{$scanFormId}"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
