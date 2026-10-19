<?php
namespace FlexOps\Resources;

use FlexOps\FlexOpsAuthError;
use FlexOps\HttpClient;

/**
 * Token acquisition for operations that need a signed-in user rather than an API key.
 * Account management (sign-up, profile, passwords) lives in the FlexOps portals.
 */
class AuthResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * Signs in and, when the Gateway returns an access token, makes the client send it as the
     * bearer token (replacing any API key). Returns the envelope of the Gateway LoginResponse:
     * `data.token`, `data.refreshToken`, `data.expiresAt`, `data.userId`, ... A two-factor account
     * gets `data.requiresTwoFactor` and no token.
     */
    public function login(string $email, string $password): mixed
    {
        $result = $this->http->post('/api/account/login', ['email' => $email, 'password' => $password]);
        $this->useToken($result);
        return $result;
    }

    /**
     * Exchanges a refresh token for a new access token and makes the client send it. The Gateway
     * needs the current (possibly expired) access token too; it defaults to the client's.
     *
     * @throws FlexOpsAuthError No access token was given and the client has none. No request is sent.
     */
    public function refreshToken(string $refreshToken, ?string $accessToken = null): mixed
    {
        $accessToken ??= $this->http->getAccessToken();
        if ($accessToken === null || $accessToken === '') {
            throw new FlexOpsAuthError('No access token to refresh: pass the current access token or log in first.');
        }
        $result = $this->http->post('/api/account/refresh-token', ['token' => $accessToken, 'refreshToken' => $refreshToken]);
        $this->useToken($result);
        return $result;
    }

    public function logout(): mixed
    {
        return $this->http->post('/api/account/logout');
    }

    private function useToken(mixed $result): void
    {
        $token = is_array($result) ? ($result['data']['token'] ?? null) : null;
        if (is_string($token) && $token !== '') {
            $this->http->setAccessToken($token);
        }
    }
}
