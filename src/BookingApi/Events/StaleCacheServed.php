<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Events;

use Throwable;

/**
 * Fired when the provider failed and a stale cached copy was returned instead.
 */
final class StaleCacheServed
{
    public function __construct(
        public readonly string $key,
        public readonly Throwable $cause,
    ) {}
}
