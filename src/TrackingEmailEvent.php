<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

/** The tracking event an email template is for, sent as the Gateway `TrackingEmailEvent` number. */
enum TrackingEmailEvent: int
{
    case LabelCreated = 0;
    case InTransit = 1;
    case OutForDelivery = 2;
    case Delivered = 3;
    case Exception = 4;
    case ReturnToSender = 5;
}
