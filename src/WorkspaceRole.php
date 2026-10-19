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
 * A member's role in a workspace, sent as the Gateway `WorkspaceRole` number
 * (`json_encode` writes the case value).
 */
enum WorkspaceRole: int
{
    case Owner = 0;
    case Admin = 1;
    case Member = 2;
    case Merchant = 3;

    /**
     * Accepts a case, its number, or its name in any letter case ("admin").
     *
     * @throws \InvalidArgumentException The value names no role.
     */
    public static function parse(self|int|string $role): self
    {
        if ($role instanceof self) {
            return $role;
        }
        if (is_int($role)) {
            return self::tryFrom($role) ?? throw new \InvalidArgumentException("Unknown workspace role: {$role}");
        }
        foreach (self::cases() as $case) {
            if (strcasecmp($case->name, $role) === 0) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Unknown workspace role: {$role}. Use Owner, Admin, Member or Merchant.");
    }
}
