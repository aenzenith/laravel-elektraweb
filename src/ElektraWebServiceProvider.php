<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb;

use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\BookingApiConfig;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

class ElektraWebServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/elektraweb.php', 'elektraweb');

        $this->app->singleton(ElektraWeb::class, function (Container $app): ElektraWeb {
            $manager = new ElektraWeb($app);

            $manager->extend(BookingApi::name(), BookingApi::class);

            return $manager;
        });

        $this->app->alias(ElektraWeb::class, 'elektraweb');

        $this->registerBookingApi();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/elektraweb.php' => $this->app->configPath('elektraweb.php'),
            ], ['elektraweb', 'elektraweb-config']);
        }

        $this->registerAboutCommand();
    }

    private function registerBookingApi(): void
    {
        $this->app->singleton(BookingApiConfig::class, function (Container $app): BookingApiConfig {
            /** @var Repository $config */
            $config = $app->make('config');

            /** @var array<string, mixed> $values */
            $values = $config->get('elektraweb.'.BookingApi::name(), []);

            return BookingApiConfig::fromArray($values);
        });

        $this->app->singleton(BookingApi::class);
        $this->app->alias(BookingApi::class, 'elektraweb.booking_api');
    }

    private function registerAboutCommand(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('ElektraWeb', fn () => [
            'Booking API URL' => fn () => (string) $this->app->make('config')->get('elektraweb.booking_api.base_url'),
            'Booking API Hotel' => fn () => (string) ($this->app->make('config')->get('elektraweb.booking_api.hotel_id') ?: '-'),
            'Booking API Auth' => fn () => (string) $this->app->make('config')->get('elektraweb.booking_api.auth.driver'),
        ]);
    }
}
