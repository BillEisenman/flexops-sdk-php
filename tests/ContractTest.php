<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOps;
use FlexOps\HazmatClassification;
use FlexOps\RecurrenceInterval;
use FlexOps\ReportFormat;
use FlexOps\TrackingEmailEvent;
use FlexOps\WorkspaceRole;
use PHPUnit\Framework\TestCase;

/**
 * Every public SDK method must call a documented Gateway operation.
 *
 * fixtures/gateway-operations.json lists the documented "METHOD /path" operations of the Developer
 * Docs OpenAPI snapshot (FlexOpsDeveloperDocs static/openapi/gateway.json, commit in the file);
 * refresh it from that file when the Gateway's public API changes.
 *
 * The legacy /api/ApiProxy/{path} catch-all refuses API-key writes. A POST, PUT or DELETE through it
 * may only come from a deprecated carrier method in DEPRECATED_PROXY_WRITES, never on a label route
 * the proxy has closed (410), and nothing sends a PATCH through it.
 */
class ContractTest extends TestCase
{
    private const PROXY_PREFIX = '/api/apiproxy/';

    /** The deprecated carrier writes, user-token only, that may still go through ApiProxy. */
    private const DEPRECATED_PROXY_WRITES = [
        'carriers.usps.getDomesticRates',
        'carriers.usps.getDomesticProducts',
        'carriers.usps.getDomesticPrices',
        'carriers.usps.getInternationalRates',
        'carriers.usps.getInternationalPrices',
        'carriers.usps.cancelDomesticLabel',
        'carriers.usps.cancelInternationalLabel',
        'carriers.usps.createPickup',
        'carriers.usps.cancelPickup',
        'carriers.usps.createScanForm',
        'carriers.ups.validateAddress',
        'carriers.ups.getRates',
        'carriers.ups.createPickup',
        'carriers.ups.cancelPickup',
        'carriers.ups.getTransitTimes',
        'carriers.ups.getLandedCost',
        'carriers.ups.searchLocations',
        'carriers.ups.uploadDocument',
        'carriers.ups.createFreightShipment',
        'carriers.ups.getFreightRate',
        'carriers.fedex.validateAddress',
        'carriers.fedex.validatePostalCode',
        'carriers.fedex.getRates',
        'carriers.fedex.cancelShipment',
        'carriers.fedex.validateShipment',
        'carriers.fedex.track',
        'carriers.fedex.trackMultiPiece',
        'carriers.fedex.registerTrackingNotification',
        'carriers.fedex.createPickup',
        'carriers.fedex.cancelPickup',
        'carriers.fedex.searchLocations',
        'carriers.fedex.getServiceStandards',
        'carriers.fedex.getFreightRate',
        'carriers.fedex.createFreightShipment',
        'carriers.fedex.groundClose',
        'carriers.fedex.uploadTradeDocuments',
        'carriers.fedex.createOpenShipment',
        'carriers.fedex.addPackagesToOpenShipment',
        'carriers.fedex.confirmOpenShipment',
        'carriers.dhl.getMultiPieceRates',
        'carriers.dhl.createPickup',
        'carriers.dhl.cancelPickup',
        'carriers.dhl.calculateLandedCost',
        'carriers.dhl.screenShipment',
        'carriers.dhl.uploadInvoice',
    ];

    /** Proxy label-generation suffixes the Gateway answers with 410 Gone (case-insensitive). */
    private const CLOSED_LABEL_SUFFIXES = [
        'generateNewUpsShipLabel', 'postNewShipLabelOrder', 'postUspsGenerateDomesticShippingLabel',
        'postUspsGenerateDomesticReturnsShippingLabel', 'postUspsGenerateInternationalShippingLabel',
        'postUspsGenerateDomesticIndiciaLabel', 'postFedExCreateNewShipment', 'postFedExCreateNewReturnShipment',
        'postDhlCreateShipment', 'postOnTracCreateShipment', 'postLsoCreateShipment', 'postAusPostCreateShipment',
        'postCanadaPostCreateShipment', 'postRoyalMailCreateShipment',
    ];

