<?php

namespace Bmwsly\MondialRelayApi\Models;

class Label
{
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

    public function getUrlByFormat(string $format): string
    {
        return match (strtoupper($format)) {
            'A4' => $this->labelUrlA4,
            'A5' => $this->labelUrlA5,
            '10X15' => $this->labelUrl10x15,
            default => throw new \InvalidArgumentException("Unsupported format: {$format}. Supported formats: A4, A5, 10x15"),
        };
    }

    public function getAvailableFormats(): array
    {
        return ['A4', 'A5', '10x15'];
    }

    public function hasFormat(string $format): bool
    {
        return in_array(strtoupper($format), ['A4', 'A5', '10X15']);
    }
}
