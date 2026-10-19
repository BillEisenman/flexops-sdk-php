# Changelog

All notable changes to the FlexOps PHP SDK are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - Unreleased

2.0 exposes only the documented public Gateway API, fixes the calls that stay to match the
Gateway's routes, bodies and numeric enums, and makes retries safe for purchases.
Not tagged yet; the version comes from the `v2.0.0` tag.

### Removed (BREAKING)
- `$client->apiKeys`: API keys are managed in the FlexOps portals.
- `$client->offsets` (`offset`, `getEmissions`, `batchOffset`): those per-label routes never existed. Use `$client->carbonOffsets`.
- `wallet->addFunds()`, `wallet->listTransactions()` and `wallet->configureAutoReload()`.
- `auth->register()`, `getProfile()`, `updateProfile()`, `changePassword()`, `forgotPassword()`, `resetPassword()` and `verifyEmail()`. `login()`, `refreshToken()` and `logout()` stay.
- `workspaces->create()` and `workspaces->update()`.
- `returns->approve()`, `reject()`, `cancel()` and `processRefund()`.
- `webhooks->get()`: the Gateway has no single-subscription route. Use `webhooks->list()`.
- `orders->create()` and `orders->cancel()`, and `inventory->postAsnReceipt()` and `inventory->updateInventory()`: they wrote through the legacy ApiProxy route, which refuses API-key writes. Order and inventory reads stay.
- Carrier label generation through ApiProxy, which the Gateway closed (410 Gone): `carriers->usps->createDomesticLabel()`, `createReturnLabel()` and `createInternationalLabel()`, `carriers->ups->createLabel()`, `carriers->fedex->createShipment()` and `createReturnShipment()`, and `carriers->dhl->createShipment()`. Buy labels with `shipping->createLabel()` (and return labels with `returns->generateLabel()`).
- `carriers->dhl->updatePickup()`: the proxy has no PATCH route.

### Changed (BREAKING)
- `$client->recurringShipments`, `$client->reportSchedules` and `$client->emailTemplates` call the real Gateway routes; the 1.x paths and shapes never worked.
  - `recurringShipments`: `list()`, `get($id)`, `create($request)`, `update($id, $request)`, `delete($id)`, `pause($id)`, `resume($id)` and `trigger($id, $idempotencyKey = null)` on `recurring-shipments` (no trailing slash). Bare JSON; delete, pause, resume and trigger return `null` (204). `list()` takes no paging arguments any more.
  - `reportSchedules` (renamed from `reports`): `list()`, `get($id)`, `create($request)`, `update($id, $request)`, `delete($id)` and the new `toggle($id)` on `report-schedules`. Responses are the envelope; delete returns `null` (204).
  - `emailTemplates`: `list()`, `get($templateId)`, `upsert($request)` (PUT, keyed by `eventType`), `delete($templateId)` and `preview($subjectTemplate, $htmlBodyTemplate)` on `email-templates` (was `shipment-email-templates`). `create()` and `update()` are replaced by `upsert()`; `preview()` no longer takes a template id. Bare JSON.
  - Writes need a user token or an API key granted the new opt-in scope (`recurring-shipments:write`, `report-schedules:write`, `email-templates:write`); other keys get 403 `API_KEY_SCOPE_REQUIRED`.
