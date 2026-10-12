# Changelog

All notable changes to the FlexOps PHP SDK are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `FlexOps\HazmatClassification` (int-backed enum, the Gateway numbers; `json_encode` writes the number). Put `'hazmatClassification' => HazmatClassification::None` (or a class) in a label or rate request; it is sent only when you set it, and the SDK never defaults it. Labels without an `orderId` must declare it.
- `returns->generateLabel($returnId, $hazmatClassification)` sends a declared HAZMAT class with a return label.
- `returns->generateLabel($returnId, [...])` also takes an options array with the package (`weightOz`, `lengthIn`, `widthIn`, `heightIn`) plus `carrierCode`, `serviceType`, `paymentConfirmed` and `hazmatClassification`. The Gateway needs the package: a workspace with no default shipping preset gets a 400 (`PackageMeasurementsRequired`) naming the missing fields when it is left out (Gateway #672).

### Fixed
- `returns->generateLabel($returnId)` sends `{}`; it sent no body, which the Gateway return-label route refuses with 400.
- `shipping->createLabel()` posts to the Gateway route `/api/shipping/labels`; it used a workspace-scoped path the Gateway does not serve.
- `shipping->track`, `cancelLabel`, `getCarriers`, `getRecommendations`, `predictDelivery` and `getSavings` use the Gateway `/api/shipping/...` routes instead of workspace-scoped paths the Gateway does not serve. `cancelLabel($labelId, $carrierCode)` now needs the carrier code the Gateway requires; without it the SDK throws `InvalidArgumentException` before any request.
- `shipping->validateAddress()` posts the Gateway `{"address": {...}}` request to `/api/shipping/addresses/validate`, and maps a legacy `street1`/`state`/`zip` address to the Gateway field names.

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
