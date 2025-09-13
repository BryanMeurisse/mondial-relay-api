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
            0 => 'Opération effectuée avec succès',
            1 => 'Enseigne invalide',
            2 => 'Numéro d\'enseigne vide ou inexistant',
            3 => 'Numéro de compte enseigne invalide',
            5 => 'Numéro de dossier enseigne invalide',
            7 => 'Numéro de client enseigne invalide (champ NCLIENT)',
            8 => 'Mot de passe ou hachage invalide',
            9 => 'Ville non reconnu ou non unique',
            10 => 'Type de collecte invalide',
            11 => 'Numéro de Relais de Collecte invalide',
            12 => 'Pays de Relais de collecte invalide',
            13 => 'Type de livraison invalide',
            14 => 'Numéro de Relais de livraison invalide',
            15 => 'Pays de Relais de livraison invalide',
            20 => 'Poids du colis invalide',
            21 => 'Taille (Longueur + Hauteur) du colis invalide',
            22 => 'Taille du Colis invalide',
            24 => 'Numéro d\'expédition ou de suivi invalide',
            26 => 'Temps de montage invalide',
            27 => 'Mode de collecte ou de livraison invalide',
            28 => 'Mode de collecte invalide',
            29 => 'Mode de livraison invalide',
            30 => 'Adresse (L1) invalide',
            31 => 'Adresse (L2) invalide',
            33 => 'Adresse (L3) invalide',
            34 => 'Adresse (L4) invalide',
            35 => 'Ville invalide',
            36 => 'Code postal invalide',
            37 => 'Pays invalide',
            38 => 'Numéro de téléphone invalide',
            39 => 'Adresse e-mail invalide',
            40 => 'Paramètres manquants',
            42 => 'Montant CRT invalide',
            43 => 'Devise CRT invalide',
            44 => 'Valeur du colis invalide',
            45 => 'Devise de la valeur du colis invalide',
            46 => 'Plage de numéro d\'expédition épuisée',
            47 => 'Nombre de colis invalide',
            48 => 'Multi-Colis Relais Interdit',
            49 => 'Action invalide',
            60 => 'Champ texte libre invalide (Ce code erreur n\'est pas invalidant)',
            61 => 'Top avisage invalide',
            62 => 'Instruction de livraison invalide',
            63 => 'Assurance invalide',
            64 => 'Temps de montage invalide',
            65 => 'Top rendez-vous invalide',
            66 => 'Top reprise invalide',
            67 => 'Latitude invalide',
            68 => 'Longitude invalide',
            69 => 'Code Enseigne invalide',
            70 => 'Numéro de Point Relais invalide',
            71 => 'Nature de point de vente non valide',
            74 => 'Langue invalide',
            78 => 'Pays de Collecte invalide',
            79 => 'Pays de Livraison invalide',
            80 => 'Code tracing : Colis enregistré',
            81 => 'Code tracing : Colis en traitement chez Mondial Relay',
            82 => 'Code tracing : Colis livré',
            83 => 'Code tracing : Anomalie',
            84 => '(Réservé Code Tracing)',
            85 => '(Réservé Code Tracing)',
            86 => '(Réservé Code Tracing)',
            87 => '(Réservé Code Tracing)',
            88 => '(Réservé Code Tracing)',
            89 => '(Réservé Code Tracing)',
            92 => 'Le code pays du destinataire et le code pays du Point Relais doivent être identiques. Ou Solde insuffisant (comptes prépayés)',
            93 => 'Aucun élément retourné par le plan de tri Si vous effectuez une collecte ou une livraison en Point Relais, vérifiez que les Point Relais sont bien disponibles. Si vous effectuez une livraison à domicile, il est probable que le code postal que vous avez indiqué n\'existe pas.',
            94 => 'Colis Inexistant',
            95 => 'Compte Enseigne non activé',
            96 => 'Type d\'enseigne incorrect en Base',
            97 => 'Clé de sécurité invalide',
            98 => 'Erreur générique (Paramètres invalides) Cette erreur masque une autre erreur de la liste et ne peut se produire que dans le cas où le compte utilisé est en mode « Production ».',
            99 => 'Erreur générique du service Cette erreur peut être due à un problème technique du service. Veuillez notifier cette erreur à Mondial Relay en précisant la date et l\'heure de la requête ainsi que les paramètres envoyés afin d\'effectuer une vérification.',
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
        return !in_array($this->getCode(), [1, 2, 3, 8, 95, 96, 97, 99]);
    }

    /**
     * Check if error is related to authentication.
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->getCode(), [1, 2, 3, 8, 95, 96, 97]);
    }

    /**
     * Check if error is related to validation.
     */
    public function isValidationError(): bool
    {
        return in_array($this->getCode(), [
            5, 7, 9, 10, 11, 12, 13, 14, 15, 20, 21, 22, 24, 26, 27, 28, 29,
            30, 31, 33, 34, 35, 36, 37, 38, 39, 40, 42, 43, 44, 45, 47, 48, 49,
            61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 74, 78, 79, 96, 98,
        ]);
    }

    /**
     * Check if error is related to business logic.
     */
    public function isBusinessError(): bool
    {
        return in_array($this->getCode(), [46, 48, 92, 93, 94]);
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
            5 => ['Vérifiez le numéro de dossier enseigne'],
            7 => ['Vérifiez le numéro de client enseigne (champ NCLIENT)'],
            8, 97 => ['Vérifiez votre clé privée Mondial Relay', 'Contactez votre responsable technique'],
            9 => ['Vérifiez l\'orthographe de la ville', 'Utilisez le code postal à la place'],
            10, 13, 27, 28, 29 => ['Vérifiez le mode de livraison/collecte', 'Consultez la documentation des modes disponibles'],
            11, 12, 14, 15, 70 => ['Vérifiez le numéro et pays du Point Relais', 'Effectuez une recherche de points relais'],
            20 => ['Vérifiez que le poids est en grammes', 'Le poids doit être supérieur à 0'],
            21 => ['Vérifiez la taille (Longueur + Hauteur) du colis'],
            22 => ['Vérifiez la taille du colis'],
            24 => ['Vérifiez le format du numéro d\'expédition ou de suivi'],
            26 => ['Vérifiez le temps de montage'],
            36 => ['Vérifiez le format du code postal', 'Code postal français : 5 chiffres'],
            38 => ['Vérifiez le format du numéro de téléphone', 'Format international recommandé'],
            39 => ['Vérifiez le format de l\'adresse e-mail'],
            40 => ['Vérifiez que tous les paramètres requis sont fournis'],
            42 => ['Vérifiez le montant CRT (Contre Remboursement)'],
            43 => ['Vérifiez la devise CRT'],
            44 => ['Vérifiez la valeur du colis'],
            45 => ['Vérifiez la devise de la valeur du colis'],
            46 => ['Contactez Mondial Relay pour obtenir une nouvelle plage de numéros'],
            48 => ['Les expéditions multi-colis ne sont pas autorisées en Point Relais'],
            60 => ['Vérifiez le champ texte libre (erreur non invalidante)'],
            61 => ['Vérifiez le paramètre d\'avisage'],
            62 => ['Vérifiez les instructions de livraison'],
            63 => ['Vérifiez les paramètres d\'assurance'],
            64 => ['Vérifiez le temps de montage'],
            65 => ['Vérifiez les paramètres de rendez-vous'],
            66 => ['Vérifiez les paramètres de reprise'],
            67, 68 => ['Vérifiez les coordonnées GPS (latitude/longitude)'],
            69 => ['Vérifiez le code enseigne'],
            71 => ['Vérifiez la nature du point de vente'],
            74 => ['Vérifiez le paramètre de langue'],
            78, 79 => ['Vérifiez les pays de collecte et de livraison'],
            92 => ['Vérifiez que le pays du destinataire et du Point Relais sont identiques', 'Vérifiez le solde de votre compte prépayé'],
            93 => ['Vérifiez que le Point Relais existe et est ouvert', 'Vérifiez que le code postal existe'],
            94 => ['Vérifiez le numéro d\'expédition', 'Le colis peut ne pas encore être dans le système'],
            95 => ['Contactez Mondial Relay pour activer votre compte'],
            96 => ['Vérifiez le type d\'enseigne en base'],
            98 => ['Vérifiez tous les paramètres', 'Passez en mode debug pour voir l\'erreur détaillée'],
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
