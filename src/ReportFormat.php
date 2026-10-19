<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-19
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

/** A scheduled report's file format, sent as the Gateway number. */
enum ReportFormat: int
{
    case Csv = 0;
    case Json = 1;
}
