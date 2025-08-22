<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Helpers;

use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use PHPUnit\Framework\TestCase;

class MondialRelayHelperTest extends TestCase
{
    public function test_get_delivery_modes()
    {
        $modes = MondialRelayHelper::getDeliveryModes();

        $this->assertIsArray($modes);
        $this->assertArrayHasKey('24R', $modes);
        $this->assertArrayHasKey('24L', $modes);
        $this->assertEquals('Livraison en point relais (24h-48h)', $modes['24R']);
    }

    public function test_get_delivery_mode_label()
    {
        $label = MondialRelayHelper::getDeliveryModeLabel('24R');
        $this->assertEquals('Livraison en point relais (24h-48h)', $label);

        $unknownLabel = MondialRelayHelper::getDeliveryModeLabel('UNKNOWN');
        $this->assertEquals('UNKNOWN', $unknownLabel);
    }

    public function test_requires_relay_point()
    {
        $this->assertTrue(MondialRelayHelper::requiresRelayPoint('24R'));
        $this->assertFalse(MondialRelayHelper::requiresRelayPoint('24L')); // Home delivery doesn't require relay
        $this->assertTrue(MondialRelayHelper::requiresRelayPoint('24X'));
        $this->assertFalse(MondialRelayHelper::requiresRelayPoint('LD1'));
        $this->assertFalse(MondialRelayHelper::requiresRelayPoint('DRI'));
    }

    public function test_is_valid_french_postal_code()
    {
        $this->assertTrue(MondialRelayHelper::isValidFrenchPostalCode('75001'));
        $this->assertTrue(MondialRelayHelper::isValidFrenchPostalCode('69000'));
        $this->assertFalse(MondialRelayHelper::isValidFrenchPostalCode('7500'));
        $this->assertFalse(MondialRelayHelper::isValidFrenchPostalCode('750001'));
        $this->assertFalse(MondialRelayHelper::isValidFrenchPostalCode('ABCDE'));
    }

    public function test_is_valid_relay_number()
    {
        $this->assertTrue(MondialRelayHelper::isValidRelayNumber('123456'));
        $this->assertTrue(MondialRelayHelper::isValidRelayNumber('000001'));
        $this->assertFalse(MondialRelayHelper::isValidRelayNumber('12345'));
        $this->assertFalse(MondialRelayHelper::isValidRelayNumber('1234567'));
        $this->assertFalse(MondialRelayHelper::isValidRelayNumber('ABCDEF'));
    }

    public function test_format_weight()
    {
        $this->assertEquals('500g', MondialRelayHelper::formatWeight(500));
        $this->assertEquals('1.00kg', MondialRelayHelper::formatWeight(1000));
        $this->assertEquals('2.50kg', MondialRelayHelper::formatWeight(2500));
    }

    public function test_convert_kg_to_grams()
    {
        $this->assertEquals(1000, MondialRelayHelper::convertKgToGrams(1.0));
        $this->assertEquals(2500, MondialRelayHelper::convertKgToGrams(2.5));
        $this->assertEquals(500, MondialRelayHelper::convertKgToGrams(0.5));
    }

    public function test_format_distance()
    {
        $this->assertEquals('500m', MondialRelayHelper::formatDistance(0.5));
        $this->assertEquals('1.0km', MondialRelayHelper::formatDistance(1.0));
        $this->assertEquals('2.5km', MondialRelayHelper::formatDistance(2.5));
    }

    public function test_get_supported_countries()
    {
        $countries = MondialRelayHelper::getSupportedCountries();

        $this->assertIsArray($countries);
        $this->assertArrayHasKey('FR', $countries);
        $this->assertArrayHasKey('BE', $countries);
        $this->assertEquals('France', $countries['FR']);
        $this->assertEquals('Belgique', $countries['BE']);
    }

    public function test_get_country_name()
    {
        $this->assertEquals('France', MondialRelayHelper::getCountryName('FR'));
        $this->assertEquals('Belgique', MondialRelayHelper::getCountryName('BE'));
        $this->assertEquals('XX', MondialRelayHelper::getCountryName('XX'));
    }

    public function test_is_valid_country_code()
    {
        $this->assertTrue(MondialRelayHelper::isValidCountryCode('FR'));
        $this->assertTrue(MondialRelayHelper::isValidCountryCode('BE'));
        $this->assertFalse(MondialRelayHelper::isValidCountryCode('XX'));
        $this->assertFalse(MondialRelayHelper::isValidCountryCode('USA'));
    }

    public function test_calculate_shipping_cost()
    {
        $cost = MondialRelayHelper::calculateShippingCost(500, '24R');
        $this->assertEquals(4.90, $cost);

        $cost = MondialRelayHelper::calculateShippingCost(1500, '24R');
        $this->assertEquals(6.40, $cost); // 4.90 + 1.50 for extra kg

        $cost = MondialRelayHelper::calculateShippingCost(500, '24L');
        $this->assertEquals(6.90, $cost);
    }

    public function test_get_tracking_url()
    {
        $url = MondialRelayHelper::getTrackingUrl('12345678901234');
        $this->assertEquals('https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition=12345678901234', $url);
    }

    public function test_format_address()
    {
        $this->assertEquals('123 Rue de la Paix', MondialRelayHelper::formatAddress('  123   Rue  de  la   Paix  '));
        $this->assertEquals('Simple Address', MondialRelayHelper::formatAddress('Simple Address'));
    }

    public function test_format_phone_number()
    {
        $this->assertEquals('0123456789', MondialRelayHelper::formatPhoneNumber('01 23 45 67 89'));
        $this->assertEquals('0123456789', MondialRelayHelper::formatPhoneNumber('01.23.45.67.89'));
        $this->assertEquals('0123456789', MondialRelayHelper::formatPhoneNumber('0123456789'));
        $this->assertEquals('0123456789', MondialRelayHelper::formatPhoneNumber('123456789'));
    }

    public function test_validate_expedition_params()
    {
        $validParams = [
            'delivery_mode' => '24R',
            'weight' => 1000,
            'sender' => [
                'name' => 'Test Sender',
                'address' => '123 Test Street',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
                'phone' => '0123456789',
            ],
            'recipient' => [
                'name' => 'Test Recipient',
                'address' => '456 Test Avenue',
                'city' => 'Lyon',
                'postal_code' => '69001',
                'country' => 'FR',
                'phone' => '0987654321',
            ],
            'relay_number' => '123456',
            'relay_country' => 'FR',
        ];

        $errors = MondialRelayHelper::validateExpeditionParams($validParams);
        $this->assertEmpty($errors);

        $invalidParams = [
            'delivery_mode' => 'INVALID',
            'weight' => 1000,
        ];

        $errors = MondialRelayHelper::validateExpeditionParams($invalidParams);
        $this->assertNotEmpty($errors);
    }
}
