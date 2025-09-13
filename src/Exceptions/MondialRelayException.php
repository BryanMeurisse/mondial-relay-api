<?php

namespace Bmwsly\MondialRelayApi\Exceptions;

use Exception;

class MondialRelayException extends Exception
{
    protected array $context = [];

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * Get all error codes and their messages from Mondial Relay API.
     */
    public static function getErrorCodes(): array
    {
        return [
            0 => 'Opération réussie',
            1 => 'Enseigne invalide',
            2 => 'Numéro d\'enseigne vide',
            3 => 'Numéro de compte enseigne incorrect',
            5 => 'Référence expéditeur incorrecte',
            7 => 'Référence destinataire incorrecte',
            8 => 'Mot de passe ou hash incorrect',
            9 => 'Ville inconnue ou non unique',
            10 => 'Type de collecte incorrect',
            11 => 'Numéro de Point Relais de collecte incorrect',
            12 => 'Pays de Point Relais de collecte incorrect',
            13 => 'Type de livraison incorrect',
            14 => 'Numéro de Point Relais de livraison incorrect',
            15 => 'Pays de Point Relais de livraison incorrect',
            20 => 'Poids du colis incorrect',
            21 => 'Longueur développée incorrecte (longueur + hauteur)',
            22 => 'Taille du colis incorrecte',
            24 => 'Numéro d\'expédition incorrect',
            25 => 'Montant insuffisant sur votre compte pour enregistrer cette expédition',
            26 => 'Délai d\'assemblage incorrect',
            27 => 'Mode de collecte ou de livraison incorrect',
            28 => 'Mode de collecte incorrect',
            29 => 'Mode de livraison incorrect',
            30 => 'Adresse incorrecte (L1)',
            31 => 'Adresse incorrecte (L2)',
            33 => 'Adresse incorrecte (L3)',
            34 => 'Adresse incorrecte (L4)',
            35 => 'Ville incorrecte',
            36 => 'Code postal incorrect',
            37 => 'Pays incorrect',
            38 => 'Numéro de téléphone incorrect',
            39 => 'Adresse e-mail incorrecte',
            40 => 'Paramètres manquants',
            42 => 'Valeur de contre-remboursement incorrecte',
            43 => 'Devise de contre-remboursement incorrecte',
            44 => 'Valeur d\'expédition incorrecte',
            45 => 'Devise d\'expédition incorrecte',
            46 => 'Fin de plage de numéros d\'expédition atteinte',
            47 => 'Nombre de colis incorrect',
            48 => 'Multi-colis non autorisé en Point Relais',
            49 => 'Action incorrecte',
            60 => 'Champ texte incorrect (ce code d\'erreur n\'a pas d\'impact)',
            61 => 'Demande de notification incorrecte',
            62 => 'Informations de livraison supplémentaires incorrectes',
            63 => 'Assurance incorrecte',
            64 => 'Délai d\'assemblage incorrect',
            65 => 'Rendez-vous incorrect',
            66 => 'Reprise incorrecte',
            67 => 'Latitude incorrecte',
            68 => 'Longitude incorrecte',
            69 => 'Code enseigne incorrect',
            70 => 'Numéro de Point Relais incorrect',
            71 => 'Nature de point de vente non valide',
            74 => 'Langue incorrecte',
            78 => 'Pays de collecte incorrect',
            79 => 'Pays de livraison incorrect',
            80 => 'Code de suivi : Colis enregistré',
            81 => 'Code de suivi : Colis en cours de traitement chez Mondial Relay',
            82 => 'Code de suivi : Colis livré',
            83 => 'Code de suivi : Anomalie',
            84 => '(Code de suivi réservé)',
            85 => '(Code de suivi réservé)',
            86 => '(Code de suivi réservé)',
            87 => '(Code de suivi réservé)',
            88 => '(Code de suivi réservé)',
            89 => '(Code de suivi réservé)',
            90 => 'Erreur générique (Paramètres incorrects)',
            91 => 'Erreur générique du système de service',
            92 => 'Erreur lors de la génération de l\'étiquette ou du traitement de l\'expédition',
            93 => 'Aucune information donnée par le plan de tri. Si vous souhaitez faire une collecte ou livraison en Point Relais, vérifiez qu\'il est disponible. Si vous souhaitez faire une livraison à domicile, vérifiez que le code postal existe.',
            94 => 'Colis inconnu',
            95 => 'Compte enseigne non activé',
            97 => 'Clé de sécurité incorrecte',
            98 => 'Erreur générique (Paramètres incorrects)',
            99 => 'Erreur générique du système de service',
        ];
    }

    /**
     * Get user-friendly error message based on error code.
     */
    public function getUserMessage(): string
    {
        $errorCodes = self::getErrorCodes();

        return $errorCodes[$this->getCode()] ?? $this->getMessage();
    }

    /**
     * Check if error is recoverable.
     */
    public function isRecoverable(): bool
    {
        // Authentication, configuration, and system errors are not recoverable
        return !in_array($this->getCode(), [1, 2, 3, 8, 95, 97, 99]);
    }

    /**
     * Check if error is related to authentication.
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->getCode(), [1, 2, 3, 8, 95, 97]);
    }

    /**
     * Check if error is related to validation.
     */
    public function isValidationError(): bool
    {
        return in_array($this->getCode(), [
            5, 7, 9, 10, 11, 12, 13, 14, 15, 20, 21, 22, 24, 26, 27, 28, 29,
            30, 31, 33, 34, 35, 36, 37, 38, 39, 40, 42, 43, 44, 45, 47, 48, 49,
            61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 74, 78, 79, 98,
        ]);
    }

