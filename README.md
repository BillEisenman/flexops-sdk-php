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

// API key authentication (recommended for server-to-server)
$client = new FlexOps([
    'apiKey'      => 'fxk_live_...',
    'workspaceId' => 'ws_abc123',
]);

// Get shipping rates from all carriers
$rates = $client->shipping->getRates([
    'fromAddress' => ['street1' => '123 Main St', 'city' => 'New York',    'state' => 'NY', 'zip' => '10001', 'country' => 'US'],
    'toAddress'   => ['street1' => '456 Oak Ave', 'city' => 'Los Angeles', 'state' => 'CA', 'zip' => '90210', 'country' => 'US'],
    'parcel'      => ['weight' => 16, 'weightUnit' => 'oz'],
]);

// Create a label with the cheapest rate
$cheapest = $rates['data'][0];  // rates are returned sorted by total cost
$label = $client->shipping->createLabel([
    'carrier'     => $cheapest['carrier'],
    'service'     => $cheapest['service'],
    'fromAddress' => ['name' => 'Warehouse', 'street1' => '123 Main St', 'city' => 'New York',    'state' => 'NY', 'zip' => '10001', 'country' => 'US'],
    'toAddress'   => ['name' => 'Customer',  'street1' => '456 Oak Ave', 'city' => 'Los Angeles', 'state' => 'CA', 'zip' => '90210', 'country' => 'US'],
    'parcel'      => ['weight' => 16, 'weightUnit' => 'oz'],
]);

echo "Label URL: {$label['data']['labelUrl']}\n";
echo "Tracking:  {$label['data']['trackingNumber']}\n";

// Track a shipment
$info = $client->shipping->track('9400111899223456789012');
```

## Authentication

### API key (recommended)

```php
$client = new FlexOps([
    'apiKey'      => 'fxk_live_...',
    'workspaceId' => 'ws_abc123',
]);
```

### Email / password

```php
$client = new FlexOps(['baseUrl' => 'https://gateway.flexops.io']);
$client->auth->login('user@example.com', 'password');
$client->workspaceId = 'ws_abc123';
```

## Sandbox / test keys

Use `fxk_test_...` (instead of `fxk_live_...`) to route to the sandbox environment. Mock carriers respond, nothing hits real carrier APIs, no charges, no real labels. Perfect for CI and integration tests.

```php
$client = new FlexOps([
    'apiKey'      => 'fxk_test_...',
    'workspaceId' => 'ws_abc123',
]);
```

## Direct carrier operations

Access carrier-specific endpoints when you need full control:

```php
// USPS domestic label
$label = $client->carriers->usps->createDomesticLabel([
    'imageType'      => 'PDF',
    'mailClass'      => 'PRIORITY_MAIL',
    'weightInOunces' => 16,
]);

// FedEx rate quote
$rates = $client->carriers->fedex->getRates([...]);

// UPS tracking
$info = $client->carriers->ups->track(['trackingNumber' => '1Z999AA10123456784']);

// DHL shipment
$shipment = $client->carriers->dhl->createShipment([...]);
```

## Webhook verification

```php
use FlexOps\Resources\WebhooksResource;

$valid = WebhooksResource::verifySignature(
    payload:   file_get_contents('php://input'),
    signature: $_SERVER['HTTP_X_FLEXOPS_SIGNATURE'],
    secret:    'whsec_...'
);
```

## Curl quickstart

Every SDK method is a thin wrapper around the FlexOps REST API. If you want to verify the API before committing to the SDK — or you're integrating from a language we don't ship a SDK for — these curl invocations hit the same endpoints:

```bash
# Shop rates across all connected carriers
curl -X POST https://gateway.flexops.io/api/workspaces/ws_abc123/shipping/rates \
  -H "X-API-Key: fxk_live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "fromAddress": {"street1": "123 Main St", "city": "New York", "state": "NY", "zip": "10001", "country": "US"},
    "toAddress":   {"street1": "456 Oak Ave", "city": "Los Angeles", "state": "CA", "zip": "90210", "country": "US"},
    "parcel":      {"weight": 16, "weightUnit": "oz"}
  }'

# Create a label
curl -X POST https://gateway.flexops.io/api/workspaces/ws_abc123/shipping/labels \
  -H "X-API-Key: fxk_live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "carrier":  "USPS",
    "service":  "PRIORITY_MAIL",
    "fromAddress": {"name": "Warehouse", "street1": "123 Main St", "city": "New York", "state": "NY", "zip": "10001", "country": "US"},
    "toAddress":   {"name": "Customer",  "street1": "456 Oak Ave", "city": "Los Angeles", "state": "CA", "zip": "90210", "country": "US"},
    "parcel":   {"weight": 16, "weightUnit": "oz"}
  }'

# Track a shipment
curl https://gateway.flexops.io/api/workspaces/ws_abc123/shipping/track/9400111899223456789012 \
  -H "X-API-Key: fxk_live_..."

# Cancel a label (via the unified carrier-agnostic endpoint)
curl -X DELETE https://gateway.flexops.io/api/v3.0/shipping/Usps/cancel/9400111899223456789012 \
  -H "X-API-Key: fxk_live_..."
```

Use an `fxk_test_...` key instead of `fxk_live_...` to hit the sandbox environment; mock carriers respond, no real charges, no real labels.

## Resources

| Resource | Description |
|----------|-------------|
| `$client->auth` | Login, register, password management |
| `$client->workspaces` | Workspace CRUD, membership, branding |
| `$client->shipping` | Rate shopping, labels, tracking, batch, cancel |
| `$client->carriers` | USPS, UPS, FedEx, DHL direct endpoints |
| `$client->webhooks` | Subscription CRUD, signature verification, delivery logs |
| `$client->wallet` | Balance, transactions, auto-reload |
| `$client->insurance` | Quotes, purchase, claims (first-party + U-PIC) |
| `$client->returns` | RMA lifecycle: create, batch, QR codes, photo upload, cost recovery |
| `$client->apiKeys` | Key creation, rotation, revocation |
| `$client->analytics` | Shipments, orders, carrier performance |
| `$client->orders` | Order management |
| `$client->inventory` | Warehouse inventory |
| `$client->pickups` | Carrier pickup scheduling |
| `$client->scanForms` | USPS scan forms |
| `$client->rules` | Shipping automation rules |
| `$client->offsets` | Carbon offset purchases |
| `$client->hsCodes` | HS code lookup for international customs |
| `$client->recurringShipments` | Scheduled recurring shipments |
| `$client->emailTemplates` | Branded post-purchase email templates |
| `$client->reports` | Report generation and scheduled delivery |

## Configuration

```php
$client = new FlexOps([
    'baseUrl'     => 'https://gateway.flexops.io', // API base URL
    'apiKey'      => 'fxk_live_...',           // API key auth
    'workspaceId' => 'ws_abc123',              // Default workspace
    'timeout'     => 30,                       // Request timeout (seconds)
]);
```

## Requirements

- PHP 8.1+
- `ext-curl`
- `ext-json`

## License

Proprietary — FlexOps, LLC
