<?php

namespace Bmwsly\MondialRelayApi\Models;

/**
 * Modèle représentant une expédition avec étiquette PDF
 *
 * Ce modèle combine une expédition et son étiquette PDF générée.
 * Il permet de télécharger directement l'étiquette dans différents formats
 * et d'accéder aux informations de suivi.
 *
 * @package Bmwsly\MondialRelayApi\Models
 * @author Bryan Meurisse
 * @version 1.1.0
 */
class ExpeditionWithLabel
{
    /**
     * @param string $expeditionNumber Numéro unique d'expédition (pour le suivi)
     * @param Label $label Objet étiquette avec URLs de téléchargement
     */
    public function __construct(
        public readonly string $expeditionNumber,
        public readonly Label $label,
    ) {
    }

    public static function fromApiResponse(object $response, string $baseUrl): self
    {
        $expeditionNumber = $response->ExpeditionNum;
        $label = Label::fromApiResponse($expeditionNumber, $response, $baseUrl);

        return new self(
            expeditionNumber: $expeditionNumber,
            label: $label,
        );
    }

    public function toArray(): array
    {
        return [
            'expedition_number' => $this->expeditionNumber,
            'label' => $this->label->toArray(),
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
     * Retourne l'URL de téléchargement de l'étiquette dans le format demandé
     *
     * @param string $format Format de l'étiquette ('A4', 'A5', '10x15')
     * @return string URL de téléchargement de l'étiquette PDF
     */
    public function getLabelUrl(string $format = 'A4'): string
    {
        return $this->label->getUrlByFormat($format);
    }

    /**
     * Télécharge directement le contenu PDF de l'étiquette
     *
     * Cette méthode télécharge l'étiquette PDF depuis les serveurs Mondial Relay
     * et retourne le contenu binaire du fichier PDF.
     *
     * @param string $format Format de l'étiquette ('A4', 'A5', '10x15')
     * @return string Contenu binaire du PDF
     * @throws \RuntimeException Si le téléchargement échoue
     *
     * @example
     * // Télécharger et sauvegarder l'étiquette A4
     * $pdfContent = $expeditionWithLabel->downloadLabel('A4');
     * file_put_contents('etiquette.pdf', $pdfContent);
     */
    public function downloadLabel(string $format = 'A4'): string
    {
        $url = $this->getLabelUrl($format);

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; Laravel Mondial Relay Package)',
            ],
        ]);

        $content = file_get_contents($url, false, $context);

        if ($content === false) {
            throw new \RuntimeException("Failed to download label PDF from: {$url}");
        }

        return $content;
    }

    /**
     * Sauvegarde l'étiquette PDF dans un fichier
     *
     * @param string $filename Nom du fichier de destination
     * @param string $format Format de l'étiquette ('A4', 'A5', '10x15')
     * @return bool true si la sauvegarde a réussi
     * @throws \RuntimeException Si le téléchargement ou la sauvegarde échoue
     *
     * @example
     * $expeditionWithLabel->saveLabelToFile('etiquette_commande_123.pdf', 'A4');
     */
    public function saveLabelToFile(string $filename, string $format = 'A4'): bool
    {
        $content = $this->downloadLabel($format);

        $result = file_put_contents($filename, $content);

        if ($result === false) {
            throw new \RuntimeException("Failed to save label to file: {$filename}");
        }

        return true;
    }

    /**
     * Retourne tous les formats d'étiquettes disponibles avec leurs URLs
     *
     * @return array Tableau associatif [format => url]
     */
    public function getAllLabelUrls(): array
    {
        return [
            'A4' => $this->label->labelUrlA4,
            'A5' => $this->label->labelUrlA5,
            '10x15' => $this->label->labelUrl10x15,
        ];
    }
}
