<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-10-12
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps;

/**
 * USPS HAZMAT classification, sent as the Gateway `HazmatClassification` number
 * (`json_encode` writes the case value).
 *
 * There is no default: leave `hazmatClassification` out when you have no answer, or send
 * `HazmatClassification::None` (0) to declare that the parcel holds no dangerous goods.
 * A label without an `orderId` must declare one.
 */
enum HazmatClassification: int
{
    case None = 0;
    case LimitedQuantity = 1;
    case LithiumBatteryContainedInEquipment = 2;
    case LithiumBatteryPackedWithEquipment = 3;
    case LithiumBatteryStandalone = 4;
    case OtherRestricted = 5;
    case LithiumBatteryUsedDevice = 6;
}
