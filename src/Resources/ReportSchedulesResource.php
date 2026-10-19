<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * Scheduled reports emailed to a list of recipients. Responses are the `{success, data, message}`
 * envelope, except delete, which answers 204 and returns null.
 *
 * Reads work with any API key or a user token. Writes need a user token (`auth->login()`) or an
 * API key granted the `report-schedules:write` scope; other keys get 403
 * `API_KEY_SCOPE_REQUIRED` with `requiredScopes`.
 */
class ReportSchedulesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath()); }
    public function get(string $id): mixed { return $this->http->get($this->wsPath() . '/' . rawurlencode($id)); }
    /**
     * Creates a schedule (201). $request is `['reportType' => 'shipments', 'format' => ReportFormat::Csv,
     * 'frequencyType' => ReportFrequency::Weekly, 'dayOfWeek' => 1, 'timeOfDayUtc' => '08:00:00',
     * 'recipientEmails' => 'ops@example.com,cfo@example.com', 'filterParametersJson' => ?, 'dayOfMonth' => ?,
     * 'maxFailures' => ?]`.
     */
    public function create(array $request): mixed { return $this->http->post($this->wsPath(), $request); }
    /** Every field of create() is optional here. */
    public function update(string $id, array $request): mixed { return $this->http->put($this->wsPath() . '/' . rawurlencode($id), $request ?: new \stdClass()); }
    public function delete(string $id): mixed { return $this->http->delete($this->wsPath() . '/' . rawurlencode($id)); }
    /** Flips `isEnabled`. The envelope's `data` is null. */
    public function toggle(string $id): mixed { return $this->http->post($this->wsPath() . '/' . rawurlencode($id) . '/toggle'); }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)() . '/report-schedules'; }
}
