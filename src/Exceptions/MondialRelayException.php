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
     * Get user-friendly error message based on error code.
     */
    public function getUserMessage(): string
    {
        return match ($this->getCode()) {
            1 => 'Enseigne invalide. Vérifiez vos identifiants Mondial Relay.',
            2 => 'Numéro d\'enseigne vide ou inexistant.',
            8 => 'Mot de passe ou clé de sécurité invalide.',
            9 => 'Ville non reconnue ou non unique.',
            10 => 'Type de collecte invalide.',
            20 => 'Poids du colis invalide.',
            24 => 'Numéro d\'expédition ou de suivi invalide.',
            36 => 'Code postal invalide.',
            92 => 'Le pays du destinataire et du point relais doivent être identiques.',
            93 => 'Aucun élément retourné par le plan de tri.',
            94 => 'Colis inexistant.',
            97 => 'Clé de sécurité invalide.',
            98 => 'Erreur générique (Paramètres invalides).',
            99 => 'Erreur générique du service.',
            default => $this->getMessage(),
        };
    }

    /**
     * Check if error is recoverable.
     */
    public function isRecoverable(): bool
    {
        return !in_array($this->getCode(), [1, 2, 8, 97]); // Authentication/config errors are not recoverable
    }

    /**
     * Check if error is related to authentication.
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->getCode(), [1, 2, 8, 97]);
    }

    /**
     * Check if error is related to validation.
     */
    public function isValidationError(): bool
    {
        return in_array($this->getCode(), [9, 10, 20, 24, 36, 92, 98]);
    }

    /**
     * Get error category.
     */
    public function getCategory(): string
    {
        return match (true) {
            $this->isAuthenticationError() => 'authentication',
            $this->isValidationError() => 'validation',
            in_array($this->getCode(), [93, 94]) => 'not_found',
            $this->getCode() === 99 => 'service',
            default => 'unknown',
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
            'is_recoverable' => $this->isRecoverable(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}
