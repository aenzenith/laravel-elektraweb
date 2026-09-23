<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Facades;

use Aenzenith\ElektraWeb\ElektraWeb as Manager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi bookingApi()
 * @method static \Aenzenith\ElektraWeb\Contracts\Module module(string $name)
 * @method static \Aenzenith\ElektraWeb\ElektraWeb extend(string $name, string|\Closure $concrete)
 * @method static bool has(string $name)
 * @method static list<string> modules()
 *
 * @see Manager
 */
class ElektraWeb extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Manager::class;
    }
}
