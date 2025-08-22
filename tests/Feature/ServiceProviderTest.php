<?php

namespace Bmwsly\MondialRelayApi\Tests\Feature;

use Bmwsly\MondialRelayApi\Facades\MondialRelay;
use Bmwsly\MondialRelayApi\Facades\MondialRelayService as MondialRelayServiceFacade;
use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Services\MondialRelayService;
use Bmwsly\MondialRelayApi\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_client()
    {
        $client = $this->app->make(MondialRelayClient::class);
        $this->assertInstanceOf(MondialRelayClient::class, $client);
    }

    public function test_service_provider_registers_service()
    {
        $service = $this->app->make(MondialRelayService::class);
        $this->assertInstanceOf(MondialRelayService::class, $service);
    }

    public function test_facades_are_registered()
    {
        $this->assertTrue(class_exists(MondialRelay::class));
        $this->assertTrue(class_exists(MondialRelayServiceFacade::class));
    }

    public function test_config_is_merged()
    {
        $this->assertEquals('BDTEST13', config('mondialrelay.enseigne'));
        $this->assertEquals('PrivateK', config('mondialrelay.private_key'));
        $this->assertTrue(config('mondialrelay.test_mode'));
        $this->assertEquals('https://api.mondialrelay.com/Web_Services.asmx', config('mondialrelay.api_url'));
    }

    public function test_client_is_singleton()
    {
        $client1 = $this->app->make(MondialRelayClient::class);
        $client2 = $this->app->make(MondialRelayClient::class);

        $this->assertSame($client1, $client2);
    }

    public function test_service_is_singleton()
    {
        $service1 = $this->app->make(MondialRelayService::class);
        $service2 = $this->app->make(MondialRelayService::class);

        $this->assertSame($service1, $service2);
    }
}
