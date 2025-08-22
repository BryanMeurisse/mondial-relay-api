<?php

namespace Bmwsly\MondialRelayApi\Models;

class ExpeditionWithLabel
{
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

    public function getTrackingUrl(): string
    {
        return 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition='.$this->expeditionNumber;
    }

    public function getLabelUrl(string $format = 'A4'): string
    {
        return $this->label->getUrlByFormat($format);
    }

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
}
