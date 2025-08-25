<?php

namespace Bmwsly\MondialRelayApi\Models;

/**
 * Modèle représentant une étiquette PDF Mondial Relay.
 *
 * Ce modèle contient les URLs de téléchargement des étiquettes PDF
 * dans les différents formats supportés par Mondial Relay.
 *
 * @author Bryan Meurisse
 * @version 1.1.0
 */
class Label
{
    /**
     * @param string $expeditionNumber Numéro d'expédition associé à l'étiquette
     * @param string $labelUrlA4 URL de téléchargement de l'étiquette au format A4
     * @param string $labelUrlA5 URL de téléchargement de l'étiquette au format A5
     * @param string $labelUrl10x15 URL de téléchargement de l'étiquette au format 10x15cm
     */
    public function __construct(
        public readonly string $expeditionNumber,
        public readonly string $labelUrlA4,
        public readonly string $labelUrlA5,
        public readonly string $labelUrl10x15,
    ) {
    }

    public static function fromApiResponse(string $expeditionNumber, object $response, string $baseUrl): self
    {
        return new self(
            expeditionNumber: $expeditionNumber,
            labelUrlA4: $baseUrl.$response->URL_Etiquette.'&format=A4',
            labelUrlA5: $baseUrl.$response->URL_Etiquette.'&format=A5',
            labelUrl10x15: $baseUrl.$response->URL_Etiquette.'&format=10x15',
        );
    }

    public static function fromLabelUrls(string $expeditionNumber, string $urlA4, string $urlA5, string $url10x15): self
    {
        return new self(
            expeditionNumber: $expeditionNumber,
            labelUrlA4: $urlA4,
            labelUrlA5: $urlA5,
            labelUrl10x15: $url10x15,
        );
    }

    public function toArray(): array
    {
        return [
            'expedition_number' => $this->expeditionNumber,
            'label_url_a4' => $this->labelUrlA4,
            'label_url_a5' => $this->labelUrlA5,
            'label_url_10x15' => $this->labelUrl10x15,
        ];
    }

    /**
     * Retourne l'URL de téléchargement pour le format demandé.
     *
     * @param string $format Format demandé ('A4', 'A5', '10x15')
     * @return string URL de téléchargement de l'étiquette PDF
     * @throws \InvalidArgumentException Si le format n'est pas supporté
     *
     * @example
     * $url = $label->getUrlByFormat('A4');
     * $pdfContent = file_get_contents($url);
     */
    public function getUrlByFormat(string $format): string
    {
        return match (strtoupper($format)) {
            'A4' => $this->labelUrlA4,
            'A5' => $this->labelUrlA5,
            '10X15' => $this->labelUrl10x15,
            default => throw new \InvalidArgumentException("Unsupported format: {$format}. Supported formats: A4, A5, 10x15"),
        };
    }

    /**
     * Retourne la liste des formats d'étiquettes disponibles.
     *
     * @return array Liste des formats supportés
     */
    public function getAvailableFormats(): array
    {
        return ['A4', 'A5', '10x15'];
    }

    /**
     * Vérifie si un format d'étiquette est supporté.
     *
     * @param string $format Format à vérifier
     * @return bool true si le format est supporté
     */
    public function hasFormat(string $format): bool
    {
        return in_array(strtoupper($format), ['A4', 'A5', '10X15']);
    }

    /**
     * Retourne toutes les URLs d'étiquettes dans un tableau associatif.
     *
     * @return array Tableau [format => url] pour tous les formats
     */
    public function getAllUrls(): array
    {
        return [
            'A4' => $this->labelUrlA4,
            'A5' => $this->labelUrlA5,
            '10x15' => $this->labelUrl10x15,
        ];
    }

    /**
     * Retourne des informations sur les formats d'étiquettes.
     *
     * @return array Informations détaillées sur chaque format
     */
    public function getFormatInfo(): array
    {
        return [
            'A4' => [
                'name' => 'A4',
                'description' => 'Format A4 standard (210x297mm)',
                'url' => $this->labelUrlA4,
                'recommended_for' => 'Impression bureau standard',
            ],
            'A5' => [
                'name' => 'A5',
                'description' => 'Format A5 compact (148x210mm)',
                'url' => $this->labelUrlA5,
                'recommended_for' => 'Économie de papier',
            ],
            '10x15' => [
                'name' => '10x15',
                'description' => 'Format 10x15cm compact',
                'url' => $this->labelUrl10x15,
                'recommended_for' => 'Étiquettes compactes, impression photo',
            ],
        ];
    }
}
