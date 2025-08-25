<?php

namespace Bmwsly\MondialRelayApi\Validation;

class MondialRelayValidator
{
    /**
     * Validate enseigne (merchant code).
     */
    public static function validateEnseigne(string $enseigne): array
    {
        $errors = [];

        if (empty($enseigne)) {
            $errors[] = 'Numéro d\'enseigne vide';
        } elseif (strlen(trim($enseigne)) < 2) {
            $errors[] = 'Numéro d\'enseigne trop court (minimum 2 caractères)';
        } elseif (strlen(trim($enseigne)) > 8) {
            $errors[] = 'Numéro d\'enseigne trop long (maximum 8 caractères)';
        }

        return $errors;
    }

    /**
     * Validate postal code based on country.
     */
    public static function validatePostalCode(string $postalCode, string $country = 'FR'): array
    {
        $errors = [];

        if (empty($postalCode)) {
            $errors[] = 'Code postal requis';

            return $errors;
        }

        $patterns = [
            'FR' => '/^[0-9]{5}$/',
            'BE' => '/^[0-9]{4}$/',
            'LU' => '/^[0-9]{4}$/',
            'NL' => '/^[0-9]{4}[A-Z]{2}$/',
            'ES' => '/^[0-9]{5}$/',
            'PT' => '/^[0-9]{4}-[0-9]{3}$/',
            'IT' => '/^[0-9]{5}$/',
            'DE' => '/^[0-9]{5}$/',
            'AT' => '/^[0-9]{4}$/',
        ];

        if (!isset($patterns[$country])) {
            $errors[] = "Pays non supporté: {$country}";
        } elseif (!preg_match($patterns[$country], $postalCode)) {
            $errors[] = "Format de code postal invalide pour {$country}";
        }

        return $errors;
    }

    /**
     * Validate city name.
     */
    public static function validateCity(string $city): array
    {
        $errors = [];

        if (empty($city)) {
            $errors[] = 'Ville requise';
        } elseif (strlen($city) > 32) {
            $errors[] = 'Nom de ville trop long (maximum 32 caractères)';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/', $city)) {
            $errors[] = 'Nom de ville contient des caractères invalides';
        }

        return $errors;
    }

    /**
     * Validate address line.
     */
    public static function validateAddressLine(string $address, int $lineNumber, bool $required = true): array
    {
        $errors = [];

        if (empty($address)) {
            if ($required) {
                $errors[] = "Adresse ligne {$lineNumber} requise";
            }

            return $errors;
        }

        $maxLengths = [
            1 => 32, // L1 - Name/Company
            2 => 32, // L2 - Additional info
            3 => 32, // L3 - Street address
            4 => 32, // L4 - Additional address
        ];

        if (strlen($address) > ($maxLengths[$lineNumber] ?? 32)) {
            $errors[] = "Adresse ligne {$lineNumber} trop longue (maximum {$maxLengths[$lineNumber]} caractères)";
        }

        return $errors;
    }

    /**
     * Validate phone number.
     */
    public static function validatePhoneNumber(string $phone, bool $required = true): array
    {
        $errors = [];

        if (empty($phone)) {
            if ($required) {
                $errors[] = 'Numéro de téléphone requis';
            }

            return $errors;
        }

        // Remove spaces, dots, dashes for validation
        $cleanPhone = preg_replace('/[\s\.\-\(\)]/', '', $phone);

        if (strlen($cleanPhone) < 10) {
            $errors[] = 'Numéro de téléphone trop court (minimum 10 chiffres)';
        } elseif (strlen($cleanPhone) > 15) {
            $errors[] = 'Numéro de téléphone trop long (maximum 15 chiffres)';
        } elseif (!preg_match('/^\+?[0-9]+$/', $cleanPhone)) {
            $errors[] = 'Format de numéro de téléphone invalide';
        }

        return $errors;
    }

    /**
     * Validate email address.
     */
    public static function validateEmail(string $email, bool $required = true): array
    {
        $errors = [];

        if (empty($email)) {
            if ($required) {
                $errors[] = 'Adresse e-mail requise';
            }

            return $errors;
        }

        if (strlen($email) > 70) {
            $errors[] = 'Adresse e-mail trop longue (maximum 70 caractères)';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format d\'adresse e-mail invalide';
        }

        return $errors;
    }

