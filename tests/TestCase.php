<?php

namespace Bmwsly\MondialRelayApi\Tests;

use Bmwsly\MondialRelayApi\MondialRelayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            MondialRelayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MondialRelay' => \Bmwsly\MondialRelayApi\Facades\MondialRelay::class,
            'MondialRelayService' => \Bmwsly\MondialRelayApi\Facades\MondialRelayService::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('mondialrelay.enseigne', 'BDTEST13');
        $app['config']->set('mondialrelay.private_key', 'PrivateK');
        $app['config']->set('mondialrelay.test_mode', true);
        $app['config']->set('mondialrelay.api_url', 'https://api.mondialrelay.com/Web_Services.asmx');
    }
}
