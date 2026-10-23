# FlexOps PHP SDK

Official PHP SDK for the [FlexOps](https://flexops.io) multi-carrier shipping platform. Supports USPS, UPS, FedEx, DHL, OnTrac, Australia Post, Canada Post, Royal Mail, and LSO with rate shopping, label generation, tracking, webhooks, wallet, insurance, returns, and more.

## Installation

```bash
composer require flexops/sdk
```

## Quick Start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use FlexOps\FlexOps;
use FlexOps\HazmatClassification;

// API key authentication (recommended for server-to-server)
$client = new FlexOps([
    'apiKey'      => 'live_...',
    'workspaceId' => 'your-workspace-id',
]);

// Get shipping rates from all carriers
$rates = $client->shipping->getRates([
    'origin' => ['addressLine1' => '123 Main St', 'city' => 'New York', 'stateProvince' => 'NY', 'postalCode' => '10001'],
    'destination' => ['addressLine1' => '456 Oak Ave', 'city' => 'Los Angeles', 'stateProvince' => 'CA', 'postalCode' => '90210'],
    'package' => ['weight' => 16, 'weightUnit' => 'oz'],
]);

$request = [
    'carrierCode' => 'USPS', 'serviceCode' => 'GROUND_ADVANTAGE',
    'origin' => ['name' => 'Warehouse', 'addressLine1' => '123 Main St', 'city' => 'New York', 'stateProvince' => 'NY', 'postalCode' => '10001', 'countryCode' => 'US'],
    'destination' => ['name' => 'Customer', 'addressLine1' => '456 Oak Ave', 'city' => 'Los Angeles', 'stateProvince' => 'CA', 'postalCode' => '90210', 'countryCode' => 'US'],
    'package' => ['weight' => 16, 'weightUnit' => 'oz'],
    'maximumPostageAmount' => 10.25, // Caller-approved ceiling in USD.
    // Required without an orderId. The SDK never fills it in; None (0) = not hazardous.
    'hazmatClassification' => HazmatClassification::None,
];
$preview = $client->shipping->createLabel($request);
echo $preview['quotedPostageAmount'];

// Invoke only after explicit caller approval, within five minutes.
$purchaseApprovedLabel = function (string $purchaseKey) use ($client, $request, $preview) {
    $request['confirmationToken'] = $preview['confirmationToken'];
    return $client->shipping->createLabel($request, $purchaseKey);
}; // Persist the purchase key and reuse it if you send this purchase again.

// Track a shipment
$info = $client->shipping->track('9400111899223456789012');
```

### Live label approval (1.1.0+)

The example below requires SDK 1.1.0 or later. Version 1.0.2 does not
include the per-call idempotency argument.

For live domestic single-label requests, `maximumPostageAmount` is required: positive
USD, at most two decimal places, up to 1,000,000. Missing or invalid values return
400 `ApprovalRequired`. Omitting `confirmationToken` returns a raw 200 preview
(`status`, `quotedPostageAmount`, `maximumPostageAmount`, `currency`, `expiresAt`,
`confirmationToken`). After explicit approval, resubmit the same shipment and ceiling
with the token and a unique per-purchase `Idempotency-Key` header. The token expires
after five minutes. A successful purchase returns the raw 201 label, including
`labelId`, `trackingNumber`, `carrierCode`, and `labelData`, without a `data` wrapper.

Keep the same key and request across retries. An uncertain outcome needs reconciliation;
do not start another purchase with a new key. An expired approval returns 409
`ApprovalExpired`; obtain and explicitly approve a fresh preview when no purchase is
unresolved. The ceiling bounds postage authorization, not later adjustments or separate
fees. Sandbox execution bypasses approval; batch, return, and raw carrier routes have
separate contracts. The SDK never automatically confirms a preview.

## Authentication

### API key (recommended)

```php
$client = new FlexOps([
    'apiKey'      => 'live_...',
    'workspaceId' => 'your-workspace-id',
]);
```

### Email / password

```php
$client = new FlexOps(['baseUrl' => 'https://gateway.flexops.io']);
$client->auth->login('user@example.com', 'password'); // the client now sends data.token as its bearer token
$client->workspaceId = 'your-workspace-id';
```

## Sandbox / test keys

Use `test_...` (instead of `live_...`) to route to the sandbox environment. Mock carriers respond, nothing hits real carrier APIs, no charges, no real labels. Perfect for CI and integration tests.

```php
$client = new FlexOps([
    'apiKey'      => 'test_...',
    'workspaceId' => 'your-workspace-id',
]);
```

## Carrier-specific calls

`$client->carriers` (USPS, UPS, FedEx, DHL) goes through the Gateway's legacy ApiProxy route,
which is sunset on 2027-06-30.

- Lookups (GET), such as the ones below, work with an API key.
- The writes (rates by POST, pickups, voids, freight, documents) are `@deprecated` and need a user
  token (`$client->auth->login()`): API keys get 403 `API_KEY_SCOPE_REQUIRED`. They stop working when
  the proxy is retired.
- The proxy's label-generation routes are closed (410), so the SDK no longer has them.

Native replacements: `shipping->getRates()`, `createLabel()`, `cancelLabel()`, `validateAddress()`
and `track()`; `pickups->schedule()`, `get()` and `cancel()`; `scanForms->create()`; and
`hsCodes->estimateLandedCost()`.

```php
// USPS city/state for a ZIP code
$place = $client->carriers->usps->cityStateLookup('10001');

// UPS tracking
$info = $client->carriers->ups->track(['trackingNumber' => '1Z999AA10123456784']);

// DHL service points
$points = $client->carriers->dhl->findServicePoints(['postalCode' => '10001', 'countryCode' => 'US']);
```

## Address validation

```php
$result = $client->shipping->validateAddress([
    'line1' => '123 Main St', 'city' => 'New York', 'state' => 'NY', 'postalCode' => '10001', 'country' => 'US',
]);
if (!$result['isValid'] && $result['suggestedAddress']) {
    // Offer $result['suggestedAddress'] to the user.
}
```

The result is bare JSON (no `data` envelope): `isValid`, `suggestedAddress`, `isResidential`,
`warnings`, `validatedBy`, `validationUnavailable` and `checkedAt`.

## Workspace members

```php
use FlexOps\WorkspaceRole;

$client->workspaces->inviteMember('teammate@example.com', WorkspaceRole::Admin); // or 'Admin'
$result = $client->workspaces->listMembers();
foreach ($result['data']['members'] as $member) {
    echo $member['email'], ' ', WorkspaceRole::from($member['role'])->name, PHP_EOL;
}
```

Roles travel as the Gateway numbers (Owner 0, Admin 1, Member 2, Merchant 3). The member
calls accept a `WorkspaceRole`, its number, or its name. `listMembers()` returns
`data.members`, `data.pendingInvites`, `data.userLimit`, `data.activeCount` and `data.canInviteMore`.

## Retries and idempotency

The client retries a request up to three times with exponential backoff, only where that is safe:

- Every POST, PUT and PATCH sends an `Idempotency-Key` header: the key you pass (`createLabel($request, $key)`, or `$idempotencyKey` on `insurance->purchase()` and `carbonOffsets->purchase()`; store it so a retry your app makes after an error or a timeout reuses it), else one generated for the call and reused on each of its retries. The Gateway replays a write it already finished instead of running it again.
- GET retries on a network error, a timeout, 429, 500, 502, 503 and 504.
- POST, PUT and PATCH retry on a network error, a timeout, 429, 502, 503, 504, and 409 `IDEMPOTENCY_CONFLICT` (the first attempt is still running). They never retry a 500: the Gateway does not store a 500 response, so a retry would run the write, and any charge, again.
- DELETE retries only on 429.

A key reused with a different request gets 422 `IDEMPOTENCY_KEY_REUSED`, which is not retried.
A successful response with no body (204) returns `null`.

## Webhook subscriptions

```php
use FlexOps\WebhookEventType;

// Leave 'events' out to receive every event.
$webhook = $client->webhooks->create([
    'url'    => 'https://your-app.com/webhooks/flexops',
    'events' => [WebhookEventType::LabelCreated, WebhookEventType::LabelCanceled, WebhookEventType::BatchCompleted],
]);
$signingSecret = $webhook['data']['secret']; // "whsec_...", returned only on create; store it

// 'events' replaces the subscribed events; leave it out to keep them.
$client->webhooks->update($webhook['data']['id'], ['events' => [WebhookEventType::LabelCreated], 'status' => 'Active']);
```

FlexOps sends these events, named exactly as below. Names are PascalCase and matched exactly, so a name like `label.created` would never match. `create()` and `update()` send `events` as the Gateway's comma-separated `eventTypes`, and throw `InvalidArgumentException`, without calling the API, for an empty list or a name FlexOps never sends.

The Gateway answers `create` with the bare subscription (`201 Created`, no envelope); `create()` returns it as `['success' => true, 'data' => $subscription]` like the other webhook calls, and keeps the subscription fields (`id`, `secret`, ...) at the top level too.

| Event | Sent when |
|---|---|
| `LabelCreated` | A live label is purchased (sandbox labels send nothing) |
| `LabelCanceled` | A live label is voided |
| `BatchCompleted` | A batch label job finishes |
| `TrackingUpdated` | A USPS tracking scan arrives (USPS push-tracking pilot workspaces only) |
| `TrackingDelivered` | A USPS package is delivered (USPS push-tracking pilot workspaces only) |

## Webhook verification

FlexOps signs each delivery with HMAC-SHA256 over `{X-FlexOps-Timestamp}.{raw body}`, keyed with your subscription secret (`whsec_...`, used as-is), and sends it as `X-FlexOps-Signature: sha256=<hex>`. Verify the raw body before you decode it.

```php
use FlexOps\Resources\WebhooksResource;

$payload = file_get_contents('php://input');

$valid = WebhooksResource::verifySignature(
    payload:   $payload,
    signature: $_SERVER['HTTP_X_FLEXOPS_SIGNATURE'] ?? null, // "sha256=..."
    timestamp: $_SERVER['HTTP_X_FLEXOPS_TIMESTAMP'] ?? null, // Unix seconds
    secret:    (string) getenv('FLEXOPS_WEBHOOK_SECRET'),   // "whsec_..."
);
if (!$valid) {
    http_response_code(401);
    exit;
}

// The body is the event data itself (no type/data envelope).
// The event name is in a header; test events send "test".
$data = json_decode($payload, true);
switch ($_SERVER['HTTP_X_FLEXOPS_EVENT'] ?? '') {
    case 'test':
        // $data: ['type' => 'test', 'message' => '...', 'timestamp' => '...', 'subscriptionId' => '...']
        break;
    case 'TrackingUpdated':
        // $data['trackingNumber'], $data['status'], ...
        break;
}
http_response_code(200);
```

Deliveries whose timestamp is more than 300 seconds from your clock (either way) are rejected to limit replays; pass `tolerance:` (seconds) to change that. `WebhooksResource::verifySignatureOrFail()` takes the same arguments and throws `FlexOps\FlexOpsWebhookSignatureError` instead of returning `false`; its `errorCode` says why (`WEBHOOK_SIGNATURE_MISMATCH`, `WEBHOOK_TIMESTAMP_OUTSIDE_TOLERANCE`, `WEBHOOK_INVALID_TIMESTAMP`, `WEBHOOK_MISSING_SIGNATURE`, `WEBHOOK_MISSING_SECRET`).

## Curl quickstart

Every SDK method is a thin wrapper around the FlexOps REST API. If you want to verify the API before committing to the SDK — or you're integrating from a language we don't ship a SDK for — these curl invocations hit the same endpoints:

```bash
# Shop rates across all connected carriers
curl -X POST https://gateway.flexops.io/api/shipping/rates \
  -H "X-API-Key: live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "origin": {"addressLine1": "123 Main St", "city": "New York", "stateProvince": "NY", "postalCode": "10001", "countryCode": "US"},
    "destination": {"addressLine1": "456 Oak Ave", "city": "Los Angeles", "stateProvince": "CA", "postalCode": "90210", "countryCode": "US"},
    "package": {"weight": 16, "weightUnit": "oz"}
  }'

