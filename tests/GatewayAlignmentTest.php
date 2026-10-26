<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\WorkspaceRole;
use PHPUnit\Framework\TestCase;

/** Routes, bodies and headers checked against the Gateway controllers and DTOs. */
class GatewayAlignmentTest extends TestCase
{
    private const WS = 'http://localhost/api/workspaces/ws-test-123';

    private static function call(array $request): array
    {
        return [$request['method'], $request['url'], $request['body']];
    }

    public function testWalletBalanceUsesMyBalanceWithTheWorkspaceHeader(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(['hasWallet' => true, 'balanceCents' => 1250, 'balance' => 12.5]);

        $balance = $client->wallet->getBalance();

        $this->assertSame(['hasWallet' => true, 'balanceCents' => 1250, 'balance' => 12.5], $balance);
        $this->assertSame(['GET', 'http://localhost/api/wallet/my-balance', null], self::call($mock->requests[0]));
        $this->assertSame('ws-test-123', MockHttpClient::header($mock->requests[0], 'X-Workspace-Id'));
    }

    public function testWalletBalanceRefusesAWorkspaceIdThatWouldInjectAHeader(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient(workspaceId: "ws\r\nX-Evil: 1");
        $this->expectException(\InvalidArgumentException::class);
        try {
            $client->wallet->getBalance();
        } finally {
            $this->assertCount(0, $mock->requests);
        }
    }

