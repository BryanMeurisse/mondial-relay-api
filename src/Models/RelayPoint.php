<?php

namespace Bmwsly\MondialRelayApi\Models;

class RelayPoint
{
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
    ) {
    }

    public static function fromApiResponse(object $relayPoint): self
    {
        return new self(
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
                'monday' => self::parseOpeningHours($relayPoint->Horaire_Lundi ?? []),
                'tuesday' => self::parseOpeningHours($relayPoint->Horaire_Mardi ?? []),
                'wednesday' => self::parseOpeningHours($relayPoint->Horaire_Mercredi ?? []),
                'thursday' => self::parseOpeningHours($relayPoint->Horaire_Jeudi ?? []),
                'friday' => self::parseOpeningHours($relayPoint->Horaire_Vendredi ?? []),
                'saturday' => self::parseOpeningHours($relayPoint->Horaire_Samedi ?? []),
                'sunday' => self::parseOpeningHours($relayPoint->Horaire_Dimanche ?? []),
            ],
            photoUrl: $relayPoint->URL_Photo ?? null,
            mapUrl: $relayPoint->URL_Plan ?? null,
        );
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

    public function getFullAddress(): string
    {
        return "{$this->address}, {$this->postalCode} {$this->city}";
    }

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

    private static function parseOpeningHours($hours): array
    {
        if (!is_array($hours)) {
            return [];
        }

        return array_map(fn ($slot) => [
            'open' => $slot->string[0] ?? '',
            'close' => $slot->string[1] ?? '',
        ], $hours);
    }
}
