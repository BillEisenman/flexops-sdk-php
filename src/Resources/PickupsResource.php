<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class PickupsResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function schedule(array $request): mixed { return $this->http->post($this->wsPath() . '/pickups', $request); }
    public function list(): mixed { return $this->http->get($this->wsPath() . '/pickups'); }
    public function get(string $pickupId): mixed { return $this->http->get($this->wsPath() . "/pickups/{$pickupId}"); }
    public function cancel(string $pickupId): mixed { return $this->http->delete($this->wsPath() . "/pickups/{$pickupId}"); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
