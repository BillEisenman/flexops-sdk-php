<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\RecurrenceInterval;
use FlexOps\ReportFormat;
use FlexOps\ReportFrequency;
use FlexOps\TrackingEmailEvent;
use PHPUnit\Framework\TestCase;

/**
 * Recurring shipments, report schedules and email templates against the Gateway controllers
 * (RecurringShipmentsController, ReportSchedulesController, ShipmentEmailTemplatesController).
 */
class SchedulesAndTemplatesTest extends TestCase
{
    private const WS = 'http://localhost/api/workspaces/ws-test-123';

    private static function call(array $request): array
    {
        return [$request['method'], $request['url'], json_decode(json_encode($request['body']), true)];
    }

    public function testRecurringShipmentRoutesAndBodies(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $create = ['name' => 'Weekly restock', 'interval' => RecurrenceInterval::Weekly, 'addressId' => 'addr-1',
            'firstRunUtc' => '2026-11-02T15:00:00Z'];
        $client->recurringShipments->list();
        $client->recurringShipments->get('rs-1');
        $client->recurringShipments->create($create);
        $client->recurringShipments->update('rs-1', ['interval' => RecurrenceInterval::Custom, 'cronExpression' => '0 9 * * 1']);
        $client->recurringShipments->delete('rs-1');
        $client->recurringShipments->pause('rs-1');
        $client->recurringShipments->resume('rs-1');
        $client->recurringShipments->trigger('rs-1');

        $this->assertSame([
            ['GET', self::WS . '/recurring-shipments', null],
            ['GET', self::WS . '/recurring-shipments/rs-1', null],
            ['POST', self::WS . '/recurring-shipments', ['name' => 'Weekly restock', 'interval' => 1, 'addressId' => 'addr-1', 'firstRunUtc' => '2026-11-02T15:00:00Z']],
            ['PUT', self::WS . '/recurring-shipments/rs-1', ['interval' => 4, 'cronExpression' => '0 9 * * 1']],
            ['DELETE', self::WS . '/recurring-shipments/rs-1', null],
            ['POST', self::WS . '/recurring-shipments/rs-1/pause', null],
            ['POST', self::WS . '/recurring-shipments/rs-1/resume', null],
            ['POST', self::WS . '/recurring-shipments/rs-1/trigger', null],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testRecurringShipmentsReturnBareJsonAndNullFor204(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $schedule = ['id' => 'rs-1', 'name' => 'Weekly restock', 'interval' => 1, 'intervalName' => 'Weekly', 'isActive' => true];
        $mock->enqueueJson([$schedule])->enqueueJson($schedule, 201)->enqueueEmpty()->enqueueEmpty()->enqueueEmpty();

        $this->assertSame([$schedule], $client->recurringShipments->list());
        $this->assertSame($schedule, $client->recurringShipments->create(['name' => 'Weekly restock']));
        $this->assertNull($client->recurringShipments->pause('rs-1'));
        $this->assertNull($client->recurringShipments->trigger('rs-1'));
        $this->assertNull($client->recurringShipments->delete('rs-1'));
    }

    public function testTriggerSendsTheCallersIdempotencyKey(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->recurringShipments->trigger('rs-1', 'run-2026-11-02');
        $client->recurringShipments->trigger('rs-1');

        $this->assertSame('run-2026-11-02', MockHttpClient::header($mock->requests[0], 'Idempotency-Key'));
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', MockHttpClient::header($mock->requests[1], 'Idempotency-Key'));
    }

    public function testReportScheduleRoutesAndBodies(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $create = ['reportType' => 'shipments', 'format' => ReportFormat::Csv, 'frequencyType' => ReportFrequency::Weekly,
            'dayOfWeek' => 1, 'timeOfDayUtc' => '08:00:00', 'recipientEmails' => 'ops@example.com,cfo@example.com'];
        $client->reportSchedules->list();
        $client->reportSchedules->get('rp-1');
        $client->reportSchedules->create($create);
        $client->reportSchedules->update('rp-1', ['format' => ReportFormat::Json]);
        $client->reportSchedules->delete('rp-1');
        $client->reportSchedules->toggle('rp-1');

        $this->assertSame([
            ['GET', self::WS . '/report-schedules', null],
            ['GET', self::WS . '/report-schedules/rp-1', null],
            ['POST', self::WS . '/report-schedules', ['reportType' => 'shipments', 'format' => 0, 'frequencyType' => 1,
                'dayOfWeek' => 1, 'timeOfDayUtc' => '08:00:00', 'recipientEmails' => 'ops@example.com,cfo@example.com']],
            ['PUT', self::WS . '/report-schedules/rp-1', ['format' => 1]],
            ['DELETE', self::WS . '/report-schedules/rp-1', null],
            ['POST', self::WS . '/report-schedules/rp-1/toggle', null],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testReportSchedulesReturnTheEnvelopeAndNullFor204(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $envelope = ['success' => true, 'data' => ['id' => 'rp-1', 'isEnabled' => true], 'message' => null];
        $mock->enqueueJson($envelope, 201)->enqueueJson(['success' => true, 'data' => null, 'message' => 'Report schedule toggled'])
            ->enqueueEmpty();

        $this->assertSame($envelope, $client->reportSchedules->create(['reportType' => 'shipments']));
        $this->assertNull($client->reportSchedules->toggle('rp-1')['data']);
        $this->assertNull($client->reportSchedules->delete('rp-1'));
    }

    public function testEmailTemplateRoutesAndBodies(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $client->emailTemplates->list();
        $client->emailTemplates->get('et-1');
        $client->emailTemplates->upsert(['eventType' => TrackingEmailEvent::Delivered, 'subjectTemplate' => 'Delivered: {{tracking_number}}',
            'htmlBodyTemplate' => '<p>Hi</p>', 'isEnabled' => true]);
        $client->emailTemplates->delete('et-1');
        $client->emailTemplates->preview('S {{carrier}}', '<p>B</p>');

        $this->assertSame([
            ['GET', self::WS . '/email-templates', null],
            ['GET', self::WS . '/email-templates/et-1', null],
            ['PUT', self::WS . '/email-templates', ['eventType' => 3, 'subjectTemplate' => 'Delivered: {{tracking_number}}',
                'htmlBodyTemplate' => '<p>Hi</p>', 'isEnabled' => true]],
            ['DELETE', self::WS . '/email-templates/et-1', null],
            ['POST', self::WS . '/email-templates/preview', ['subjectTemplate' => 'S {{carrier}}', 'htmlBodyTemplate' => '<p>B</p>']],
        ], array_map(self::call(...), $mock->requests));
    }

    public function testEmailTemplatesReturnBareJsonAndNullFor204(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $template = ['id' => 'et-1', 'eventType' => 3, 'eventTypeName' => 'Delivered', 'isEnabled' => true];
        $mock->enqueueJson($template)->enqueueJson(['renderedSubject' => 'S USPS', 'renderedHtmlBody' => '<p>B</p>'])->enqueueEmpty();

        $this->assertSame($template, $client->emailTemplates->upsert(['eventType' => 3]));
        $this->assertSame(['renderedSubject' => 'S USPS', 'renderedHtmlBody' => '<p>B</p>'], $client->emailTemplates->preview('S {{carrier}}', '<p>B</p>'));
        $this->assertNull($client->emailTemplates->delete('et-1'));
    }

    public function testEnumValuesMatchTheGateway(): void
    {
        $values = fn (string $enum) => array_column(array_map(fn ($c) => ['n' => $c->name, 'v' => $c->value], $enum::cases()), 'v', 'n');
        $this->assertSame(['Daily' => 0, 'Weekly' => 1, 'Biweekly' => 2, 'Monthly' => 3, 'Custom' => 4], $values(RecurrenceInterval::class));
        $this->assertSame(['Csv' => 0, 'Json' => 1], $values(ReportFormat::class));
        $this->assertSame(['Daily' => 0, 'Weekly' => 1, 'Monthly' => 2], $values(ReportFrequency::class));
        $this->assertSame(['LabelCreated' => 0, 'InTransit' => 1, 'OutForDelivery' => 2, 'Delivered' => 3, 'Exception' => 4,
            'ReturnToSender' => 5], $values(TrackingEmailEvent::class));
    }
}
