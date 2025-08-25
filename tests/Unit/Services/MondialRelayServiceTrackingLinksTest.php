<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Services;

use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Services\MondialRelayService;
use Bmwsly\MondialRelayApi\Tests\TestCase;
use Mockery;

class MondialRelayServiceTrackingLinksTest extends TestCase
{
    private MondialRelayService $service;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(MondialRelayClient::class);
        $this->service = new MondialRelayService($this->mockClient);
    }

    public function test_generate_tracking_url()
    {
        $expeditionNumber = '12345678';
        $expectedUrl = 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition=12345678';

        $this->mockClient
            ->shouldReceive('generateTrackingUrl')
            ->once()
            ->with($expeditionNumber)
            ->andReturn($expectedUrl);

        $result = $this->service->generateTrackingUrl($expeditionNumber);

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_generate_connect_tracing_link()
    {
        $expeditionNumber = '12345678';
        $userLogin = 'test@example.com';
        $expectedUrl = 'http://connect.mondialrelay.com/BDTEST13/Expedition/Afficher?numeroExpedition=12345678&login=test@example.com&ts=1234567890&crc=ABCDEF123456';

        $this->mockClient
            ->shouldReceive('generateConnectTracingLink')
            ->once()
            ->with($expeditionNumber, $userLogin)
            ->andReturn($expectedUrl);

        $result = $this->service->generateConnectTracingLink($expeditionNumber, $userLogin);

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_generate_permalink_tracing_link()
    {
        $expeditionNumber = '12345678';
        $language = 'fr';
        $country = 'fr';
        $expectedUrl = 'http://www.mondialrelay.fr/public/permanent/tracking.aspx?ens=BDTEST1311&exp=12345678&pays=fr&language=fr&crc=ABCDEF123456';

        $this->mockClient
            ->shouldReceive('generatePermalinkTracingLink')
            ->once()
            ->with($expeditionNumber, $language, $country)
            ->andReturn($expectedUrl);

        $result = $this->service->generatePermalinkTracingLink($expeditionNumber, $language, $country);

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_generate_permalink_tracing_link_with_defaults()
    {
        $expeditionNumber = '12345678';
        $expectedUrl = 'http://www.mondialrelay.fr/public/permanent/tracking.aspx?ens=BDTEST1311&exp=12345678&pays=fr&language=fr&crc=ABCDEF123456';

        $this->mockClient
            ->shouldReceive('generatePermalinkTracingLink')
            ->once()
            ->with($expeditionNumber, 'fr', 'fr')
            ->andReturn($expectedUrl);

        $result = $this->service->generatePermalinkTracingLink($expeditionNumber);

        $this->assertEquals($expectedUrl, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