- The carrier writes through the legacy ApiProxy route are `@deprecated` (the proxy is sunset 2027-06-30) and need a user token: API keys get 403 `API_KEY_SCOPE_REQUIRED`. Use `shipping`, `pickups` and `scanForms` where they cover the call. Carrier lookups (GET) still work with API keys.
- `shipping->validateAddress()` posts the flat Gateway AddressValidationRequest (`name`, `line1`, `line2`, `city`, `state`, `postalCode`, `country`) to the documented `/api/addresses/validate`, not the undocumented `/api/shipping/addresses/validate`. A legacy `street1`/`zip` address or a ShippingAddress (`addressLine1`/`stateProvince`/`countryCode`) is mapped. The result is the bare AddressValidationResult (`isValid`, `suggestedAddress`, `isResidential`, `warnings`, `validatedBy`, `validationUnavailable`, `checkedAt`).
- `wallet->getBalance()` calls `GET /api/wallet/my-balance` with `X-Workspace-Id: <workspaceId>` and returns the bare `{hasWallet, balanceCents, balance}`.
- `hsCodes` calls `/api/shipping/hs-codes/search`, `/api/shipping/hs-codes/{code}` and `/api/shipping/landed-cost`, without the workspace in the path (bare JSON responses). Landed cost takes `{destinationCountry, items[{description, quantity, value, weightOz, hsCode?, originCountry, skuOrProductId?}], shippingCost}`.
- `insurance->getQuote($carrierCode, $declaredValue, $provider = null, $trackingNumber = null)` sends `carrierCode` (was `carrier`); `purchase($trackingNumber, $carrierCode, $declaredValue, $provider = null, $coverageTier = null)` posts to `insurance/policies` (was `insurance/purchase`); `fileClaim($policyId, $claimReason, $claimAmount, $evidenceJson = null)` posts `{policyId, claimReason, claimAmount}` to `insurance/claims` (was `policies/{id}/claims` with `description`).
- `returns->create()` posts the Gateway CreateRmaRequest to `returns/rma` (`orderId` is a number; `reason` and `items[{sku, quantity, reason?}]` are required).
- `returns->markReceived($rmaId, $rmaNumber, $inspectedItems, $warehouseId = null)` posts `{rmaNumber, warehouseId?, inspectedItems[{partNumber, quantity, disposition 0|1|2, ...}]}` (was `($returnId, $items)` sending `items`).
- `returns->list($page, $pageSize, $startDate, $endDate)` filters by date; the Gateway has no `status` filter, so that argument is gone.
- `returns->generateLabel()` names its first argument `$rmaNumber` and documents `rateIndicator`; `carrierCode` stays optional (the Gateway defaults it to `usps`).
- `rules->reorder($rules)` posts `{rules: [{ruleId, priority}]}` to `shipping-rules/reorder` (was a PUT of bare ids).
- `pickups->list()` reads `pickups/scheduled`; `pickups->get($id, $carrierCode = 'usps')` and `pickups->cancel($id, $carrierCode = 'usps')` send the carrier code the Gateway needs.
- `workspaces->inviteMember($email, $role)` and `updateMemberRole($userId, $role)` send the role as the Gateway number (`role`, and `newRole` for a role change; `updateMemberRole` sent `role`). `$role` is a `FlexOps\WorkspaceRole`, its number, or its name; an unknown role throws `InvalidArgumentException` without a request. `inviteMember()` now requires the role: the Gateway reads a missing role as Owner.
- `auth->login()` and `auth->refreshToken()` make the client send the returned `data.token` as its bearer token. `refreshToken($refreshToken, $accessToken = null)` posts `{token, refreshToken}` as the Gateway requires; the access token defaults to the client's, and with neither it throws `FlexOpsAuthError` without a request.
- `workspaces->listMembers()` is documented as returning `data.members` (with `pendingInvites`, `userLimit`, `activeCount`, `canInviteMore`), not a bare member list.

### Added
- `$client->carbonOffsets`: `quote($weightLbs, $distanceMiles)`, `purchase($trackingNumber, $weightLbs, $distanceMiles)`, `list($page, $pageSize)`, `get($offsetId)`, `summary()`, `getSettings()` and `updateSettings($autoOffsetEnabled)`.
- `rules->test($shipment)` dry-runs the shipping rules (`POST shipping-rules/test`).
- `webhooks->test($webhookId)` sends a test event (`POST webhooks/{id}/test`).
- `FlexOps\WorkspaceRole` (int-backed enum: Owner 0, Admin 1, Member 2, Merchant 3).
- `FlexOps\RecurrenceInterval`, `FlexOps\ReportFormat`, `FlexOps\ReportFrequency` and `FlexOps\TrackingEmailEvent` (int-backed enums, the Gateway numbers).
- A contract test calls every public SDK method and checks each request against the documented Gateway operations (`tests/fixtures/gateway-operations.json`); only the listed deprecated carrier methods may write through ApiProxy, never on a closed label route or with PATCH.

### Retries (BREAKING for callers relying on retried writes)
- Every POST, PUT and PATCH sends an `Idempotency-Key` header: the caller's (`shipping->createLabel($request, $key)`), else one random UUID per call, reused on each of its retries. The Gateway replays a write it already finished instead of running it again.
- POST, PUT and PATCH no longer retry a 500 (the Gateway does not store 500 responses, so a retry ran the write, and any charge, again). They retry on a network error or timeout, 429, 502, 503, 504, and 409 `IDEMPOTENCY_CONFLICT` (the first request with that key is still running). A key reused with a different request gets 422 `IDEMPOTENCY_KEY_REUSED`, and the PascalCase 409 `IdempotencyConflict` of label purchase and managed returns means the inputs changed; neither is retried.
- DELETE retries only on 429, never after a dropped connection or a 5xx.
- GET retries as before (network error, 429, 500, 502, 503, 504).
- A 2xx with no body (such as 204) returns `null` instead of an empty string.
- Header values containing CR or LF are refused with `InvalidArgumentException` before any request.

