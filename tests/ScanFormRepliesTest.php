<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-24
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Tests;

use FlexOps\FlexOpsError;
use FlexOps\ScanFormState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** SCAN form replies as the Gateway writes them (ScanFormsController, ScanFormDto), from a fixture. */
class ScanFormRepliesTest extends TestCase
{
    private static function fixtures(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/fixtures/scan-forms.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testACreatedFormHasTheGatewayFields(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(self::fixtures()['created']);

        $form = $client->scanForms->create([
            'trackingNumbers' => ['9400111899223456789014', '9400111899223456789021'], 'manifestDate' => '2026-09-08',
        ])['data'];

        $this->assertSame('3f2b8c1e9d4a4f6b8a7e2c5d1b0f9e88', $form['scanFormId']);
        $this->assertSame(ScanFormState::Created->value, $form['state']);
        $this->assertTrue(ScanFormState::isCreated($form));
        $this->assertTrue($form['success']);
        $this->assertFalse($form['isSandbox']);
        $this->assertSame('9475711899560013735215', $form['manifestNumber']);
        $this->assertSame('2026-09-08T00:00:00', $form['manifestDate']);
        $this->assertSame('2026-09-08T21:14:03.512Z', $form['createdAtUtc']);
        $this->assertSame(2, $form['packageCount']);
        $this->assertSame('78701', $form['originPostalCode']);
        $this->assertSame(['9400111899223456789014', '9400111899223456789021'], $form['trackingNumbers']);
        $this->assertNull($form['errorMessage']);
        $this->assertStringStartsWith('%PDF-', ScanFormState::pdfBytes($form));
    }

    public function testTheListHasSandboxFormsWithoutAPdfAndUnresolvedOperations(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(self::fixtures()['list']);

        [$sandbox, $unknown] = $client->scanForms->list()['data'];

        $this->assertTrue($sandbox['isSandbox']);
        $this->assertNull($sandbox['scanFormImage']);
        $this->assertNull(ScanFormState::pdfBytes($sandbox));
        $this->assertStringStartsWith('SBOX', $sandbox['manifestNumber']);
        $this->assertSame(ScanFormState::OutcomeUnknown->value, $unknown['state']);
        $this->assertFalse(ScanFormState::isCreated($unknown));
        $this->assertFalse($unknown['success']);
        $this->assertSame('', $unknown['manifestNumber']);
        $this->assertSame(2, $unknown['packageCount']);
    }

    public function testGetReadsOneForm(): void
    {
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson(self::fixtures()['created']);

        $form = $client->scanForms->get('3f2b8c1e9d4a4f6b8a7e2c5d1b0f9e88')['data'];

        $this->assertSame('http://localhost/api/workspaces/ws-test-123/scan-forms/3f2b8c1e9d4a4f6b8a7e2c5d1b0f9e88', $mock->requests[0]['url']);
        $this->assertSame('3f2b8c1e9d4a4f6b8a7e2c5d1b0f9e88', $form['scanFormId']);
    }

    public function testAnImageThatIsNotBase64IsRefused(): void
    {
        $this->expectException(FlexOpsError::class);

        ScanFormState::pdfBytes(['scanFormImage' => '%%% not base64']);
    }

    public static function conflicts(): array
    {
        return [['requestIdReused'], ['needsReview']];
    }

    #[DataProvider('conflicts')]
    public function testAGateway409IsThrownOnceWithoutARetry(string $name): void
    {
        $reply = self::fixtures()[$name];
        ['client' => $client, 'mock' => $mock] = TestHelper::createClient();
        $mock->enqueueJson($reply['body'], $reply['status'])->enqueueJson($reply['body'], $reply['status']);

        try {
            $client->scanForms->create(['trackingNumbers' => ['9400'], 'manifestDate' => '2026-09-08', 'requestId' => 'req-1']);
            $this->fail('Expected a 409 FlexOpsError');
        } catch (FlexOpsError $error) {
            $this->assertSame(409, $error->statusCode);
            $this->assertSame($reply['body']['message'], $error->getMessage());
        }
        $this->assertCount(1, $mock->requests);
    }
}
