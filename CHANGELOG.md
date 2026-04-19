# Changelog

All notable changes to the FlexOps PHP SDK are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
