<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/** Returns (RMAs). Every response is the `{success, data, message, errorCode}` envelope. */
class ReturnsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    /** $startDate and $endDate are ISO 8601 dates or date-times. */
    public function list(?int $page = null, ?int $pageSize = null, ?string $startDate = null, ?string $endDate = null): mixed
    {
        return $this->http->get($this->wsPath() . '/returns', array_filter(['page' => $page, 'pageSize' => $pageSize, 'startDate' => $startDate, 'endDate' => $endDate]) ?: null);
    }
    public function get(string $returnId): mixed { return $this->http->get($this->wsPath() . "/returns/{$returnId}"); }
    /**
     * Creates an RMA (201). $request is the Gateway CreateRmaRequest: `orderId` (a number), `reason`
     * and `items` (`[['sku' => ..., 'quantity' => 1, 'reason' => ?]]`) are required; `orderNumber`,
     * `notes`, `customerEmail` and `reasonId` are optional.
     */
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/returns/rma', $request); }
    /**
     * Generate a return label for an approved RMA.
     *
     * Pass the package as an array: `['weightOz' => 16, 'lengthIn' => 10, 'widthIn' => 8, 'heightIn' => 4]`,
     * optionally with `carrierCode` (the Gateway defaults it to `usps`), `serviceType`, `rateIndicator`,
     * `paymentConfirmed` and `hazmatClassification`.
     * The carrier needs the weight and all three dimensions. Anything you leave out comes from the
     * workspace's default shipping preset; with no preset the Gateway answers 400
     * `PackageMeasurementsRequired` naming the missing fields.
     *
     * For backward compatibility the argument may also be a `HazmatClassification` (declares what the
     * return holds) or null; both send no package, so they only work for a workspace with a preset.
     *
     * @param array<string, mixed>|\FlexOps\HazmatClassification|null $options
     */
    public function generateLabel(string $rmaNumber, array|\FlexOps\HazmatClassification|null $options = null): mixed
    {
        if ($options instanceof \FlexOps\HazmatClassification) {
            $options = ['hazmatClassification' => $options];
        }
        // Always a JSON object: the Gateway binds a required GenerateReturnLabelRequest body and refuses an empty one with 400.
        $body = empty($options) ? new \stdClass() : $options;
        return $this->http->post($this->wsPath() . "/returns/{$rmaNumber}/label", $body);
    }
    /**
     * Records the inspection of a returned RMA. Each inspected item is `['partNumber' => ..., 'quantity' => 1,
     * 'disposition' => 0|1|2, 'partDescription' => ?, 'inspectionNotes' => ?]`. The Gateway defaults the
     * warehouse to 1.
     */
    public function markReceived(int $rmaId, string $rmaNumber, array $inspectedItems, ?int $warehouseId = null): mixed
    {
        return $this->http->post($this->wsPath() . "/returns/{$rmaId}/receive", array_filter(
            ['rmaNumber' => $rmaNumber, 'warehouseId' => $warehouseId, 'inspectedItems' => $inspectedItems],
            fn($v) => $v !== null,
        ));
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
