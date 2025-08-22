<?php

namespace Bmwsly\MondialRelayApi;

use Bmwsly\MondialRelayApi\Services\MondialRelayService;
use Illuminate\Support\ServiceProvider;

class MondialRelayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mondialrelay.php', 'mondialrelay');

        $this->app->singleton(MondialRelayClient::class, fn ($app) => new MondialRelayClient(
            $app['config']['mondialrelay.enseigne'],
            $app['config']['mondialrelay.private_key'],
            $app['config']['mondialrelay.test_mode'] ?? true,
            $app['config']['mondialrelay.api_url'] ?? null
        ));

        $this->app->singleton(MondialRelayService::class, fn ($app) => new MondialRelayService($app[MondialRelayClient::class]));
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/mondialrelay.php' => $this->app->configPath('mondialrelay.php'),
        ], 'config');
    }
}
