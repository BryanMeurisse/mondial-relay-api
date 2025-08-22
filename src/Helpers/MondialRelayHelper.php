<?php

namespace Bmwsly\MondialRelayApi\Helpers;

class MondialRelayHelper
{
    /**
     * Get delivery mode options.
     */
    public static function getDeliveryModes(): array
    {
        return [
            '24R' => 'Livraison en point relais (24h-48h)',
            '24L' => 'Livraison à domicile (24h-48h)',
            '24X' => 'Livraison express en point relais',
            'LD1' => 'Livraison à domicile (J+1)',
            'LDS' => 'Livraison à domicile le samedi',
            'DRI' => 'Drive',
        ];
    }

    /**
     * Get delivery mode label.
     */
    public static function getDeliveryModeLabel(string $mode): string
    {
        return self::getDeliveryModes()[$mode] ?? $mode;
    }

    /**
     * Check if delivery mode requires relay point.
     */
    public static function requiresRelayPoint(string $mode): bool
    {
        return in_array($mode, ['24R', '24X']);
    }

    /**
     * Validate French postal code.
     */
    public static function isValidFrenchPostalCode(string $postalCode): bool
    {
        return preg_match('/^[0-9]{5}$/', $postalCode) === 1;
    }

    /**
     * Validate relay number.
     */
    public static function isValidRelayNumber(string $relayNumber): bool
    {
        return preg_match('/^[0-9]{6}$/', $relayNumber) === 1;
    }

    /**
     * Format weight from grams to kilograms.
     */
    public static function formatWeight(int $weightInGrams): string
    {
        if ($weightInGrams < 1000) {
            return "{$weightInGrams}g";
        }

        return number_format($weightInGrams / 1000, 2).'kg';
    }

    /**
     * Convert weight from kilograms to grams.
     */
    public static function convertKgToGrams(float $weightInKg): int
    {
        return (int) round($weightInKg * 1000);
    }

    /**
     * Format distance.
     */
    public static function formatDistance(float $distance): string
    {
        if ($distance < 1) {
            return round($distance * 1000).'m';
        }

        return number_format($distance, 1).'km';
    }

    /**
     * Get country codes supported by Mondial Relay.
     */
    public static function getSupportedCountries(): array
    {
        return [
            'FR' => 'France',
            'BE' => 'Belgique',
            'LU' => 'Luxembourg',
            'NL' => 'Pays-Bas',
            'ES' => 'Espagne',
            'PT' => 'Portugal',
            'IT' => 'Italie',
            'DE' => 'Allemagne',
            'AT' => 'Autriche',
        ];
    }

    /**
     * Get country name from code.
     */
    public static function getCountryName(string $countryCode): string
    {
        return self::getSupportedCountries()[$countryCode] ?? $countryCode;
    }

    /**
     * Validate country code.
     */
    public static function isValidCountryCode(string $countryCode): bool
    {
        return array_key_exists($countryCode, self::getSupportedCountries());
    }

    /**
     * Format opening hours for display.
     */
    public static function formatOpeningHours(array $openingHours): array
    {
        $formatted = [];
        $dayNames = [
            'monday' => 'Lundi',
            'tuesday' => 'Mardi',
            'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi',
            'friday' => 'Vendredi',
            'saturday' => 'Samedi',
            'sunday' => 'Dimanche',
        ];

        foreach ($dayNames as $key => $name) {
            $hours = $openingHours[$key] ?? [];
            if (empty($hours)) {
                $formatted[$name] = 'Fermé';
            } else {
                $slots = array_filter(
                    array_map(
                        fn ($slot) => !empty($slot['open']) && !empty($slot['close'])
                            ? "{$slot['open']}-{$slot['close']}"
                            : null,
                        $hours
                    )
                );
                $formatted[$name] = implode(', ', $slots) ?: 'Fermé';
            }
        }

        return $formatted;
    }

    /**
     * Calculate shipping cost based on weight and delivery mode
     * This is a basic example - you should implement your own pricing logic.
     */
    public static function calculateShippingCost(int $weightInGrams, string $deliveryMode): float
    {
        $basePrices = [
            '24R' => 4.90,
            '24L' => 6.90,
            '24X' => 7.90,
            'LD1' => 8.90,
            'LDS' => 9.90,
            'DRI' => 5.90,
        ];

        $basePrice = $basePrices[$deliveryMode] ?? 4.90;

        // Add weight surcharge for packages over 1kg
        if ($weightInGrams > 1000) {
            $extraKg = ceil(($weightInGrams - 1000) / 1000);
            $basePrice += $extraKg * 1.50;
        }

        return $basePrice;
    }

    /**
     * Generate tracking URL.
     */
    public static function getTrackingUrl(string $expeditionNumber): string
    {
        return 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition='.urlencode($expeditionNumber);
    }

    /**
     * Validate expedition parameters.
     */
    public static function validateExpeditionParams(array $params): array
    {
        $errors = [];

        // Required fields
        $required = [
            'delivery_mode' => 'Mode de livraison requis',
            'weight' => 'Poids requis',
            'sender.name' => 'Nom de l\'expéditeur requis',
            'sender.address' => 'Adresse de l\'expéditeur requise',
            'sender.city' => 'Ville de l\'expéditeur requise',
            'sender.postal_code' => 'Code postal de l\'expéditeur requis',
            'sender.country' => 'Pays de l\'expéditeur requis',
            'sender.phone' => 'Téléphone de l\'expéditeur requis',
            'recipient.name' => 'Nom du destinataire requis',
            'recipient.address' => 'Adresse du destinataire requise',
            'recipient.city' => 'Ville du destinataire requise',
            'recipient.postal_code' => 'Code postal du destinataire requis',
            'recipient.country' => 'Pays du destinataire requis',
            'recipient.phone' => 'Téléphone du destinataire requis',
        ];

        foreach ($required as $field => $message) {
            if (empty(data_get($params, $field))) {
                $errors[] = $message;
            }
        }

        // Validate delivery mode
        if (!empty($params['delivery_mode']) && !array_key_exists($params['delivery_mode'], self::getDeliveryModes())) {
            $errors[] = 'Mode de livraison invalide';
        }

        // Validate relay point for relay delivery modes
        if (!empty($params['delivery_mode']) && self::requiresRelayPoint($params['delivery_mode'])) {
            if (empty($params['relay_number'])) {
                $errors[] = 'Numéro de point relais requis pour ce mode de livraison';
            }
            if (empty($params['relay_country'])) {
                $errors[] = 'Pays du point relais requis pour ce mode de livraison';
            }
        }

        return $errors;
    }

    /**
     * Clean and format address.
     */
    public static function formatAddress(string $address): string
    {
        return trim(preg_replace('/\s+/', ' ', $address));
    }

    /**
     * Format phone number for API.
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ensure French format
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return $phone;
        }

        if (strlen($phone) === 9) {
            return "0{$phone}";
        }

        return $phone;
    }
}
