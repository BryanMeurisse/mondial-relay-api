<?php

namespace Bmwsly\MondialRelayApi\Models;

class LabelBatch
{
    public function __construct(
        /** @var string[] */
        public readonly array $expeditionNumbers,
        public readonly string $pdfUrlA4,
        public readonly string $pdfUrlA5,
        public readonly string $pdfUrl10x15,
    ) {
    }

    public static function fromApiResponse(array $expeditionNumbers, object $response, string $baseUrl): self
    {
        return new self(
            expeditionNumbers: $expeditionNumbers,
            pdfUrlA4: $baseUrl.$response->URL_PDF_A4,
            pdfUrlA5: $baseUrl.$response->URL_PDF_A5,
            pdfUrl10x15: $baseUrl.$response->URL_PDF_10x15,
        );
    }

    public function toArray(): array
    {
        return [
            'expedition_numbers' => $this->expeditionNumbers,
            'pdf_url_a4' => $this->pdfUrlA4,
            'pdf_url_a5' => $this->pdfUrlA5,
            'pdf_url_10x15' => $this->pdfUrl10x15,
        ];
    }

    public function getPdfUrlByFormat(string $format): string
    {
        return match (strtoupper($format)) {
            'A4' => $this->pdfUrlA4,
            'A5' => $this->pdfUrlA5,
            '10X15' => $this->pdfUrl10x15,
            default => throw new \InvalidArgumentException("Unsupported format: {$format}. Supported formats: A4, A5, 10x15"),
        };
    }

    public function getExpeditionCount(): int
    {
        return count($this->expeditionNumbers);
    }

    public function containsExpedition(string $expeditionNumber): bool
    {
        return in_array($expeditionNumber, $this->expeditionNumbers);
    }

    public function getAvailableFormats(): array
    {
        return ['A4', 'A5', '10x15'];
    }
}