    /**
     * Validate weight in grams.
     */
    public static function validateWeight(int $weight): array
    {
        $errors = [];

        if ($weight <= 0) {
            $errors[] = 'Poids doit être supérieur à 0';
        } elseif ($weight > 30000) { // 30kg max
            $errors[] = 'Poids maximum dépassé (30kg)';
        }

        return $errors;
    }

    /**
     * Validate delivery mode.
     */
    public static function validateDeliveryMode(string $mode): array
    {
        $errors = [];

        // Use the same list as MondialRelayHelper to avoid duplication
        $validModes = array_keys(\Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper::getDeliveryModes());

        if (!in_array($mode, $validModes)) {
            $errors[] = "Mode de livraison invalide: {$mode}";
        }

        return $errors;
    }

    /**
     * Validate relay point number.
     */
    public static function validateRelayNumber(string $relayNumber): array
    {
        $errors = [];

        if (empty($relayNumber)) {
            $errors[] = 'Numéro de point relais requis';
        } elseif (!preg_match('/^[0-9]{6}$/', $relayNumber)) {
            $errors[] = 'Format de numéro de point relais invalide (6 chiffres requis)';
        }

        return $errors;
    }

    /**
     * Validate expedition number.
     */
    public static function validateExpeditionNumber(string $expeditionNumber): array
    {
        $errors = [];

        if (empty($expeditionNumber)) {
            $errors[] = 'Numéro d\'expédition requis';
        } elseif (!preg_match('/^[0-9]{8}$/', $expeditionNumber)) {
            $errors[] = 'Format de numéro d\'expédition invalide (8 chiffres requis)';
        }

        return $errors;
    }

    /**
     * Validate country code.
     */
    public static function validateCountryCode(string $country): array
    {
        $errors = [];

        // Use the same list as MondialRelayHelper to avoid duplication
        $supportedCountries = array_keys(\Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper::getSupportedCountries());

        if (!in_array($country, $supportedCountries)) {
            $errors[] = "Code pays non supporté: {$country}";
        }

        return $errors;
    }

    /**
     * Validate language code.
     */
    public static function validateLanguage(string $language): array
    {
        $errors = [];

        $supportedLanguages = ['FR', 'NL', 'EN', 'ES', 'DE', 'IT'];

        if (!in_array($language, $supportedLanguages)) {
            $errors[] = "Code langue non supporté: {$language}";
        }

        return $errors;
    }

    /**
     * Validate COD (Cash on Delivery) amount.
     */
    public static function validateCodAmount(float $amount): array
    {
        $errors = [];

        if ($amount < 0) {
            $errors[] = 'Montant contre-remboursement ne peut pas être négatif';
        } elseif ($amount > 3000) {
            $errors[] = 'Montant contre-remboursement maximum dépassé (3000€)';
        }

        return $errors;
    }

    /**
     * Validate insurance level.
     */
    public static function validateInsurance(string $insurance): array
    {
        $errors = [];

        $validLevels = ['0', '1', '2', '3', '4', '5'];

        if (!in_array($insurance, $validLevels)) {
            $errors[] = "Niveau d'assurance invalide: {$insurance}";
        }

        return $errors;
    }

    /**
     * Validate complete address.
     */
    public static function validateAddress(array $address, bool $isRecipient = false): array
    {
        $errors = [];

        // Validate required fields
        $errors = array_merge($errors, self::validateAddressLine($address['line1'] ?? '', 1, true));
        $errors = array_merge($errors, self::validateAddressLine($address['line3'] ?? '', 3, true));
        $errors = array_merge($errors, self::validateCity($address['city'] ?? ''));
        $errors = array_merge($errors, self::validatePostalCode($address['postal_code'] ?? '', $address['country'] ?? 'FR'));
        $errors = array_merge($errors, self::validateCountryCode($address['country'] ?? 'FR'));

        // Validate optional fields
        if (!empty($address['line2'])) {
            $errors = array_merge($errors, self::validateAddressLine($address['line2'], 2, false));
        }
        if (!empty($address['line4'])) {
            $errors = array_merge($errors, self::validateAddressLine($address['line4'], 4, false));
        }

        // Phone is required for recipients
        $errors = array_merge($errors, self::validatePhoneNumber($address['phone'] ?? '', $isRecipient));

        // Email validation
        if (!empty($address['email'])) {
            $errors = array_merge($errors, self::validateEmail($address['email'], false));
        }

        // Language validation
        if (!empty($address['language'])) {
            $errors = array_merge($errors, self::validateLanguage($address['language']));
        }

        return $errors;
    }
}
