<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

/**
 * Login with a pre-issued "login-token".
 */
final class LoginTokenCredentials implements Credentials
{
    public function __construct(
        private readonly string $loginToken,
    ) {}

    public function fingerprint(): string
    {
        return 'login_token:'.hash('sha256', $this->loginToken);
    }

    public function headers(): array
    {
        return [];
    }

    public function payload(): array
    {
        return ['login-token' => $this->loginToken];
    }
}
