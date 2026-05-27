# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`flexops/sdk` is the **official hand-crafted PHP SDK** for the FlexOps Platform. It targets the FlexOps **Gateway BFF**. Published to Packagist as `flexops/sdk`; current tag `v1.0.1`. PHP 8.1+; requires `ext-curl` and `ext-json`.

> **Gateway-targeted, not VSCS-targeted.** All hand-crafted SDKs in this family (.NET, Node, Python, Go, PHP, Ruby) hit Gateway. The Java SDK is the lone exception — it was auto-generated against VisionSuiteCoreServices and is archived as of 2026-03-08.

## Build & Run Commands

```bash
composer install                    # Install dependencies
vendor/bin/phpunit                  # Run tests
vendor/bin/phpunit --configuration phpunit.xml
```

## Architecture

```text
Customer PHP app  →  flexops/sdk (this repo)  →  Gateway BFF (gateway.flexops.io)
                                                 ↓
                                                 VSCS / Integrations / etc.
```

## Key Directories

| Path | Purpose |
|---|---|
| `src/` | PHP source — client, models, error classes |
| `tests/` | PHPUnit specs |
| `composer.json` | Package metadata + dependencies |
| `phpunit.xml` | PHPUnit config |
| `CHANGELOG.md` | Per-release notes |

## Conventions

- **PHP ≥ 8.1** (modern type hints, readonly properties, enums where natural).
- HTTP transport is `ext-curl` directly — no Guzzle dependency. Keep it that way: small dep surface is a deliberate design choice for an SDK that gets bundled into wildly different host apps.
- Errors come back as typed exception classes carrying Gateway's error envelope — don't throw plain `\Exception`.
- PHPUnit `^10.5 || ^11.0`.

## Publish

Packagist auto-updates on GitHub tag push (webhook integration). Bump version in `composer.json` if you maintain one there + `CHANGELOG.md`, tag (`vX.Y.Z`), push the tag — Packagist picks it up.

## Related Repositories

| Repository | Purpose |
|---|---|
| **This repo** | `flexops/sdk` on Packagist — `BillEisenman/flexops-sdk-php` |
| FlexOps Gateway | The HTTP API this SDK calls — `BillEisenman/FlexOpsGateway` |
| Sibling SDKs | `FlexOps.Sdk` (.NET), `@flexops/sdk` (Node), `flexops` (Python/Ruby), `flexops-sdk-go` (Go) |
| FlexOps Developer Docs | Hosts the SDK page — `BillEisenman/FlexOpsDeveloperDocs` |
