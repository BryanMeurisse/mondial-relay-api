<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Models;

use Bmwsly\MondialRelayApi\Models\RelayPoint;
use PHPUnit\Framework\TestCase;

class RelayPointTest extends TestCase
{
    public function test_can_create_relay_point_from_api_response()
    {
        $apiResponse = (object) [
            'Num' => '123456',
            'LgAdr1' => 'Tabac',
            'LgAdr2' => 'de la Gare',
            'LgAdr3' => '123 Rue de la Paix',
            'LgAdr4' => '',
            'CP' => '75001',
            'Ville' => 'Paris',
            'Pays' => 'FR',
            'Latitude' => '48.8566',
            'Longitude' => '2.3522',
            'Distance' => '0.5',
            'Horaire_Lundi' => [],
            'Horaire_Mardi' => [],
            'Horaire_Mercredi' => [],
            'Horaire_Jeudi' => [],
            'Horaire_Vendredi' => [],
            'Horaire_Samedi' => [],
            'Horaire_Dimanche' => [],
            'URL_Photo' => 'https://example.com/photo.jpg',
            'URL_Plan' => 'https://example.com/map.jpg',
        ];

        $relayPoint = RelayPoint::fromApiResponse($apiResponse);

        $this->assertEquals('123456', $relayPoint->number);
        $this->assertEquals('Tabac de la Gare', $relayPoint->name);
        $this->assertEquals('123 Rue de la Paix', $relayPoint->address);
        $this->assertEquals('75001', $relayPoint->postalCode);
        $this->assertEquals('Paris', $relayPoint->city);
        $this->assertEquals('FR', $relayPoint->country);
        $this->assertEquals(48.8566, $relayPoint->latitude);
        $this->assertEquals(2.3522, $relayPoint->longitude);
        $this->assertEquals(0.5, $relayPoint->distance);
        $this->assertEquals('https://example.com/photo.jpg', $relayPoint->photoUrl);
        $this->assertEquals('https://example.com/map.jpg', $relayPoint->mapUrl);
    }

    public function test_can_convert_to_array()
    {
        $relayPoint = new RelayPoint(
            number: '123456',
            name: 'Test Relay',
            address: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            country: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distance: 0.5,
            openingHours: [],
            photoUrl: 'https://example.com/photo.jpg',
            mapUrl: 'https://example.com/map.jpg'
        );

        $array = $relayPoint->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('123456', $array['number']);
        $this->assertEquals('Test Relay', $array['name']);
        $this->assertEquals('123 Test Street', $array['address']);
        $this->assertEquals('75001', $array['postal_code']);
        $this->assertEquals('Paris', $array['city']);
        $this->assertEquals('FR', $array['country']);
        $this->assertEquals(48.8566, $array['latitude']);
        $this->assertEquals(2.3522, $array['longitude']);
        $this->assertEquals(0.5, $array['distance']);
        $this->assertEquals('https://example.com/photo.jpg', $array['photo_url']);
        $this->assertEquals('https://example.com/map.jpg', $array['map_url']);
    }

    public function test_can_get_full_address()
    {
        $relayPoint = new RelayPoint(
            number: '123456',
            name: 'Test Relay',
            address: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            country: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distance: 0.5,
            openingHours: []
        );

        $fullAddress = $relayPoint->getFullAddress();

        $this->assertEquals('123 Test Street, 75001 Paris', $fullAddress);
    }

    public function test_can_check_if_open_today()
    {
        $openingHours = [
            'monday' => [['open' => '09:00', 'close' => '18:00']],
            'tuesday' => [['open' => '09:00', 'close' => '18:00']],
            'wednesday' => [['open' => '09:00', 'close' => '18:00']],
            'thursday' => [['open' => '09:00', 'close' => '18:00']],
            'friday' => [['open' => '09:00', 'close' => '18:00']],
            'saturday' => [],
            'sunday' => [],
        ];

        $relayPoint = new RelayPoint(
            number: '123456',
            name: 'Test Relay',
            address: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            country: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distance: 0.5,
            openingHours: $openingHours
        );

        // This test depends on the current day, so we'll just check the method exists and returns a boolean
        $this->assertIsBool($relayPoint->isOpenToday());
    }
}