# Preview a live label; this does not purchase it.
# hazmatClassification 0 = not hazardous; required when there is no orderId.
curl -X POST https://gateway.flexops.io/api/shipping/labels \
  -H "X-API-Key: live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "carrierCode": "USPS", "serviceCode": "GROUND_ADVANTAGE",
    "origin": {"name": "Warehouse", "addressLine1": "123 Main St", "city": "New York", "stateProvince": "NY", "postalCode": "10001", "countryCode": "US"},
    "destination": {"name": "Customer", "addressLine1": "456 Oak Ave", "city": "Los Angeles", "stateProvince": "CA", "postalCode": "90210", "countryCode": "US"},
    "package": {"weight": 16, "weightUnit": "oz"},
    "maximumPostageAmount": 10.25,
    "hazmatClassification": 0
  }'

# After explicit approval, resend the same body with confirmationToken from
# the preview and an Idempotency-Key header unique to this purchase.
# See the SDK example above; retain that key for retries.

# Track a shipment
curl https://gateway.flexops.io/api/shipping/track/9400111899223456789012 \
  -H "X-API-Key: live_..."

# Cancel (void) a label
curl -X DELETE "https://gateway.flexops.io/api/shipping/labels/{labelId}?carrierCode=USPS" \
  -H "X-API-Key: live_..."
