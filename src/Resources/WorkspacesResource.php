<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

class WorkspacesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get('/api/workspaces'); }
    public function get(?string $workspaceId = null): mixed { return $this->http->get('/api/workspaces/' . ($workspaceId ?? ($this->getWsId)())); }
    public function create(array $request): mixed { return $this->http->post('/api/workspaces', $request); }
    public function update(array $data): mixed { return $this->http->put('/api/workspaces/' . ($this->getWsId)(), $data); }
    public function listMembers(): mixed { return $this->http->get($this->wsPath() . '/members'); }
    public function inviteMember(string $email, ?string $role = null): mixed { return $this->http->post($this->wsPath() . '/members/invite', array_filter(['email' => $email, 'role' => $role])); }
    public function removeMember(string $userId): mixed { return $this->http->delete($this->wsPath() . "/members/{$userId}"); }
    public function updateMemberRole(string $userId, string $role): mixed { return $this->http->put($this->wsPath() . "/members/{$userId}/role", ['role' => $role]); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
