<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;
use FlexOps\WorkspaceRole;

/** Workspaces and members. Every response is the `{success, data, message, errorCode}` envelope. */
class WorkspacesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get('/api/workspaces'); }
    public function get(?string $workspaceId = null): mixed { return $this->http->get('/api/workspaces/' . ($workspaceId ?? ($this->getWsId)())); }
    public function listMembers(): mixed { return $this->http->get($this->wsPath() . '/members'); }
    /**
     * Invites a member. $role is a WorkspaceRole, its number, or its name ("Admin"); it is sent as
     * the Gateway number. There is no default: the Gateway would read a missing role as Owner.
     *
     * @throws \InvalidArgumentException $role names no role. No request is sent.
     */
    public function inviteMember(string $email, WorkspaceRole|int|string $role): mixed
    {
        return $this->http->post($this->wsPath() . '/members/invite', ['email' => $email, 'role' => WorkspaceRole::parse($role)->value]);
    }
    public function removeMember(string $userId): mixed { return $this->http->delete($this->wsPath() . "/members/{$userId}"); }
    /**
     * Changes a member's role, sent as the Gateway `newRole` number.
     *
     * @throws \InvalidArgumentException $role names no role. No request is sent.
     */
    public function updateMemberRole(string $userId, WorkspaceRole|int|string $role): mixed
    {
        return $this->http->put($this->wsPath() . "/members/{$userId}/role", ['newRole' => WorkspaceRole::parse($role)->value]);
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)(); }
}
