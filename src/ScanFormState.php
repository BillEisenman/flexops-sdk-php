<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-24
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

/**
 * The `state` of a SCAN form array returned by `$client->scanForms` (the Gateway's ScanFormDto).
 * The Gateway may add other values; treat them like `OutcomeUnknown`.
 */
enum ScanFormState: string
{
    /** USPS returned the manifest, and the Gateway saved its number and PDF. */
    case Created = 'Created';

    /**
     * The carrier's answer was lost or did not match. The packages stay reserved: do not submit
     * them again until the outcome is resolved.
     */
    case OutcomeUnknown = 'OutcomeUnknown';

    /** Whether a SCAN form array (`data` of create/get, or an item of list) is a created form. */
    public static function isCreated(array $form): bool
    {
        return ($form['state'] ?? null) === self::Created->value;
    }

    /**
     * Decodes a SCAN form array's `scanFormImage` (the base64 PS Form 5630 PDF), or returns null when
     * it has none: sandbox forms are never sent to USPS, and an unresolved operation has no PDF.
     *
     * @throws FlexOpsError The image is not valid base64 (code INVALID_SCAN_FORM_IMAGE).
     */
    public static function pdfBytes(array $form): ?string
    {
        $image = $form['scanFormImage'] ?? null;
        if ($image === null) {
            return null;
        }
        $bytes = base64_decode($image, true);
        return $bytes === false ? throw new FlexOpsError('scanFormImage is not valid base64.', 0, 'INVALID_SCAN_FORM_IMAGE') : $bytes;
    }
}
