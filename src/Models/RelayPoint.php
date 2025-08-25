<?php

namespace Bmwsly\MondialRelayApi\Models;

use Illuminate\Support\Facades\Log;

/**
 * Modèle représentant un point relais Mondial Relay.
 *
 * Ce modèle contient toutes les informations nécessaires pour identifier
 * et utiliser un point relais dans vos expéditions.
 *
 * @author Bryan Meurisse
 * @version 1.1.0
 */
class RelayPoint
{
    /**
     * @param string $number Numéro unique du point relais (OBLIGATOIRE pour les expéditions)
     * @param string $name Nom commercial du point relais
     * @param string $address Adresse complète du point relais
     * @param string $postalCode Code postal du point relais
     * @param string $city Ville du point relais
     * @param string $country Code pays (FR, BE, ES, etc.)
     * @param float $latitude Latitude GPS pour géolocalisation
     * @param float $longitude Longitude GPS pour géolocalisation
     * @param float $distance Distance en mètres depuis le point de recherche
     * @param array $openingHours Horaires d'ouverture par jour de la semaine
     * @param string|null $photoUrl URL de la photo du point relais (optionnel)
     * @param string|null $mapUrl URL du plan d'accès (optionnel)
     */
    public function __construct(
        public readonly string $number,
        public readonly string $name,
        public readonly string $address,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $country,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $distance,
        public readonly array $openingHours,
        public readonly ?string $photoUrl = null,
        public readonly ?string $mapUrl = null,
    ) {}

    public static function fromApiResponse(object $relayPoint): self
    {
        $openingHours = new self(
            number: $relayPoint->Num,
            name: trim("{$relayPoint->LgAdr1} {$relayPoint->LgAdr2}"),
            address: trim("{$relayPoint->LgAdr3} {$relayPoint->LgAdr4}"),
            postalCode: $relayPoint->CP,
            city: $relayPoint->Ville,
            country: $relayPoint->Pays,
            latitude: (float) $relayPoint->Latitude,
            longitude: (float) $relayPoint->Longitude,
            distance: (float) $relayPoint->Distance,
            openingHours: [
                'monday' => self::parseOpeningHours($relayPoint->Horaires_Lundi ?? []),
                'tuesday' => self::parseOpeningHours($relayPoint->Horaires_Mardi ?? []),
                'wednesday' => self::parseOpeningHours($relayPoint->Horaires_Mercredi ?? []),
                'thursday' => self::parseOpeningHours($relayPoint->Horaires_Jeudi ?? []),
                'friday' => self::parseOpeningHours($relayPoint->Horaires_Vendredi ?? []),
                'saturday' => self::parseOpeningHours($relayPoint->Horaires_Samedi ?? []),
                'sunday' => self::parseOpeningHours($relayPoint->Horaires_Dimanche ?? []),
            ],
            photoUrl: $relayPoint->URL_Photo ?? null,
            mapUrl: $relayPoint->URL_Plan ?? null,
        );

        return $openingHours;
    }

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance' => $this->distance,
            'opening_hours' => $this->openingHours,
            'photo_url' => $this->photoUrl,
            'map_url' => $this->mapUrl,
        ];
    }

    /**
     * Retourne l'adresse complète formatée du point relais.
     *
     * @return string Adresse complète (adresse, code postal, ville)
     */
    public function getFullAddress(): string
    {
        return "{$this->address}, {$this->postalCode} {$this->city}";
    }

    /**
     * Vérifie si le point relais est ouvert aujourd'hui.
     *
     * @return bool true si ouvert aujourd'hui, false sinon
     */
    public function isOpenToday(): bool
    {
        $today = strtolower(date('l'));
        $dayMapping = [
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday',
        ];

        $todayHours = $this->openingHours[$dayMapping[$today]] ?? [];

        return !empty($todayHours);
    }

    /**
     * Retourne les horaires d'ouverture d'aujourd'hui.
     *
     * @return array Horaires du jour (format: [['open' => '0900', 'close' => '1800'], ...])
     */
    public function getTodayOpeningHours(): array
    {
        $today = strtolower(date('l'));
        $dayMapping = [
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday',
        ];

        return $this->openingHours[$dayMapping[$today]] ?? [];
    }

    /**
     * Retourne la distance formatée en kilomètres.
     *
     * @return string Distance formatée (ex: "1.5 km")
     */
    public function getFormattedDistance(): string
    {
        if ($this->distance < 1000) {
            return round($this->distance) . ' m';
        }

        return number_format($this->distance / 1000, 1) . ' km';
    }

    /**
     * Vérifie si le point relais est actuellement ouvert.
     *
     * @return bool true si ouvert maintenant, false sinon
     */
    public function isCurrentlyOpen(): bool
    {
        $todayHours = $this->getTodayOpeningHours();

        if (empty($todayHours)) {
            return false;
        }

        $currentTime = (int) date('Hi'); // Format HHMM

        foreach ($todayHours as $slot) {
            $openTime = (int) $slot['open'];
            $closeTime = (int) $slot['close'];

            if ($openTime > 0 && $closeTime > 0 && $currentTime >= $openTime && $currentTime <= $closeTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne les coordonnées GPS sous forme de tableau.
     *
     * @return array ['latitude' => float, 'longitude' => float]
     */
    public function getCoordinates(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Génère une URL Google Maps pour le point relais.
     *
     * @return string URL Google Maps
     */
    public function getGoogleMapsUrl(): string
    {
        return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }

    private static function parseOpeningHours($hours): array
    {

        $open = $hours->string[0] ?? null;
        $close = $hours->string[1] ?? null;

        $is24h = $open === '0001' && $close === '2359';


        $openingHours = [
            "is24h" => $is24h,
            "open" => substr($hours->string[0], 0, 2) . ':' . substr($hours->string[0], 2, 2),
            "close" => substr($hours->string[1], 0, 2) . ':' . substr($hours->string[1], 2, 2),
        ];


        return $openingHours;

    }
}
