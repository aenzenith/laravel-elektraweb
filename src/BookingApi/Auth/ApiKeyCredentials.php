<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

/**
 * Login with `Authorization: Bearer <api-key>`.
 */
final class ApiKeyCredentials implements Credentials
{
    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function fingerprint(): string
    {
        return 'api_key:'.hash('sha256', $this->apiKey);
    }

    public function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->apiKey];
    }

    public function payload(): array
    {
        return [];
    }
}
