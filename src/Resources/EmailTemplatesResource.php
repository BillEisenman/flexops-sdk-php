<?php
namespace FlexOps\Resources;

use FlexOps\HttpClient;

/**
 * Branded shipment-tracking emails, one template per tracking event. Responses are bare JSON
 * (no envelope); delete answers 204 and returns null.
 *
 * Reads work with any API key or a user token. Writes (upsert, delete and preview) need a user
 * token (`auth->login()`) or an API key granted the `email-templates:write` scope; other keys get
 * 403 `API_KEY_SCOPE_REQUIRED` with `requiredScopes`.
 */
class EmailTemplatesResource
{
    public function __construct(private readonly HttpClient $http, private readonly \Closure $getWsId) {}

    public function list(): mixed { return $this->http->get($this->wsPath()); }
    public function get(string $templateId): mixed { return $this->http->get($this->wsPath() . '/' . rawurlencode($templateId)); }
    /**
     * Creates or replaces the template for `eventType`. $request is `['eventType' => TrackingEmailEvent::Delivered,
     * 'subjectTemplate' => 'Delivered: {{tracking_number}}', 'htmlBodyTemplate' => '<p>...</p>', 'isEnabled' => true]`.
     */
    public function upsert(array $request): mixed { return $this->http->put($this->wsPath(), $request); }
    public function delete(string $templateId): mixed { return $this->http->delete($this->wsPath() . '/' . rawurlencode($templateId)); }
    /** Renders a draft with sample data. Returns `['renderedSubject' => ..., 'renderedHtmlBody' => ...]`. */
    public function preview(string $subjectTemplate, string $htmlBodyTemplate): mixed
    {
        return $this->http->post($this->wsPath() . '/preview', ['subjectTemplate' => $subjectTemplate, 'htmlBodyTemplate' => $htmlBodyTemplate]);
    }

    private function wsPath(): string { return '/api/workspaces/' . ($this->getWsId)() . '/email-templates'; }
}