```

Use a `test_...` key instead of `live_...` to hit the sandbox environment; mock carriers respond, no real charges, no real labels.

## Resources

| Resource | Description |
|----------|-------------|
| `$client->auth` | Login, token refresh and logout (for user-token calls) |
| `$client->workspaces` | Workspaces and members |
| `$client->shipping` | Rate shopping, labels, multi-package labels, tracking, address validation, batch, cancel |
| `$client->carriers` | Read-only USPS, UPS and DHL lookups |
| `$client->webhooks` | Subscriptions, test events, delivery logs, signature verification |
| `$client->wallet` | Workspace wallet balance |
| `$client->insurance` | Quotes, policies, claims |
| `$client->returns` | RMAs (create, read, list), return labels, receiving, received returns |
| `$client->analytics` | Shipments, orders, carrier performance |
| `$client->orders` | Order lookups |
| `$client->inventory` | Warehouse inventory lookups |
| `$client->pickups` | Carrier pickup scheduling |
| `$client->scanForms` | USPS scan forms |
| `$client->rules` | Shipping automation rules, dry-run tests |
| `$client->carbonOffsets` | Carbon offset quotes, purchases and settings |
| `$client->hsCodes` | HS code search and landed-cost estimates |
| `$client->recurringShipments` | Scheduled recurring shipments |
| `$client->reportSchedules` | Scheduled report delivery by email |
| `$client->emailTemplates` | Branded shipment-tracking email templates |

API keys, wallet funding and account management live in the FlexOps portals; 2.0 removed them
from the SDK. See the [CHANGELOG](CHANGELOG.md) for the full list.

### Multi-package labels

One order shipped in several boxes with the same carrier and service; each box gets its own label and a
box that fails does not stop the others. Multi-package labels cannot carry HAZMAT: send
`HazmatClassification::None` or leave `hazmatClassification` out. `maximumPostageAmount` is required: the most
the whole shipment may cost in USD (positive, at most two decimals). The Gateway refuses to buy a box that would
take the total above it (`PriceCeilingExceeded`; once the amount is used up the remaining boxes report
`PriceCeilingReached`), and a request without a valid ceiling is 400 `ApprovalRequired`.

```php
use FlexOps\FlexOpsError;
use FlexOps\HazmatClassification;

