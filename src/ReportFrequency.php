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
 * How often a scheduled report runs (the Gateway `frequencyType` number). Weekly reports take a
 * `dayOfWeek` (0 Sunday to 6), monthly reports a `dayOfMonth` (1 to 28).
 */
enum ReportFrequency: int
{
    case Daily = 0;
    case Weekly = 1;
    case Monthly = 2;
}
