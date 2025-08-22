<?php

namespace Bmwsly\MondialRelayApi\Models;

class Expedition
{
    public function __construct(
        public readonly string $expeditionNumber,
        public readonly string $agencyCode,
        public readonly string $group,
        public readonly string $shuttle,
        public readonly string $agency,
        public readonly string $tourCode,
        public readonly string $deliveryMode,
        public readonly array $barcodes = [],
    ) {
    }

    public static function fromApiResponse(object $response): self
    {
        return new self(
            expeditionNumber: $response->ExpeditionNum,
            agencyCode: $response->TRI_AgenceCode,
            group: $response->TRI_Groupe,
            shuttle: $response->TRI_Navette,
            agency: $response->TRI_Agence,
            tourCode: $response->TRI_TourneeCode,
            deliveryMode: $response->TRI_LivraisonMode,
            barcodes: $response->CodesBarres ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'expedition_number' => $this->expeditionNumber,
            'agency_code' => $this->agencyCode,
            'group' => $this->group,
            'shuttle' => $this->shuttle,
            'agency' => $this->agency,
            'tour_code' => $this->tourCode,
            'delivery_mode' => $this->deliveryMode,
            'barcodes' => $this->barcodes,
        ];
    }

    public function getTrackingUrl(): string
    {
        return 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition='.$this->expeditionNumber;
    }

    public function getDeliveryModeLabel(): string
    {
        $modes = [
            '24R' => 'Livraison en point relais (24h-48h)',
            '24L' => 'Livraison à domicile (24h-48h)',
            '24X' => 'Livraison express en point relais',
            'LD1' => 'Livraison à domicile (J+1)',
            'LDS' => 'Livraison à domicile le samedi',
            'DRI' => 'Drive',
        ];

        return $modes[$this->deliveryMode] ?? $this->deliveryMode;
    }

    public function isRelayDelivery(): bool
    {
        return in_array($this->deliveryMode, ['24R', '24X']);
    }

    public function isHomeDelivery(): bool
    {
        return in_array($this->deliveryMode, ['24L', 'LD1', 'LDS']);
    }
}
