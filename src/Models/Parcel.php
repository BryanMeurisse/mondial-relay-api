<?php

namespace Bmwsly\MondialRelayApi\Models;

class Parcel
{
    public function __construct(
        public int $weightInGrams,
        public string $content = 'Produit e-commerce',
        public ?int $lengthInCm = null,
        public ?int $widthInCm = null,
        public ?int $heightInCm = null,
        public ?float $value = null,
        public string $currency = 'EUR',
        public ?string $reference = null
    ) {
    }

    /**
     * Create parcel from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            weightInGrams: $data['weight'] ?? $data['weight_in_grams'] ?? 1000,
            content: $data['content'] ?? 'Produit e-commerce',
            lengthInCm: $data['length'] ?? $data['length_in_cm'] ?? null,
            widthInCm: $data['width'] ?? $data['width_in_cm'] ?? null,
            heightInCm: $data['height'] ?? $data['height_in_cm'] ?? null,
            value: $data['value'] ?? null,
            currency: $data['currency'] ?? 'EUR',
            reference: $data['reference'] ?? null
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'weight_in_grams' => $this->weightInGrams,
            'content' => $this->content,
            'length_in_cm' => $this->lengthInCm,
            'width_in_cm' => $this->widthInCm,
            'height_in_cm' => $this->heightInCm,
            'value' => $this->value,
            'currency' => $this->currency,
            'reference' => $this->reference,
        ];
    }

    /**
     * Get weight in kilograms.
     */
    public function getWeightInKg(): float
    {
        return $this->weightInGrams / 1000;
    }

    /**
     * Get formatted weight.
     */
    public function getFormattedWeight(): string
    {
        if ($this->weightInGrams < 1000) {
            return $this->weightInGrams.'g';
        }

        return number_format($this->getWeightInKg(), 2).'kg';
    }

    /**
     * Get dimensions string.
     */
    public function getDimensionsString(): ?string
    {
        if (!$this->lengthInCm || !$this->widthInCm || !$this->heightInCm) {
            return null;
        }

        return "{$this->lengthInCm}x{$this->widthInCm}x{$this->heightInCm}cm";
    }

    /**
     * Calculate volume in cubic centimeters.
     */
    public function getVolumeInCm3(): ?int
    {
        if (!$this->lengthInCm || !$this->widthInCm || !$this->heightInCm) {
            return null;
        }

        return $this->lengthInCm * $this->widthInCm * $this->heightInCm;
    }

    /**
     * Calculate volumetric weight (1kg = 5000cm³).
     */
    public function getVolumetricWeightInGrams(): ?int
    {
        $volume = $this->getVolumeInCm3();
        if (!$volume) {
            return null;
        }

        return (int) ceil($volume / 5000) * 1000;
    }

    /**
     * Get billable weight (higher of actual weight or volumetric weight).
     */
    public function getBillableWeightInGrams(): int
    {
        $volumetricWeight = $this->getVolumetricWeightInGrams();

        if (!$volumetricWeight) {
            return $this->weightInGrams;
        }

        return max($this->weightInGrams, $volumetricWeight);
    }

    /**
     * Validate parcel data.
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->weightInGrams <= 0) {
            $errors[] = 'Le poids doit être supérieur à 0';
        }

        if ($this->weightInGrams > 30000) {
            $errors[] = 'Le poids maximum est de 30kg';
        }

        if (empty($this->content)) {
            $errors[] = 'Le contenu du colis est requis';
        }

        if (strlen($this->content) > 50) {
            $errors[] = 'La description du contenu ne peut pas dépasser 50 caractères';
        }

        // Validate dimensions if provided
        if ($this->lengthInCm !== null && $this->lengthInCm <= 0) {
            $errors[] = 'La longueur doit être supérieure à 0';
        }

        if ($this->widthInCm !== null && $this->widthInCm <= 0) {
            $errors[] = 'La largeur doit être supérieure à 0';
        }

        if ($this->heightInCm !== null && $this->heightInCm <= 0) {
            $errors[] = 'La hauteur doit être supérieure à 0';
        }

        // Check maximum dimensions
        $maxDimension = 150; // cm
        if ($this->lengthInCm && $this->lengthInCm > $maxDimension) {
            $errors[] = "La longueur maximum est de {$maxDimension}cm";
        }

        if ($this->widthInCm && $this->widthInCm > $maxDimension) {
            $errors[] = "La largeur maximum est de {$maxDimension}cm";
        }

        if ($this->heightInCm && $this->heightInCm > $maxDimension) {
            $errors[] = "La hauteur maximum est de {$maxDimension}cm";
        }

        // Validate value if provided
        if ($this->value !== null && $this->value < 0) {
            $errors[] = 'La valeur du colis ne peut pas être négative';
        }

        return $errors;
    }

    /**
     * Check if parcel is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Create a simple parcel with just weight and content.
     */
    public static function simple(int $weightInGrams, string $content = 'Produit e-commerce'): self
    {
        return new self($weightInGrams, $content);
    }

    /**
     * Create parcel with dimensions.
     */
    public static function withDimensions(
        int $weightInGrams,
        int $lengthInCm,
        int $widthInCm,
        int $heightInCm,
        string $content = 'Produit e-commerce'
    ): self {
        return new self($weightInGrams, $content, $lengthInCm, $widthInCm, $heightInCm);
    }

    /**
     * Create parcel with value.
     */
    public static function withValue(
        int $weightInGrams,
        float $value,
        string $content = 'Produit e-commerce',
        string $currency = 'EUR'
    ): self {
        return new self($weightInGrams, $content, null, null, null, $value, $currency);
    }
}
