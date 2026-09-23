<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb;

use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\Contracts\Module;
use Aenzenith\ElektraWeb\Exceptions\UnknownModuleException;
use Closure;
use Illuminate\Contracts\Container\Container;

/**
 * Entry point that hands out ElektraWeb modules.
 *
 * Modules register themselves with {@see extend()} which keeps this class
 * open for new ElektraWeb products without touching existing code.
 */
class ElektraWeb
{
    /**
     * @var array<string, class-string<Module>|Closure(Container): Module>
     */
    private array $modules = [];

    /**
     * @var array<string, Module>
     */
    private array $resolved = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Register a module under its name.
     *
     * @param  class-string<Module>|Closure(Container): Module  $concrete
     */
    public function extend(string $name, string|Closure $concrete): static
    {
        $this->modules[$name] = $concrete;
        unset($this->resolved[$name]);

        return $this;
    }

    /**
     * Resolve a module by name.
     */
    public function module(string $name): Module
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (! isset($this->modules[$name])) {
            throw UnknownModuleException::named($name);
        }

        $concrete = $this->modules[$name];

        $module = $concrete instanceof Closure
            ? $concrete($this->container)
            : $this->container->make($concrete);

        return $this->resolved[$name] = $module;
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * @return list<string>
     */
    public function modules(): array
    {
        return array_keys($this->modules);
    }

    /**
     * The Booking API module.
     */
    public function bookingApi(): BookingApi
    {
        /** @var BookingApi $module */
        $module = $this->module(BookingApi::name());

        return $module;
    }
}
