<?php

namespace Bmwsly\MondialRelayApi\Models;

/**
 * Modèle représentant une expédition Mondial Relay
 *
 * Ce modèle contient les informations de base d'une expédition créée
 * via l'API Mondial Relay. Le numéro d'expédition est l'élément clé
 * pour le suivi et la gestion des colis.
 *
 * @package Bmwsly\MondialRelayApi\Models
 * @author Bryan Meurisse
 * @version 1.1.0
 */
class Expedition
{
    /**
     * @param string $expeditionNumber Numéro unique d'expédition (OBLIGATOIRE pour le suivi)
     * @param string $agencyCode Code de l'agence Mondial Relay
     * @param string $group Groupe de tri
     * @param string $shuttle Code navette
     * @param string $agency Nom de l'agence
     * @param string $tourCode Code de tournée
     * @param string $deliveryMode Mode de livraison (24R, 24L, etc.)
     * @param array $barcodes Codes-barres associés à l'expédition
     */
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

    /**
     * Génère l'URL publique de suivi du colis
     *
     * @return string URL de suivi Mondial Relay
     */
    public function getTrackingUrl(): string
    {
        return 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition='.$this->expeditionNumber;
    }

    /**
     * Retourne le libellé français du mode de livraison
     *
     * @return string Description du mode de livraison
     */
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

    /**
     * Vérifie si la livraison se fait en point relais
     *
     * @return bool true si livraison en point relais
     */
    public function isRelayDelivery(): bool
    {
        return in_array($this->deliveryMode, ['24R', '24X']);
    }

    /**
     * Vérifie si la livraison se fait à domicile
     *
     * @return bool true si livraison à domicile
     */
    public function isHomeDelivery(): bool
    {
        return in_array($this->deliveryMode, ['24L', 'LD1', 'LDS']);
    }
}
