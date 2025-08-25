<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Helpers;

use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use Bmwsly\MondialRelayApi\Tests\TestCase;

class MondialRelayHelperTrackingTest extends TestCase
{
    public function test_get_tracking_url()
    {
        $expeditionNumber = '12345678';
        $expectedUrl = 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition=12345678';

        $result = MondialRelayHelper::getTrackingUrl($expeditionNumber);

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_get_connect_tracing_link()
    {
        $expeditionNumber = '12345678';
        $userLogin = 'test@example.com';
        $enseigne = 'BDTEST13';
        $password = 'testpassword';

        $result = MondialRelayHelper::getConnectTracingLink($expeditionNumber, $userLogin, $enseigne, $password);

        $this->assertStringStartsWith('http://connect.mondialrelay.com/BDTEST13/Expedition/Afficher', $result);
        $this->assertStringContainsString('numeroExpedition=12345678', $result);
        $this->assertStringContainsString('login=test@example.com', $result);
        $this->assertStringContainsString('&ts=', $result);
        $this->assertStringContainsString('&crc=', $result);
    }

    public function test_get_permalink_tracing_link()
    {
        $expeditionNumber = '12345678';
        $enseigne = 'BDTEST13';
        $brandId = '11';
        $privateKey = 'TestAPI1key';
        $language = 'fr';
        $country = 'fr';

        $result = MondialRelayHelper::getPermalinkTracingLink(
            $expeditionNumber,
            $enseigne,
            $brandId,
            $privateKey,
            $language,
            $country
        );

        $this->assertStringStartsWith('http://www.mondialrelay.fr/public/permanent/tracking.aspx', $result);
        $this->assertStringContainsString('ens=BDTEST1311', $result);
        $this->assertStringContainsString('exp=12345678', $result);
        $this->assertStringContainsString('pays=fr', $result);
        $this->assertStringContainsString('language=fr', $result);
        $this->assertStringContainsString('&crc=', $result);
    }

    public function test_get_permalink_tracing_link_with_different_language()
    {
        $expeditionNumber = '12345678';
        $enseigne = 'BDTEST13';
        $brandId = '11';
        $privateKey = 'TestAPI1key';
        $language = 'en';
        $country = 'gb';

        $result = MondialRelayHelper::getPermalinkTracingLink(
            $expeditionNumber,
            $enseigne,
            $brandId,
            $privateKey,
            $language,
            $country
        );

        $this->assertStringContainsString('pays=gb', $result);
        $this->assertStringContainsString('language=en', $result);
    }

    public function test_security_hash_generation_consistency()
    {
        $expeditionNumber = '12345678';
        $enseigne = 'BDTEST13';
        $brandId = '11';
        $privateKey = 'TestAPI1key';

        // Generate the same link twice
        $result1 = MondialRelayHelper::getPermalinkTracingLink($expeditionNumber, $enseigne, $brandId, $privateKey);
        $result2 = MondialRelayHelper::getPermalinkTracingLink($expeditionNumber, $enseigne, $brandId, $privateKey);

        // Should be identical (same hash for same parameters)
        $this->assertEquals($result1, $result2);
    }

    public function test_connect_link_timestamp_changes()
    {
        $expeditionNumber = '12345678';
        $userLogin = 'test@example.com';
        $enseigne = 'BDTEST13';
        $password = 'testpassword';

        // Generate two links with a small delay
        $result1 = MondialRelayHelper::getConnectTracingLink($expeditionNumber, $userLogin, $enseigne, $password);
        sleep(1);
        $result2 = MondialRelayHelper::getConnectTracingLink($expeditionNumber, $userLogin, $enseigne, $password);

        // Should be different due to timestamp
        $this->assertNotEquals($result1, $result2);

        // But both should contain the same base elements
        $this->assertStringContainsString('numeroExpedition=12345678', $result1);
        $this->assertStringContainsString('numeroExpedition=12345678', $result2);
        $this->assertStringContainsString('login=test@example.com', $result1);
        $this->assertStringContainsString('login=test@example.com', $result2);
    }
}
