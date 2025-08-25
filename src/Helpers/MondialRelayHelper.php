<?php

namespace Bmwsly\MondialRelayApi\Helpers;

use Bmwsly\MondialRelayApi\Validation\MondialRelayValidator;

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
            'HOM' => 'Livraison à domicile',
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
        return empty(MondialRelayValidator::validatePostalCode($postalCode, 'FR'));
    }

    /**
     * Validate postal code for any supported country.
     */
    public static function isValidPostalCode(string $postalCode, string $country = 'FR'): bool
    {
        return empty(MondialRelayValidator::validatePostalCode($postalCode, $country));
    }

    /**
     * Validate relay number.
     */
    public static function isValidRelayNumber(string $relayNumber): bool
    {
        return empty(MondialRelayValidator::validateRelayNumber($relayNumber));
    }

    /**
     * Validate expedition number.
     */
    public static function isValidExpeditionNumber(string $expeditionNumber): bool
    {
        return empty(MondialRelayValidator::validateExpeditionNumber($expeditionNumber));
    }

    /**
     * Validate delivery mode.
     */
    public static function isValidDeliveryMode(string $mode): bool
    {
        return empty(MondialRelayValidator::validateDeliveryMode($mode));
    }

    /**
     * Validate weight in grams.
     */
    public static function isValidWeight(int $weight): bool
    {
        return empty(MondialRelayValidator::validateWeight($weight));
    }

    /**
     * Validate email address.
     */
    public static function isValidEmail(string $email): bool
    {
        return empty(MondialRelayValidator::validateEmail($email, false));
    }

    /**
     * Validate phone number.
     */
    public static function isValidPhoneNumber(string $phone): bool
    {
        return empty(MondialRelayValidator::validatePhoneNumber($phone, false));
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
     * Validate country code.
     */
    public static function isValidCountryCode(string $countryCode): bool
    {
        return empty(MondialRelayValidator::validateCountryCode($countryCode));
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
     * Generate basic tracking URL (public).
     */
    public static function getTrackingUrl(string $expeditionNumber): string
    {
        return 'https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition='.urlencode($expeditionNumber);
    }

    /**
     * Generate secure connect tracing link for professional extranet.
     *
     * @param string $expeditionNumber Expedition number (8 digits)
     * @param string $userLogin Login to connect to the system
     * @param string $enseigne Customer code
     * @param string $password API password for V2
     * @return string Secure URL for professional tracking
     */
    public static function getConnectTracingLink(
        string $expeditionNumber,
        string $userLogin,
        string $enseigne,
        string $password
    ): string {
        $baseUrl = 'http://connect.mondialrelay.com';
        $tracingUrl = '/'.trim(strtoupper($enseigne)).'/Expedition/Afficher?numeroExpedition='.$expeditionNumber;

        return $baseUrl.self::addConnectSecurityParameters($tracingUrl, $userLogin, $password);
    }

    /**
     * Generate secure permalink tracing link for public tracking.
     *
     * @param string $expeditionNumber Expedition number (8 digits)
     * @param string $enseigne Customer code
     * @param string $brandId Brand ID (numeric)
     * @param string $privateKey Private key for security
     * @param string $language Language code (default: 'fr')
     * @param string $country Country code (default: 'fr')
     * @return string Secure permalink URL
     */
    public static function getPermalinkTracingLink(
        string $expeditionNumber,
        string $enseigne,
        string $brandId,
        string $privateKey,
        string $language = 'fr',
        string $country = 'fr'
    ): string {
        $tracingUrl = 'http://www.mondialrelay.fr/public/permanent/tracking.aspx?ens='.
                     $enseigne.$brandId.'&exp='.$expeditionNumber.
                     '&pays='.$country.'&language='.$language;

        return $tracingUrl.self::addPermalinkSecurityParameters($expeditionNumber, $enseigne, $brandId, $privateKey);
    }

    /**
     * Add security parameters to connect URL.
     *
     * @param string $urlToSecure URL to secure
     * @param string $userLogin User login
     * @param string $password API password
     * @return string URL with security parameters
     */
    private static function addConnectSecurityParameters(string $urlToSecure, string $userLogin, string $password): string
    {
        $urlToSecure = $urlToSecure.'&login='.$userLogin.'&ts='.time();
        $urlToEncode = $password.'_'.$urlToSecure;

        return $urlToSecure.'&crc='.strtoupper(md5($urlToEncode));
    }

    /**
     * Add security parameters to permalink URL.
     *
     * @param string $expeditionNumber Expedition number
     * @param string $enseigne Customer code
     * @param string $brandId Brand ID
     * @param string $privateKey Private key
     * @return string Security parameters
     */
    private static function addPermalinkSecurityParameters(
        string $expeditionNumber,
        string $enseigne,
        string $brandId,
        string $privateKey
    ): string {
        $urlToSecure = '<'.$enseigne.$brandId.'>'.$expeditionNumber.'<'.$privateKey.'>';

        return '&crc='.strtoupper(md5($urlToSecure));
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

        // Handle international French numbers (+33)
        if (strlen($phone) === 11 && substr($phone, 0, 2) === '33') {
            return '0' . substr($phone, 2);
        }

        // Ensure French format
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return $phone;
        }

        if (strlen($phone) === 9) {
            return "0{$phone}";
        }

        return $phone;
    }

    /**
     * Get delivery mode requirements.
     */
    public static function getDeliveryModeRequirements(string $mode): array
    {
        return match ($mode) {
            '24R', '24X' => [
                'requires_relay' => true,
                'allows_multi_parcel' => false,
                'max_weight' => 30000,
                'description' => 'Livraison en Point Relais',
            ],
            '24L', 'LD1', 'LDS' => [
                'requires_relay' => false,
                'allows_multi_parcel' => true,
                'max_weight' => 30000,
                'description' => 'Livraison à domicile',
            ],
            'DRI' => [
                'requires_relay' => true,
                'allows_multi_parcel' => false,
                'max_weight' => 20000,
                'description' => 'Drive',
            ],
            default => [
                'requires_relay' => false,
                'allows_multi_parcel' => false,
                'max_weight' => 30000,
                'description' => 'Mode inconnu',
            ],
        };
    }

    /**
     * Check if delivery mode allows multi-parcel.
     */
    public static function allowsMultiParcel(string $mode): bool
    {
        return self::getDeliveryModeRequirements($mode)['allows_multi_parcel'];
    }

    /**
     * Get maximum weight for delivery mode.
     */
    public static function getMaxWeightForMode(string $mode): int
    {
        return self::getDeliveryModeRequirements($mode)['max_weight'];
    }

    /**
     * Format expedition number for display.
     */
    public static function formatExpeditionNumber(string $expeditionNumber): string
    {
        // Add spaces for readability: 12345678 -> 1234 5678
        if (strlen($expeditionNumber) === 8) {
            return substr($expeditionNumber, 0, 4).' '.substr($expeditionNumber, 4);
        }

        return $expeditionNumber;
    }

    /**
     * Parse expedition number from various formats.
     */
    public static function parseExpeditionNumber(string $input): string
    {
        // Remove spaces and non-numeric characters
        return preg_replace('/[^\d]/', '', $input);
    }

    /**
     * Generate security hash for API calls.
     */
    public static function generateSecurityHash(array $params, string $privateKey): string
    {
        $string = '';
        foreach ($params as $value) {
            $string .= $value;
        }

        return strtoupper(md5($string.$privateKey));
    }

    /**
     * Get insurance levels and their descriptions.
     */
    public static function getInsuranceLevels(): array
    {
        return [
            '0' => 'Aucune assurance',
            '1' => 'Assurance niveau 1 (jusqu\'à 100€)',
            '2' => 'Assurance niveau 2 (jusqu\'à 300€)',
            '3' => 'Assurance niveau 3 (jusqu\'à 500€)',
            '4' => 'Assurance niveau 4 (jusqu\'à 1000€)',
            '5' => 'Assurance niveau 5 (jusqu\'à 1500€)',
        ];
    }

    /**
     * Get insurance level description.
     */
    public static function getInsuranceLevelDescription(string $level): string
    {
        return self::getInsuranceLevels()[$level] ?? 'Niveau inconnu';
    }

    /**
     * Get collection modes and their descriptions.
     */
    public static function getCollectionModes(): array
    {
        return [
            'CCC' => 'Collecte chez le commerçant',
            'CDR' => 'Collecte en Drive',
            'CDS' => 'Collecte sur site',
        ];
    }

    /**
     * Get collection mode description.
     */
    public static function getCollectionModeDescription(string $mode): string
    {
        return self::getCollectionModes()[$mode] ?? $mode;
    }
}
