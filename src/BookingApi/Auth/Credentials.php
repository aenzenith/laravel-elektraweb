<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

/**
 * A way of authenticating against POST /login.
 */
interface Credentials
{
    /**
     * Stable identifier used to key the cached token. Must not leak the secret.
     */
    public function fingerprint(): string;

    /**
     * Extra headers for the login request.
     *
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * JSON body for the login request. Empty array sends `{}`.
     *
     * @return array<string, mixed>
     */
    public function payload(): array;
}