    /**
     * One call per public method, with dummy arguments. Keys are "resource.method"
     * ("carriers.usps.method" for the carrier calls). The deprecated carrier writes are called with
     * a dummy body for each required parameter.
     *
     * @return array<string, \Closure(FlexOps): mixed>
     */
    private static function calls(): array
    {
        $calls = self::explicitCalls();
        foreach (self::DEPRECATED_PROXY_WRITES as $name) {
            [, $carrier, $method] = explode('.', $name);
            $args = [];
            foreach ((new \ReflectionMethod(self::carrierClass($carrier), $method))->getParameters() as $parameter) {
                if (!$parameter->isOptional()) {
                    $args[] = (string) $parameter->getType() === 'string' ? '1' : ['dummy' => true];
                }
            }
            $calls[$name] = fn (FlexOps $c) => $c->carriers->{$carrier}->{$method}(...$args);
        }
        return $calls;
    }

    private static function carrierClass(string $carrier): string
    {
        return (new \ReflectionProperty(\FlexOps\Resources\CarriersResource::class, $carrier))->getType()->getName();
    }

    /** @return array<string, \Closure(FlexOps): mixed> */
    private static function explicitCalls(): array
    {
        $address = ['line1' => '1 Main St', 'city' => 'Austin', 'state' => 'TX', 'postalCode' => '78701'];
        return [
            'auth.login' => fn (FlexOps $c) => $c->auth->login('a@example.com', 'pw'),
            'auth.refreshToken' => fn (FlexOps $c) => $c->auth->refreshToken('rt', 'expired-access-token'),
            'auth.logout' => fn (FlexOps $c) => $c->auth->logout(),

            'workspaces.list' => fn (FlexOps $c) => $c->workspaces->list(),
            'workspaces.get' => fn (FlexOps $c) => $c->workspaces->get(),
            'workspaces.listMembers' => fn (FlexOps $c) => $c->workspaces->listMembers(),
            'workspaces.inviteMember' => fn (FlexOps $c) => $c->workspaces->inviteMember('a@example.com', WorkspaceRole::Member),
            'workspaces.removeMember' => fn (FlexOps $c) => $c->workspaces->removeMember('user-1'),
            'workspaces.updateMemberRole' => fn (FlexOps $c) => $c->workspaces->updateMemberRole('user-1', 'Admin'),

            'shipping.getRates' => fn (FlexOps $c) => $c->shipping->getRates(TestHelper::rateRequest()),
            'shipping.getCheapestRate' => fn (FlexOps $c) => $c->shipping->getCheapestRate(TestHelper::rateRequest()),
            'shipping.getFastestRate' => fn (FlexOps $c) => $c->shipping->getFastestRate(TestHelper::rateRequest()),
            'shipping.createLabel' => fn (FlexOps $c) => $c->shipping->createLabel(['hazmatClassification' => HazmatClassification::None]),
            'shipping.cancelLabel' => fn (FlexOps $c) => $c->shipping->cancelLabel('lbl-1', 'USPS'),
            'shipping.track' => fn (FlexOps $c) => $c->shipping->track('9400 1'),
            'shipping.validateAddress' => fn (FlexOps $c) => $c->shipping->validateAddress($address),
            'shipping.createBatch' => fn (FlexOps $c) => $c->shipping->createBatch(['items' => []]),
            'shipping.previewBatch' => fn (FlexOps $c) => $c->shipping->previewBatch(['items' => []]),
            'shipping.createMultiPackage' => fn (FlexOps $c) => $c->shipping->createMultiPackage(['orderId' => 1, 'carrierCode' => 'USPS', 'packages' => [['weightInOunces' => 16, 'lengthInInches' => 10, 'widthInInches' => 8, 'heightInInches' => 4]]]),
            'shipping.getBatchStatus' => fn (FlexOps $c) => $c->shipping->getBatchStatus('job-1'),
            'shipping.downloadBatchLabel' => fn (FlexOps $c) => $c->shipping->downloadBatchLabel('job-1', 'item-1'),
            'shipping.getCarriers' => fn (FlexOps $c) => $c->shipping->getCarriers(),
            'shipping.getRecommendations' => fn (FlexOps $c) => $c->shipping->getRecommendations(['originPostalCode' => '10001']),
            'shipping.predictDelivery' => fn (FlexOps $c) => $c->shipping->predictDelivery(['carrierCode' => 'USPS']),
            'shipping.getSavings' => fn (FlexOps $c) => $c->shipping->getSavings(),

            'carriers.usps.validateAddress' => fn (FlexOps $c) => $c->carriers->usps->validateAddress(['zip' => '10001']),
            'carriers.usps.cityStateLookup' => fn (FlexOps $c) => $c->carriers->usps->cityStateLookup('10001'),
            'carriers.usps.zipCodeLookup' => fn (FlexOps $c) => $c->carriers->usps->zipCodeLookup(['city' => 'Austin']),
            'carriers.usps.trackSummary' => fn (FlexOps $c) => $c->carriers->usps->trackSummary(['trackingNumber' => '9400']),
            'carriers.usps.trackDetail' => fn (FlexOps $c) => $c->carriers->usps->trackDetail(['trackingNumber' => '9400']),
            'carriers.usps.deliveryStandards' => fn (FlexOps $c) => $c->carriers->usps->deliveryStandards(['originZip' => '10001']),
            'carriers.usps.findDropOffLocations' => fn (FlexOps $c) => $c->carriers->usps->findDropOffLocations(['zip' => '10001']),
            'carriers.usps.findPostOffices' => fn (FlexOps $c) => $c->carriers->usps->findPostOffices(['zip' => '10001']),
            'carriers.ups.track' => fn (FlexOps $c) => $c->carriers->ups->track(['trackingNumber' => '1Z']),
            'carriers.dhl.validateAddress' => fn (FlexOps $c) => $c->carriers->dhl->validateAddress(['postalCode' => '10001']),
            'carriers.dhl.getRates' => fn (FlexOps $c) => $c->carriers->dhl->getRates(['weight' => 1]),
            'carriers.dhl.getProducts' => fn (FlexOps $c) => $c->carriers->dhl->getProducts(['weight' => 1]),
            'carriers.dhl.track' => fn (FlexOps $c) => $c->carriers->dhl->track(['trackingNumber' => '1']),
            'carriers.dhl.trackMultiple' => fn (FlexOps $c) => $c->carriers->dhl->trackMultiple(['trackingNumbers' => '1,2']),
            'carriers.dhl.getProofOfDelivery' => fn (FlexOps $c) => $c->carriers->dhl->getProofOfDelivery(['trackingNumber' => '1']),
            'carriers.dhl.getReferenceData' => fn (FlexOps $c) => $c->carriers->dhl->getReferenceData(['datasetName' => 'countries']),
            'carriers.dhl.findServicePoints' => fn (FlexOps $c) => $c->carriers->dhl->findServicePoints(['postalCode' => '10001']),

            'webhooks.list' => fn (FlexOps $c) => $c->webhooks->list(),
            'webhooks.create' => fn (FlexOps $c) => $c->webhooks->create(['url' => 'https://example.com/hook']),
            'webhooks.update' => fn (FlexOps $c) => $c->webhooks->update('wh-1', ['status' => 'Paused']),
            'webhooks.delete' => fn (FlexOps $c) => $c->webhooks->delete('wh-1'),
            'webhooks.rotateSecret' => fn (FlexOps $c) => $c->webhooks->rotateSecret('wh-1'),
            'webhooks.listDeliveryLogs' => fn (FlexOps $c) => $c->webhooks->listDeliveryLogs('wh-1'),
            'webhooks.test' => fn (FlexOps $c) => $c->webhooks->test('wh-1'),

            'wallet.getBalance' => fn (FlexOps $c) => $c->wallet->getBalance(),

            'insurance.getProviders' => fn (FlexOps $c) => $c->insurance->getProviders(),
            'insurance.getQuote' => fn (FlexOps $c) => $c->insurance->getQuote('USPS', 100.0),
            'insurance.purchase' => fn (FlexOps $c) => $c->insurance->purchase('9400 1', 'USPS', 100.0),
            'insurance.void' => fn (FlexOps $c) => $c->insurance->void('pol-1'),
            'insurance.fileClaim' => fn (FlexOps $c) => $c->insurance->fileClaim('pol-1', 'Lost', 100.0),

            'returns.list' => fn (FlexOps $c) => $c->returns->list(),
            'returns.get' => fn (FlexOps $c) => $c->returns->get(42),
            'returns.listRmas' => fn (FlexOps $c) => $c->returns->listRmas(),
            'returns.getRma' => fn (FlexOps $c) => $c->returns->getRma('RMA-1'),
            'returns.create' => fn (FlexOps $c) => $c->returns->create(['orderId' => 1, 'reason' => 'Damaged', 'items' => [['sku' => 'A', 'quantity' => 1]]]),
            'returns.generateLabel' => fn (FlexOps $c) => $c->returns->generateLabel('RMA-1'),
            'returns.markReceived' => fn (FlexOps $c) => $c->returns->markReceived(42, 'RMA-1', [['partNumber' => 'A', 'quantity' => 1, 'disposition' => 0]]),

            'analytics.shipmentsTrend' => fn (FlexOps $c) => $c->analytics->shipmentsTrend('2026-01-01', '2026-02-01'),
            'analytics.carrierSummary' => fn (FlexOps $c) => $c->analytics->carrierSummary(),
            'analytics.topDestinations' => fn (FlexOps $c) => $c->analytics->topDestinations(limit: 5),
            'analytics.inventoryMetrics' => fn (FlexOps $c) => $c->analytics->inventoryMetrics(),
            'analytics.stockByWarehouse' => fn (FlexOps $c) => $c->analytics->stockByWarehouse(),
            'analytics.orderMetrics' => fn (FlexOps $c) => $c->analytics->orderMetrics(),
            'analytics.orderTrend' => fn (FlexOps $c) => $c->analytics->orderTrend(),
            'analytics.topSellingProducts' => fn (FlexOps $c) => $c->analytics->topSellingProducts(),
            'analytics.returnsMetrics' => fn (FlexOps $c) => $c->analytics->returnsMetrics(),
            'analytics.returnsTrend' => fn (FlexOps $c) => $c->analytics->returnsTrend(),
            'analytics.returnReasons' => fn (FlexOps $c) => $c->analytics->returnReasons(),
            'analytics.performanceMetrics' => fn (FlexOps $c) => $c->analytics->performanceMetrics(),
            'analytics.carrierPerformance' => fn (FlexOps $c) => $c->analytics->carrierPerformance(),
            'analytics.shippingCostAnalytics' => fn (FlexOps $c) => $c->analytics->shippingCostAnalytics(),
            'analytics.deliveryPerformance' => fn (FlexOps $c) => $c->analytics->deliveryPerformance(),

            'orders.getNewOrders' => fn (FlexOps $c) => $c->orders->getNewOrders(),
            'orders.getByStatus' => fn (FlexOps $c) => $c->orders->getByStatus(['status' => 'New']),
            'orders.getDetails' => fn (FlexOps $c) => $c->orders->getDetails('ORD-1'),
            'orders.getExtendedDetails' => fn (FlexOps $c) => $c->orders->getExtendedDetails('ORD-1'),
            'orders.getStatus' => fn (FlexOps $c) => $c->orders->getStatus('ORD-1'),
            'orders.getItems' => fn (FlexOps $c) => $c->orders->getItems('ORD-1'),
            'orders.getShipMethods' => fn (FlexOps $c) => $c->orders->getShipMethods(),
            'orders.getWarehouses' => fn (FlexOps $c) => $c->orders->getWarehouses(),
            'orders.getCountryCodes' => fn (FlexOps $c) => $c->orders->getCountryCodes(),
            'orders.getStatusTypes' => fn (FlexOps $c) => $c->orders->getStatusTypes(),

            'inventory.getWarehouseSnapshot' => fn (FlexOps $c) => $c->inventory->getWarehouseSnapshot(),
            'inventory.getCompleteSnapshot' => fn (FlexOps $c) => $c->inventory->getCompleteSnapshot(),
            'inventory.getPartNumbers' => fn (FlexOps $c) => $c->inventory->getPartNumbers(),

            'pickups.schedule' => fn (FlexOps $c) => $c->pickups->schedule(['carrierCode' => 'usps']),
            'pickups.list' => fn (FlexOps $c) => $c->pickups->list(),
            'pickups.get' => fn (FlexOps $c) => $c->pickups->get('PU-1'),
            'pickups.cancel' => fn (FlexOps $c) => $c->pickups->cancel('PU-1'),

            'scanForms.create' => fn (FlexOps $c) => $c->scanForms->create(['labelIds' => ['lbl-1']]),
            'scanForms.list' => fn (FlexOps $c) => $c->scanForms->list(),
            'scanForms.get' => fn (FlexOps $c) => $c->scanForms->get('sf-1'),

            'rules.list' => fn (FlexOps $c) => $c->rules->list(),
            'rules.get' => fn (FlexOps $c) => $c->rules->get('rule-1'),
            'rules.create' => fn (FlexOps $c) => $c->rules->create(['name' => 'R']),
            'rules.update' => fn (FlexOps $c) => $c->rules->update('rule-1', ['name' => 'R']),
            'rules.delete' => fn (FlexOps $c) => $c->rules->delete('rule-1'),
            'rules.reorder' => fn (FlexOps $c) => $c->rules->reorder([['ruleId' => 'rule-1', 'priority' => 0]]),
            'rules.test' => fn (FlexOps $c) => $c->rules->test(['weightOz' => 16]),

            'hsCodes.search' => fn (FlexOps $c) => $c->hsCodes->search('mug'),
            'hsCodes.lookup' => fn (FlexOps $c) => $c->hsCodes->lookup('6912.00'),
            'hsCodes.estimateLandedCost' => fn (FlexOps $c) => $c->hsCodes->estimateLandedCost(['destinationCountry' => 'CA', 'items' => []]),

            'carbonOffsets.quote' => fn (FlexOps $c) => $c->carbonOffsets->quote(1.0, 100.0),
            'carbonOffsets.purchase' => fn (FlexOps $c) => $c->carbonOffsets->purchase('9400 1', 1.0, 100.0),
            'carbonOffsets.list' => fn (FlexOps $c) => $c->carbonOffsets->list(1, 25),
            'carbonOffsets.get' => fn (FlexOps $c) => $c->carbonOffsets->get('off-1'),
            'carbonOffsets.summary' => fn (FlexOps $c) => $c->carbonOffsets->summary(),
            'carbonOffsets.getSettings' => fn (FlexOps $c) => $c->carbonOffsets->getSettings(),
            'carbonOffsets.updateSettings' => fn (FlexOps $c) => $c->carbonOffsets->updateSettings(true),

            'recurringShipments.list' => fn (FlexOps $c) => $c->recurringShipments->list(),
            'recurringShipments.get' => fn (FlexOps $c) => $c->recurringShipments->get('rs-1'),
            'recurringShipments.create' => fn (FlexOps $c) => $c->recurringShipments->create(['name' => 'Weekly', 'interval' => RecurrenceInterval::Weekly, 'firstRunUtc' => '2026-11-02T15:00:00Z']),
            'recurringShipments.update' => fn (FlexOps $c) => $c->recurringShipments->update('rs-1', ['name' => 'W']),
            'recurringShipments.delete' => fn (FlexOps $c) => $c->recurringShipments->delete('rs-1'),
            'recurringShipments.pause' => fn (FlexOps $c) => $c->recurringShipments->pause('rs-1'),
            'recurringShipments.resume' => fn (FlexOps $c) => $c->recurringShipments->resume('rs-1'),
            'recurringShipments.trigger' => fn (FlexOps $c) => $c->recurringShipments->trigger('rs-1'),

            'reportSchedules.list' => fn (FlexOps $c) => $c->reportSchedules->list(),
            'reportSchedules.get' => fn (FlexOps $c) => $c->reportSchedules->get('rp-1'),
            'reportSchedules.create' => fn (FlexOps $c) => $c->reportSchedules->create(['reportType' => 'shipments', 'timeOfDayUtc' => '08:00:00', 'recipientEmails' => 'a@example.com']),
            'reportSchedules.update' => fn (FlexOps $c) => $c->reportSchedules->update('rp-1', ['format' => ReportFormat::Json]),
            'reportSchedules.delete' => fn (FlexOps $c) => $c->reportSchedules->delete('rp-1'),
            'reportSchedules.toggle' => fn (FlexOps $c) => $c->reportSchedules->toggle('rp-1'),

            'emailTemplates.list' => fn (FlexOps $c) => $c->emailTemplates->list(),
            'emailTemplates.get' => fn (FlexOps $c) => $c->emailTemplates->get('et-1'),
            'emailTemplates.upsert' => fn (FlexOps $c) => $c->emailTemplates->upsert(['eventType' => TrackingEmailEvent::Delivered, 'subjectTemplate' => 'S', 'htmlBodyTemplate' => 'B']),
            'emailTemplates.delete' => fn (FlexOps $c) => $c->emailTemplates->delete('et-1'),
            'emailTemplates.preview' => fn (FlexOps $c) => $c->emailTemplates->preview('S', 'B'),
        ];
    }