    public function testHsCodesAndLandedCostAreNotWorkspaceScoped(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $landed = ['destinationCountry' => 'CA', 'shippingCost' => 12.5, 'items' => [
            ['description' => 'Mug', 'quantity' => 2, 'value' => 20.0, 'weightOz' => 8, 'originCountry' => 'US'],
        ]];
        $client->hsCodes->search('mug', 'CA', 5);
        $client->hsCodes->lookup('6912.00');
        $client->hsCodes->estimateLandedCost($landed);

        $this->assertSame([
            ['GET', 'http://localhost/api/shipping/hs-codes/search?query=mug&destinationCountry=CA&maxResults=5', null],
            ['GET', 'http://localhost/api/shipping/hs-codes/6912.00', null],
            ['POST', 'http://localhost/api/shipping/landed-cost', $landed],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testInsuranceSendsTheGatewayRequests(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->insurance->getQuote('USPS', 100.0);
        $client->insurance->getQuote('UPS', 50.0, 'shipsurance', '1Z1');
        $client->insurance->purchase('9400 1', 'USPS', 100.0, null, 'standard');
        $client->insurance->fileClaim('pol-1', 'Lost in transit', 100.0);
        $client->insurance->void('pol-1');
        $client->insurance->getProviders();

        $this->assertSame([
            ['POST', self::WS . '/insurance/quote', ['declaredValue' => 100.0, 'carrierCode' => 'USPS']],
            ['POST', self::WS . '/insurance/quote', ['declaredValue' => 50.0, 'carrierCode' => 'UPS', 'trackingNumber' => '1Z1', 'provider' => 'shipsurance']],
            ['POST', self::WS . '/insurance/policies', ['trackingNumber' => '9400 1', 'carrierCode' => 'USPS', 'declaredValue' => 100.0, 'coverageTier' => 'standard']],
            ['POST', self::WS . '/insurance/claims', ['policyId' => 'pol-1', 'claimReason' => 'Lost in transit', 'claimAmount' => 100.0]],
            ['DELETE', self::WS . '/insurance/policies/pol-1', null],
            ['GET', self::WS . '/insurance/providers', null],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testReturnsUseTheRmaRoutes(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $rma = ['orderId' => 1001, 'reason' => 'Damaged', 'items' => [['sku' => 'MUG-1', 'quantity' => 1]]];
        $inspected = [['partNumber' => 'MUG-1', 'quantity' => 1, 'disposition' => 0]];
        $client->returns->create($rma);
        $client->returns->generateLabel('RMA-1', ['carrierCode' => 'usps', 'rateIndicator' => 'SP', 'weightOz' => 16]);
        $client->returns->markReceived(42, 'RMA-1', $inspected);
        $client->returns->markReceived(42, 'RMA-1', $inspected, 3);

        $this->assertSame([
            ['POST', self::WS . '/returns/rma', $rma],
            ['POST', self::WS . '/returns/RMA-1/label', ['carrierCode' => 'usps', 'rateIndicator' => 'SP', 'weightOz' => 16]],
            ['POST', self::WS . '/returns/42/receive', ['rmaNumber' => 'RMA-1', 'inspectedItems' => $inspected]],
            ['POST', self::WS . '/returns/42/receive', ['rmaNumber' => 'RMA-1', 'warehouseId' => 3, 'inspectedItems' => $inspected]],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testRulesReorderAndTest(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $order = [['ruleId' => 'r-2', 'priority' => 0], ['ruleId' => 'r-1', 'priority' => 1]];
        $shipment = ['weightOz' => 16, 'declaredValue' => 20, 'destinationCountry' => 'US'];
        $client->rules->reorder($order);
        $client->rules->test($shipment);

        $this->assertSame([
            ['POST', self::WS . '/shipping-rules/reorder', ['rules' => $order]],
            ['POST', self::WS . '/shipping-rules/test', $shipment],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testScanFormCreateSendsTheGatewayFieldNamesAndTheCallersRequestId(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $request = ['trackingNumbers' => ['9400111899223456789014'], 'manifestDate' => '2026-09-08',
            'requestId' => 'req-1', 'originPostalCode' => '10001', 'warehouseId' => 7];

        $client->scanForms->create($request);

        $this->assertSame([['POST', self::WS . '/scan-forms', $request]], array_map(self::call(...), $mock->requests));
    }

    public function testScanFormCreateGeneratesARequestIdPerCall(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();

        $client->scanForms->create(['trackingNumbers' => ['9400'], 'manifestDate' => '2026-09-08']);
        $client->scanForms->create(['trackingNumbers' => ['9400'], 'manifestDate' => '2026-09-08', 'requestId' => null]);

        $ids = array_map(fn ($r) => $r['body']['requestId'] ?? null, $mock->requests);
        $this->assertCount(2, $ids);
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', (string) $id);
        }
        $this->assertNotSame($ids[0], $ids[1]);
        $this->assertSame(['trackingNumbers', 'manifestDate', 'requestId'], array_keys($mock->requests[0]['body']));
    }

    public function testPickupsUseTheScheduledListAndCarrierCode(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $pickup = ['carrierCode' => 'usps', 'pickupDate' => '2026-10-20', 'street' => '1 Main St', 'city' => 'Austin',
            'state' => 'TX', 'postalCode' => '78701', 'packageCount' => 2];
        $client->pickups->schedule($pickup);
        $client->pickups->list();
        $client->pickups->get('PU 1');
        $client->pickups->get('PU-2', 'ups');
        $client->pickups->cancel('PU-2', 'ups');

        $this->assertSame([
            ['POST', self::WS . '/pickups', $pickup],
            ['GET', self::WS . '/pickups/scheduled', null],
            ['GET', self::WS . '/pickups/PU%201?carrierCode=usps', null],
            ['GET', self::WS . '/pickups/PU-2?carrierCode=ups', null],
            ['DELETE', self::WS . '/pickups/PU-2?carrierCode=ups', null],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testMemberRolesAreSentAsGatewayNumbers(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->workspaces->inviteMember('a@example.com', 'admin');
        $client->workspaces->inviteMember('b@example.com', WorkspaceRole::Merchant);
        $client->workspaces->updateMemberRole('user-1', 'Member');
        $client->workspaces->updateMemberRole('user-1', 0);

        $this->assertSame([
            ['POST', self::WS . '/members/invite', ['email' => 'a@example.com', 'role' => 1]],
            ['POST', self::WS . '/members/invite', ['email' => 'b@example.com', 'role' => 3]],
            ['PUT', self::WS . '/members/user-1/role', ['newRole' => 2]],
            ['PUT', self::WS . '/members/user-1/role', ['newRole' => 0]],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testAnUnknownRoleIsRefusedWithoutARequest(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        foreach (['Viewer', 7] as $role) {
            try {
                $client->workspaces->inviteMember('a@example.com', $role);
                $this->fail("Expected role {$role} to be refused");
            } catch (\InvalidArgumentException) {
            }
        }
        $this->assertCount(0, $mock->requests);
    }

    public function testWorkspaceRoleValuesMatchTheGatewayEnum(): void
    {
        $this->assertSame(['Owner' => 0, 'Admin' => 1, 'Member' => 2, 'Merchant' => 3],
            array_column(array_map(fn ($c) => ['n' => $c->name, 'v' => $c->value], WorkspaceRole::cases()), 'v', 'n'));
    }

    public function testCarbonOffsets(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->carbonOffsets->quote(2.5, 300.0);
        $client->carbonOffsets->purchase('9400 1', 2.5, 300.0);
        $client->carbonOffsets->list();
        $client->carbonOffsets->list(2, 10);
        $client->carbonOffsets->get('off-1');
        $client->carbonOffsets->summary();
        $client->carbonOffsets->getSettings();
        $client->carbonOffsets->updateSettings(false);

        $this->assertSame([
            ['POST', self::WS . '/carbon-offsets/quote', ['weightLbs' => 2.5, 'distanceMiles' => 300.0]],
            ['POST', self::WS . '/carbon-offsets/purchase', ['trackingNumber' => '9400 1', 'weightLbs' => 2.5, 'distanceMiles' => 300.0]],
            ['GET', self::WS . '/carbon-offsets', null],
            ['GET', self::WS . '/carbon-offsets?page=2&pageSize=10', null],
            ['GET', self::WS . '/carbon-offsets/off-1', null],
            ['GET', self::WS . '/carbon-offsets/summary', null],
            ['GET', self::WS . '/carbon-offsets/settings', null],
            ['PUT', self::WS . '/carbon-offsets/settings', ['autoOffsetEnabled' => false]],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testWebhookTestEvent(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->webhooks->test('wh-1');

        $this->assertSame(['POST', self::WS . '/webhooks/wh-1/test', null], self::call($mock->requests[0]));
    }

    public function testLoginAndRefreshSendTheGatewayBodiesAndAdoptTheNewToken(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(['success' => true, 'data' => ['token' => 'at-1', 'refreshToken' => 'rt-1']])
            ->enqueueJson(['success' => true, 'data' => ['token' => 'at-2', 'refreshToken' => 'rt-2']]);

        $login = $client->auth->login('a@example.com', 'pw');
        $client->auth->refreshToken($login['data']['refreshToken']);
        $client->auth->logout();

        $this->assertSame([
            ['POST', 'http://localhost/api/account/login', ['email' => 'a@example.com', 'password' => 'pw']],
            ['POST', 'http://localhost/api/account/refresh-token', ['token' => 'at-1', 'refreshToken' => 'rt-1']],
            ['POST', 'http://localhost/api/account/logout', null],
        ], array_map(self::call(...), $mock->requests));
        // The login token replaces the API key; the refreshed token replaces the login token.
        $this->assertSame('Bearer at-1', MockHttpClient::header($mock->requests[1], 'Authorization'));
        $this->assertNull(MockHttpClient::header($mock->requests[1], 'X-Api-Key'));
        $this->assertSame('Bearer at-2', MockHttpClient::header($mock->requests[2], 'Authorization'));
    }

    public function testATwoFactorLoginKeepsTheCurrentCredentials(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(['success' => true, 'data' => ['requiresTwoFactor' => true, 'token' => null]]);

        $client->auth->login('a@example.com', 'pw');
        $client->webhooks->list();

        $this->assertSame('sk_test_key', MockHttpClient::header($mock->requests[1], 'X-Api-Key'));
    }

    public function testRefreshNeedsAnAccessToken(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        try {
            $client->auth->refreshToken('rt');
            $this->fail('Expected a refresh without an access token to be refused');
        } catch (\FlexOps\FlexOpsAuthError) {
        }
        $this->assertCount(0, $mock->requests);

        $client->auth->refreshToken('rt', 'expired-at');
        $this->assertSame(['token' => 'expired-at', 'refreshToken' => 'rt'], $mock->requests[0]['body']);
    }

    public function testReturnsListFiltersByDate(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->returns->list(2, 10, '2026-10-01', '2026-10-19');
        $client->returns->list();

        $this->assertSame(self::WS . '/returns?page=2&pageSize=10&startDate=2026-10-01&endDate=2026-10-19', $mock->requests[0]['url']);
        $this->assertSame(self::WS . '/returns', $mock->requests[1]['url']);
    }

    public function testRemovedPortalResourcesAreGone(): void
    {
        ['client' => $client] = TestHelper::createClient();
        // `reports` is now `reportSchedules`.
        foreach (['apiKeys', 'reports', 'offsets'] as $resource) {
            $this->assertFalse(property_exists($client, $resource), "{$resource} should be removed");
        }
        foreach (['addFunds', 'listTransactions', 'configureAutoReload'] as $method) {
            $this->assertFalse(method_exists($client->wallet, $method), "wallet->{$method} should be removed");
        }
        $this->assertFalse(method_exists($client->webhooks, 'get'));
        $this->assertFalse(method_exists($client->orders, 'create'));
    }

    /** The proxy answers 410 for its label routes and has no PATCH, so these carrier writes stay removed. */
    public function testClosedCarrierLabelRoutesStayRemoved(): void
    {
        ['client' => $client] = TestHelper::createClient();
        $closed = [
            'usps' => ['createDomesticLabel', 'createReturnLabel', 'createInternationalLabel'],
            'ups' => ['createLabel'],
            'fedex' => ['createShipment', 'createReturnShipment'],
            'dhl' => ['createShipment', 'updatePickup'],
        ];
        foreach ($closed as $carrier => $methods) {
            foreach ($methods as $method) {
                $this->assertFalse(method_exists($client->carriers->{$carrier}, $method), "carriers->{$carrier}->{$method} should stay removed");
            }
        }
    }

    public function testDeprecatedCarrierWritesUseTheLegacyProxyWithAnIdempotencyKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient(apiKey: null, accessToken: 'user-token');
        $client->carriers->usps->createPickup(['pickupDate' => '2026-10-20']);
        $client->carriers->fedex->cancelShipment(['trackingNumber' => '7946']);
        $client->carriers->dhl->cancelPickup();

        $this->assertSame([
            ['POST', 'http://localhost/api/ApiProxy/api/v3/CarrierPickup/postUspsCreateCarrierPickupSchedule', ['pickupDate' => '2026-10-20']],
            ['PUT', 'http://localhost/api/ApiProxy/api/v3/Shipping/putFedExCancelShipment', ['trackingNumber' => '7946']],
            ['DELETE', 'http://localhost/api/ApiProxy/api/v2/ShippingLabel/deleteDhlPickup', null],
        ], array_map(self::call(...), $mock->requests));
        $this->assertNotNull(MockHttpClient::header($mock->requests[0], 'Idempotency-Key'));
        $this->assertNotNull(MockHttpClient::header($mock->requests[1], 'Idempotency-Key'));
        $this->assertNull(MockHttpClient::header($mock->requests[2], 'Idempotency-Key'));
    }
}
