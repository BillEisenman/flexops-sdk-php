<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ReturnsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(?int $page = null, ?int $pageSize = null, ?string $status = null): mixed
    {
        return $this->http->get($this->wsPath() . '/returns', array_filter(['page' => $page, 'pageSize' => $pageSize, 'status' => $status]) ?: null);
    }
    public function get(string $returnId): mixed { return $this->http->get($this->wsPath() . "/returns/{$returnId}"); }
    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/returns', $request); }
    public function approve(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/approve"); }
    public function reject(string $returnId, string $reason): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/reject", ['reason' => $reason]); }
    public function cancel(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/cancel"); }
    /**
     * Generate a return label for an approved RMA.
     *
     * Pass the package as an array: `['weightOz' => 16, 'lengthIn' => 10, 'widthIn' => 8, 'heightIn' => 4]`,
     * optionally with `carrierCode`, `serviceType`, `paymentConfirmed` and `hazmatClassification`.
     * The carrier needs the weight and all three dimensions. Anything you leave out comes from the
     * workspace's default shipping preset; with no preset the Gateway answers 400
     * `PackageMeasurementsRequired` naming the missing fields.
     *
     * For backward compatibility the argument may also be a `HazmatClassification` (declares what the
     * return holds) or null; both send no package, so they only work for a workspace with a preset.
     *
     * @param array<string, mixed>|\FlexOps\HazmatClassification|null $options
     */
    public function generateLabel(string $returnId, array|\FlexOps\HazmatClassification|null $options = null): mixed
    {
        if ($options instanceof \FlexOps\HazmatClassification) {
            $options = ['hazmatClassification' => $options];
        }
        // Always a JSON object: the Gateway binds a required GenerateReturnLabelRequest body and refuses an empty one with 400.
        $body = empty($options) ? new \stdClass() : $options;
        return $this->http->post($this->wsPath() . "/returns/{$returnId}/label", $body);
    }
    public function markReceived(string $returnId, array $items): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/receive", ['items' => $items]); }
    public function processRefund(string $returnId): mixed { return $this->http->post($this->wsPath() . "/returns/{$returnId}/refund"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