    /** Every public instance method of every resource the client exposes, by reflection. */
    private static function publicMethods(): array
    {
        $names = [];
        $walk = function (object $resource, string $prefix) use (&$walk, &$names): void {
            $class = new \ReflectionClass($resource);
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (!$method->isStatic() && !$method->isConstructor() && $method->getDeclaringClass()->getName() === $class->getName()) {
                    $names[] = $prefix . $method->getName();
                }
            }
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                $value = $property->getValue($resource);
                if (is_object($value) && str_starts_with($value::class, 'FlexOps\\')) {
                    $walk($value, $prefix . $property->getName() . '.');
                }
            }
        };

        ['client' => $client] = TestHelper::createClient();
        foreach ((new \ReflectionClass($client))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($client);
            if (is_object($value) && str_starts_with($value::class, 'FlexOps\\Resources\\')) {
                $walk($value, $property->getName() . '.');
            }
        }
        sort($names);
        return $names;
    }

    /** @return list<array{string, list<string>}> Documented operations as [METHOD, normalized path segments]. */
    private static function documentedOperations(): array
    {
        $fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/gateway-operations.json'), true, flags: JSON_THROW_ON_ERROR);
        $operations = [];
        foreach ($fixture['operations'] as $operation) {
            [$method, $path] = explode(' ', $operation, 2);
            $operations[] = [strtoupper($method), explode('/', self::normalize($path))];
        }
        return $operations;
    }

    /** Strips the query, maps /api/v1 to /api and lower-cases the path. */
    private static function normalize(string $path): string
    {
        $path = strtolower(explode('?', $path, 2)[0]);
        return preg_replace('#^/api/v1(?=/|$)#', '/api', $path);
    }

    private static function isDocumented(string $method, string $path, array $operations): bool
    {
        $segments = explode('/', $path);
        foreach ($operations as [$documentedMethod, $template]) {
            if ($documentedMethod !== $method || count($template) !== count($segments)) {
                continue;
            }
            $matches = true;
            foreach ($template as $i => $part) {
                $isParam = str_starts_with($part, '{') && str_ends_with($part, '}');
                if ($isParam ? $segments[$i] === '' : $part !== $segments[$i]) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return true;
            }
        }
        return false;
    }

    public function testEveryPublicMethodIsExercised(): void
    {
        $listed = array_keys(self::calls());
        sort($listed);
        $this->assertSame(self::publicMethods(), $listed,
            'Add each new public SDK method to ContractTest::calls() (and remove deleted ones).');
    }

    public function testEveryProxyWriteIsDeprecated(): void
    {
        foreach (self::DEPRECATED_PROXY_WRITES as $name) {
            [, $carrier, $method] = explode('.', $name);
            $doc = (string) (new \ReflectionMethod(self::carrierClass($carrier), $method))->getDocComment();
            $this->assertStringContainsString('@deprecated Legacy carrier proxy (sunset 2027-06-30)', $doc, "{$name} must be @deprecated");
        }
    }

    public function testEveryCallHitsADocumentedGatewayOperation(): void
    {
        $operations = self::documentedOperations();
        $undocumented = [];
        $proxyWrites = [];
        $closedRoutes = [];
        $proxyWriters = [];
        $checked = 0;

        foreach (self::calls() as $name => $call) {
            ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
            $call($client);
            $this->assertNotEmpty($mock->requests, "{$name} sent no request");

            foreach ($mock->requests as $request) {
                $checked++;
                $method = $request['method'];
                $path = self::normalize(parse_url($request['url'], PHP_URL_PATH));
                if (str_starts_with($path, self::PROXY_PREFIX)) {
                    if ($method !== 'GET') {
                        $proxyWriters[$name] = true;
                        if ($method === 'PATCH' || !in_array($name, self::DEPRECATED_PROXY_WRITES, true)) {
                            $proxyWrites[] = "{$name}: {$method} {$path}";
                        }
                        foreach (self::CLOSED_LABEL_SUFFIXES as $suffix) {
                            if (str_ends_with($path, strtolower($suffix))) {
                                $closedRoutes[] = "{$name}: {$method} {$path}";
                            }
                        }
                    }
                    $documented = self::isDocumented($method, '/api/apiproxy/x', $operations);
                } else {
                    $documented = self::isDocumented($method, $path, $operations);
                }
                if (!$documented) {
                    $undocumented[] = "{$name}: {$method} {$path}";
                }
            }
        }

        $this->assertSame([], $proxyWrites, 'Only a listed deprecated carrier write may write through ApiProxy, and never with PATCH.');
        $this->assertSame([], $closedRoutes, 'The proxy answers 410 Gone for these label routes.');
        $expected = self::DEPRECATED_PROXY_WRITES;
        $actual = array_keys($proxyWriters);
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual, 'DEPRECATED_PROXY_WRITES must list exactly the methods that write through ApiProxy.');
        $this->assertSame([], $undocumented, 'These calls hit no documented Gateway operation.');
        $this->assertSame(count(self::calls()), $checked, 'Each method should send exactly one request.');
    }
}