    /**
     * Check if error is related to business logic.
     */
    public function isBusinessError(): bool
    {
        return in_array($this->getCode(), [25, 46, 48, 93, 94]);
    }

    /**
     * Check if error is related to tracking.
     */
    public function isTrackingInfo(): bool
    {
        return in_array($this->getCode(), [80, 81, 82, 83, 84, 85, 86, 87, 88, 89]);
    }

    /**
     * Check if error is a system error.
     */
    public function isSystemError(): bool
    {
        return in_array($this->getCode(), [99]);
    }

    /**
     * Get error category.
     */
    public function getCategory(): string
    {
        return match (true) {
            $this->getCode() === 0 => 'success',
            $this->isAuthenticationError() => 'authentication',
            $this->isValidationError() => 'validation',
            $this->isBusinessError() => 'business',
            $this->isTrackingInfo() => 'tracking',
            $this->isSystemError() => 'system',
            default => 'unknown',
        };
    }

    /**
     * Get error severity level.
     */
    public function getSeverity(): string
    {
        return match (true) {
            $this->getCode() === 0 => 'success',
            $this->isTrackingInfo() => 'info',
            $this->getCode() === 60 => 'warning', // Text field error has no impact
            $this->isAuthenticationError() || $this->isSystemError() => 'critical',
            $this->isBusinessError() => 'error',
            $this->isValidationError() => 'error',
            default => 'error',
        };
    }

    /**
     * Get suggested actions based on error code.
     */
    public function getSuggestedActions(): array
    {
        return match ($this->getCode()) {
            1, 2, 3 => ['Vérifiez votre numéro d\'enseigne Mondial Relay'],
            8, 97 => ['Vérifiez votre clé privée Mondial Relay', 'Contactez votre responsable technique'],
            9 => ['Vérifiez l\'orthographe de la ville', 'Utilisez le code postal à la place'],
            10, 13, 27, 28, 29 => ['Vérifiez le mode de livraison/collecte', 'Consultez la documentation des modes disponibles'],
            11, 12, 14, 15, 70 => ['Vérifiez le numéro et pays du Point Relais', 'Effectuez une recherche de points relais'],
            20 => ['Vérifiez que le poids est en grammes', 'Le poids doit être supérieur à 0'],
            24 => ['Vérifiez le format du numéro d\'expédition', 'Le numéro doit contenir 8 chiffres'],
            25 => ['Rechargez votre compte Mondial Relay', 'Contactez votre responsable commercial'],
            36 => ['Vérifiez le format du code postal', 'Code postal français : 5 chiffres'],
            38 => ['Vérifiez le format du numéro de téléphone', 'Format international recommandé'],
            39 => ['Vérifiez le format de l\'adresse e-mail'],
            40 => ['Vérifiez que tous les paramètres requis sont fournis'],
            46 => ['Contactez Mondial Relay pour obtenir une nouvelle plage de numéros'],
            48 => ['Les expéditions multi-colis ne sont pas autorisées en Point Relais'],
            93 => ['Vérifiez que le Point Relais existe et est ouvert', 'Vérifiez que le code postal existe'],
            94 => ['Vérifiez le numéro d\'expédition', 'Le colis peut ne pas encore être dans le système'],
            95 => ['Contactez Mondial Relay pour activer votre compte'],
            99 => ['Réessayez plus tard', 'Contactez le support technique si le problème persiste'],
            default => ['Consultez la documentation', 'Contactez le support technique'],
        };
    }

    /**
     * Convert to array for logging/debugging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'user_message' => $this->getUserMessage(),
            'code' => $this->getCode(),
            'category' => $this->getCategory(),
            'severity' => $this->getSeverity(),
            'is_recoverable' => $this->isRecoverable(),
            'is_authentication_error' => $this->isAuthenticationError(),
            'is_validation_error' => $this->isValidationError(),
            'is_business_error' => $this->isBusinessError(),
            'is_tracking_info' => $this->isTrackingInfo(),
            'suggested_actions' => $this->getSuggestedActions(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * Create exception from API response.
     */
    public static function fromApiResponse(array $response, array $context = []): self
    {
        $code = (int) ($response['STAT'] ?? $response['code'] ?? 0);
        $baseMessage = self::getErrorCodes()[$code] ?? 'Erreur inconnue';

        // Build detailed error message with context
        $detailedMessage = $baseMessage;

        // Add method context if available
        if (isset($context['method'])) {
            $detailedMessage .= " (Méthode: {$context['method']})";
        }

        // Add specific parameter context for better debugging
        if (isset($context['postal_code'])) {
            $detailedMessage .= " - Code postal: {$context['postal_code']}";
        }

        if (isset($context['country'])) {
            $detailedMessage .= " - Pays: {$context['country']}";
        }

        if (isset($context['enseigne'])) {
            $detailedMessage .= " - Enseigne: {$context['enseigne']}";
        }

        // Add API response details for debugging
        if ($code !== 0) {
            $detailedMessage .= " [Code erreur API: {$code}]";
        }

        return new self($detailedMessage, $code, null, array_merge($context, [
            'api_response' => $response,
            'api_error_code' => $code,
            'base_message' => $baseMessage,
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Create exception for validation errors.
     */
    public static function validation(string $message, array $errors = []): self
    {
        return new self($message, 98, null, [
            'validation_errors' => $errors,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get detailed debug information about the error.
     */
    public function getDebugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * Check if this is an API-related error.
     */
    public function isApiError(): bool
    {
        return isset($this->context['api_response']) || isset($this->context['api_error_code']);
    }

    /**
     * Create exception for authentication errors.
     */
    public static function authentication(string $message = 'Erreur d\'authentification'): self
    {
        return new self($message, 8, null, [
            'timestamp' => now()->toISOString(),
        ]);
    }
}
