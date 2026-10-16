<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-16
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

/**
 * Event names FlexOps sends in the `X-FlexOps-Event` header and matches webhook
 * subscriptions against.
 *
 * Names are PascalCase and matched exactly: a dotted name such as `label.created`
 * matches nothing. `TrackingUpdated` and `TrackingDelivered` are sent only to
 * workspaces in the USPS push-tracking pilot.
 */
enum WebhookEventType: string
{
    case LabelCreated = 'LabelCreated';
    case LabelCanceled = 'LabelCanceled';
    case BatchCompleted = 'BatchCompleted';
    case TrackingUpdated = 'TrackingUpdated';
    case TrackingDelivered = 'TrackingDelivered';
}
