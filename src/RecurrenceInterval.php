<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

/**
 * How often a recurring shipment runs, sent as the Gateway `RecurrenceInterval` number.
 * `Custom` runs on the request's `cronExpression`.
 */
enum RecurrenceInterval: int
{
    case Daily = 0;
    case Weekly = 1;
    case Biweekly = 2;
    case Monthly = 3;
    case Custom = 4;
}
