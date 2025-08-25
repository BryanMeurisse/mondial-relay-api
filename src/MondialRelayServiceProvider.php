<?php

namespace Bmwsly\MondialRelayApi;

use Bmwsly\MondialRelayApi\Clients\MondialRelayHybridClient;
use Bmwsly\MondialRelayApi\Debug\MondialRelayDebugger;
use Bmwsly\MondialRelayApi\Services\MondialRelayService;
use Illuminate\Support\ServiceProvider;

class MondialRelayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mondialrelay.php', 'mondialrelay');

        // Register debugger
        $this->app->singleton(MondialRelayDebugger::class, fn ($app) => new MondialRelayDebugger(
            $app['config']['mondialrelay.debug.enabled'] ?? false,
            $app['config']['mondialrelay.debug.log_to_file'] ?? true,
            $app['config']['mondialrelay.debug.display_in_browser'] ?? false
        ));

        // Register hybrid client (supports both SOAP and REST)
        $this->app->singleton(MondialRelayHybridClient::class, fn ($app) => new MondialRelayHybridClient(
            $app['config']['mondialrelay.enseigne'],
            $app['config']['mondialrelay.private_key'],
            $app['config']['mondialrelay.test_mode'] ?? true,
            $app['config']['mondialrelay.api_url'] ?? null,
            $app['config']['mondialrelay.api_v2.enabled'] ? $app['config']['mondialrelay.api_v2'] : null,
            $app['config']['mondialrelay.api_v2.enabled'] ?? false,
            $app[MondialRelayDebugger::class]
        ));

        // Keep backward compatibility with MondialRelayClient
        $this->app->singleton(MondialRelayClient::class, fn ($app) => $app[MondialRelayHybridClient::class]->getSoapClient());

        $this->app->singleton(MondialRelayService::class, fn ($app) => new MondialRelayService($app[MondialRelayClient::class]));
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/mondialrelay.php' => $this->app->configPath('mondialrelay.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bmwsly\MondialRelayApi\Console\Commands\MondialRelayDiagnoseCommand::class,
            ]);
        }

        // Configure logging channel
        $this->configureLogging();
    }

    /**
     * Configure logging channel for Mondial Relay.
     */
    private function configureLogging(): void
    {
        $config = $this->app['config'];

        $config->set('logging.channels.mondial_relay', [
            'driver' => 'daily',
            'path' => storage_path('logs/mondial-relay.log'),
            'level' => 'debug',
            'days' => 14,
            'replace_placeholders' => true,
        ]);
    }
}