try {
    $shipment = $client->shipping->createMultiPackage([
        'orderId' => 12345,
        'carrierCode' => 'USPS',
        'serviceCode' => 'PRIORITY',
        'hazmatClassification' => HazmatClassification::None,
        'maximumPostageAmount' => 25.00, // required: the most the whole shipment may cost, in USD
        'packages' => [
            ['weightInOunces' => 16, 'lengthInInches' => 10, 'widthInInches' => 8, 'heightInInches' => 4, 'packageReference' => 'Box 1 of 2'],
            ['weightInOunces' => 8, 'lengthInInches' => 6, 'widthInInches' => 6, 'heightInInches' => 6, 'packageReference' => 'Box 2 of 2'],
        ],
    ], 'order-12345-boxes'); // optional Idempotency-Key; reuse it when you retry
} catch (FlexOpsError $e) {
    // No label was bought: 422 AllPackagesFailed, or 409 OutcomeUnknown when a label may exist
    // (check the order before buying again). $e->data holds the per-box results.
}
```

### Returns

`returns->create()` opens an RMA; `getRma()` and `listRmas()` read RMAs. `returns->list()` and `get()` read
*received* returns, and `get()` takes the numeric return ID, not an RMA number.

### Recurring shipments, report schedules and email templates

```php
use FlexOps\RecurrenceInterval;
use FlexOps\ReportFormat;
use FlexOps\ReportFrequency;
use FlexOps\TrackingEmailEvent;

$schedule = $client->recurringShipments->create([
    'name' => 'Weekly restock', 'interval' => RecurrenceInterval::Weekly,
    'presetId' => $presetId, 'addressId' => $addressId, 'firstRunUtc' => '2026-11-02T15:00:00Z',
]); // bare JSON: $schedule['id'], $schedule['nextRunUtc'], ...

$report = $client->reportSchedules->create([
    'reportType' => 'shipments', 'format' => ReportFormat::Csv, 'frequencyType' => ReportFrequency::Weekly,
    'dayOfWeek' => 1, 'timeOfDayUtc' => '08:00:00', 'recipientEmails' => 'ops@example.com',
]); // envelope: $report['data']['id']

$client->emailTemplates->upsert([
    'eventType' => TrackingEmailEvent::Delivered,
    'subjectTemplate' => 'Delivered: {{tracking_number}}', 'htmlBodyTemplate' => '<p>Your order arrived.</p>',
]);
```

Reads work with any API key or a user token. Writes need a user token or an API key granted the
resource's opt-in scope (`recurring-shipments:write`, `report-schedules:write`,
`email-templates:write`); other keys get 403 `API_KEY_SCOPE_REQUIRED` with `requiredScopes`.

## Configuration

```php
$client = new FlexOps([
    'baseUrl'     => 'https://gateway.flexops.io', // API base URL
    'apiKey'      => 'live_...',               // API key auth
    'workspaceId' => 'your-workspace-id',      // Default workspace
    'timeout'     => 30,                       // Request timeout (seconds)
]);
```

## Requirements

- PHP 8.1+
- `ext-curl`
- `ext-json`

## License

MIT © FlexOps, LLC. See [LICENSE](LICENSE) for full text.