### Added (since 1.1.0)
- `FlexOps\WebhookEventType` (string-backed enum) with the event names FlexOps sends and matches subscriptions on: `LabelCreated`, `LabelCanceled`, `BatchCompleted`, and, for USPS push-tracking pilot workspaces, `TrackingUpdated` and `TrackingDelivered`. The README has a subscription sample and the event table.
- `FlexOps\HazmatClassification` (int-backed enum, the Gateway numbers; `json_encode` writes the number). Put `'hazmatClassification' => HazmatClassification::None` (or a class) in a label or rate request; it is sent only when you set it, and the SDK never defaults it. Labels without an `orderId` must declare it.
- `returns->generateLabel($returnId, $hazmatClassification)` sends a declared HAZMAT class with a return label.
- `returns->generateLabel($returnId, [...])` also takes an options array with the package (`weightOz`, `lengthIn`, `widthIn`, `heightIn`) plus `carrierCode`, `serviceType`, `paymentConfirmed` and `hazmatClassification`. The Gateway needs the package: a workspace with no default shipping preset gets a 400 (`PackageMeasurementsRequired`) naming the missing fields when it is left out (Gateway #672).

### Fixed (since 1.1.0)
- **BREAKING**: webhook subscriptions now subscribe to the events you name (flexops-sdk-node#43, flexops-sdk-python#8). `webhooks->create()` and `webhooks->update()` sent the request array as-is, so an `events` list reached the Gateway as a field it ignores and every subscription received every event. They now send `events` (a list of `WebhookEventType` cases or their exact names) as the Gateway's comma-separated `eventTypes`. Both throw `InvalidArgumentException`, without calling the API, when `events` is empty, is not a list, names an event FlexOps never sends (such as `label.created`, which never matched anything), or is given together with `eventTypes`. Leave `events` out to receive every event on create, or to keep the current events on update. An explicit `eventTypes` string is still sent as-is, unchecked.
- `webhooks->create()` returns `['success' => true, 'data' => $subscription]`, so `$result['data']['secret']` holds the signing secret. The Gateway create route answers `201` with the bare subscription, not the envelope the other webhook routes use, so `$result['data']` was missing and code following that shape lost the secret (flexops-sdk-dotnet#10). The subscription fields stay at the top level as before, and an already wrapped body is returned unchanged.
- **BREAKING**: webhook signature verification now matches what FlexOps sends (flexops-sdk-node#41). `WebhooksResource::verifySignature($payload, $signature, $secret)` hashed the body alone and compared bare hex, so it returned `false` for every real delivery. It is now `verifySignature(string $payload, ?string $signature, ?string $timestamp, string $secret, int $tolerance = 300, ?int $now = null): bool`; it signs `{X-FlexOps-Timestamp}.{raw body}` with HMAC-SHA256 keyed by the whole `whsec_...` secret, accepts the `sha256=` prefix, compares with `hash_equals()`, and rejects missing, non-numeric or out-of-tolerance timestamps. Three-argument calls now throw `ArgumentCountError`: add the `X-FlexOps-Timestamp` header value as the third argument (or pass `timestamp:` by name). The new `verifySignatureOrFail()` takes the same arguments and throws `FlexOps\FlexOpsWebhookSignatureError` (a `FlexOpsError` whose `errorCode` says why) instead of returning `false`. Header name constants (`WebhooksResource::SIGNATURE_HEADER`, `TIMESTAMP_HEADER`, `EVENT_HEADER`, ...) are added.
- The README webhook sample is now a complete handler. Webhook bodies have no `type`/`data` envelope; the event name is in `X-FlexOps-Event`.
- `returns->generateLabel($returnId)` sends `{}`; it sent no body, which the Gateway return-label route refuses with 400.
- `shipping->createLabel()` posts to the Gateway route `/api/shipping/labels`; it used a workspace-scoped path the Gateway does not serve.
- `shipping->track`, `cancelLabel`, `getCarriers`, `getRecommendations`, `predictDelivery` and `getSavings` use the Gateway `/api/shipping/...` routes instead of workspace-scoped paths the Gateway does not serve. `cancelLabel($labelId, $carrierCode)` now needs the carrier code the Gateway requires; without it the SDK throws `InvalidArgumentException` before any request.

## [1.1.0] - 2026-09-19

### Bounded label purchase support

- Single-label requests support maximumPostageAmount and confirmationToken.
- CreateLabel/create_label accepts a per-purchase idempotency key and preserves it on retries.
- Preview and purchase responses are raw objects, not success/data envelopes.
- Gateway errorCode is preserved on SDK errors.
- Register the existing error-class file in Composer's classmap so authentication and rate-limit errors autoload reliably.
- Read the README approval flow and migration notes before upgrading.

### Added
- Initial README with installation, quick start, authentication (API key and email/password), sandbox guidance, direct carrier operations, webhook verification, and a curl quickstart section.

## [1.0.0] - 2026-03-08

### Added
- Initial public release.
- `\FlexOps\FlexOps` entry point with API key and email/password authentication.
- 20 resource classes covering auth, workspaces, shipping, carriers, webhooks, wallet, insurance, returns, api keys, analytics, orders, inventory, pickups, scan forms, rules, offsets, HS codes, recurring shipments, email templates, and reports.
- Direct carrier access for USPS, UPS, FedEx, and DHL.
- PSR-4 autoloading.
- Requires PHP 8.1+ with `ext-curl` and `ext-json`.
