<?php
// ***********************************************************************
// Package          : flexops/sdk
// Author           : FlexOps, LLC
// Created          : 2026-03-08
//
// Copyright (c) 2021-2026 by FlexOps, LLC. All rights reserved.
// ***********************************************************************

namespace FlexOps\Resources;

use FlexOps\HttpClient;

class AuthResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function login(string $email, string $password): mixed
    {
        return $this->http->post('/api/Account/login', ['email' => $email, 'password' => $password]);
    }

    public function register(array $request): mixed
    {
        return $this->http->post('/api/Account/register', $request);
    }

    public function refreshToken(string $refreshToken): mixed
    {
        return $this->http->post('/api/Account/refresh-token', ['refreshToken' => $refreshToken]);
    }

    public function logout(): mixed
    {
        return $this->http->post('/api/Account/logout');
    }

    public function getProfile(): mixed
    {
        return $this->http->get('/api/Account/profile');
    }

    public function updateProfile(array $data): mixed
    {
        return $this->http->put('/api/Account/profile', $data);
    }

    public function changePassword(string $currentPassword, string $newPassword): mixed
    {
        return $this->http->post('/api/Account/change-password', ['currentPassword' => $currentPassword, 'newPassword' => $newPassword]);
    }

    public function forgotPassword(string $email): mixed
    {
        return $this->http->post('/api/Account/forgot-password', ['email' => $email]);
    }

    public function resetPassword(string $token, string $newPassword): mixed
    {
        return $this->http->post('/api/Account/reset-password', ['token' => $token, 'newPassword' => $newPassword]);
    }

    public function verifyEmail(string $token): mixed
    {
        return $this->http->post('/api/Account/verify-email', ['token' => $token]);
    }
}
