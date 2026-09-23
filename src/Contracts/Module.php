<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Contracts;

use Aenzenith\ElektraWeb\ElektraWeb;

/**
 * An ElektraWeb product surface (Booking API, future PMS / Channel Manager APIs...).
 *
 * Modules are resolved lazily from the container through {@see ElektraWeb::module()}.
 */
interface Module
{
    /**
     * Short, stable identifier used as the config key and registry name.
     */
    public static function name(): string;
}
