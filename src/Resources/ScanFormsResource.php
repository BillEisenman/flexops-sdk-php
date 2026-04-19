<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class ScanFormsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function create(array $request): mixed { return $this->http->post($this->wsPath() . '/scan-forms', $request); }
    public function list(): mixed { return $this->http->get($this->wsPath() . '/scan-forms'); }
    public function get(string $scanFormId): mixed { return $this->http->get($this->wsPath() . "/scan-forms/{$scanFormId}"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
