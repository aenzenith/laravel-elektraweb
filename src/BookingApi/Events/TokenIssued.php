<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Events;

use Aenzenith\ElektraWeb\BookingApi\Auth\AccessToken;
use Aenzenith\ElektraWeb\BookingApi\Auth\Credentials;

/**
 * Fired after a successful POST /login.
 */
final class TokenIssued
{
    public function __construct(
        public readonly AccessToken $token,
        public readonly Credentials $credentials,
    ) {}
}
