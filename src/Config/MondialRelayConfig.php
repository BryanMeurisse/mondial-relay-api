<?php

namespace Bmwsly\MondialRelayApi\Config;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Validation\MondialRelayValidator;

class MondialRelayConfig
{
    private array $config;
    private array $validationErrors = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->validate();
    }

    /**
     * Get configuration value.
     */
    public function get(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set configuration value.
     */
    public function set(string $key, $value): self
    {
        data_set($this->config, $key, $value);

        return $this;
    }

    /**
     * Get all configuration.
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Validate configuration.
     */
    public function validate(): bool
    {
        $this->validationErrors = [];

        // Validate enseigne
        $enseigne = $this->get('enseigne');
        if (empty($enseigne)) {
            $this->validationErrors[] = 'Enseigne is required';
        } else {
            $enseigneErrors = MondialRelayValidator::validateEnseigne($enseigne);
            $this->validationErrors = array_merge($this->validationErrors, $enseigneErrors);
        }

        // Validate private key
        $privateKey = $this->get('private_key');
        if (empty($privateKey)) {
            $this->validationErrors[] = 'Private key is required';
        } elseif (strlen($privateKey) < 8) {
            $this->validationErrors[] = 'Private key too short (minimum 8 characters)';
        }

        // Validate API URLs
        $apiUrl = $this->get('api_url');
        if (!empty($apiUrl) && !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            $this->validationErrors[] = 'Invalid API URL format';
        }

        $apiV2Url = $this->get('api_v2.url');
        if (!empty($apiV2Url) && !filter_var($apiV2Url, FILTER_VALIDATE_URL)) {
            $this->validationErrors[] = 'Invalid API V2 URL format';
        }

        // Validate API V2 credentials if enabled
        if ($this->get('api_v2.enabled', false)) {
            if (empty($this->get('api_v2.user'))) {
                $this->validationErrors[] = 'API V2 user is required when API V2 is enabled';
            }
            if (empty($this->get('api_v2.password'))) {
                $this->validationErrors[] = 'API V2 password is required when API V2 is enabled';
            }
        }

        return empty($this->validationErrors);
    }

    /**
     * Get validation errors.
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Check if configuration is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validationErrors);
    }

    /**
     * Throw exception if configuration is invalid.
     */
    public function validateOrFail(): void
    {
        if (!$this->isValid()) {
            throw MondialRelayException::validation(
                'Configuration invalide: '.implode(', ', $this->validationErrors),
                $this->validationErrors
            );
        }
    }

    /**
     * Get environment-specific configuration.
     */
    public function getEnvironmentConfig(): array
    {
        $isTestMode = $this->get('test_mode', true);

        return [
            'environment' => $isTestMode ? 'test' : 'production',
            'api_url' => $this->getApiUrl(),
            'api_v2_url' => $this->getApiV2Url(),
            'debug_enabled' => $this->get('debug.enabled', false),
            'timeout' => $this->get('timeout', 30),
            'retry_attempts' => $this->get('retry_attempts', 3),
        ];
    }

    /**
     * Get appropriate API URL based on environment.
     */
    public function getApiUrl(): string
    {
        $customUrl = $this->get('api_url');
        if (!empty($customUrl)) {
            return $customUrl;
        }

        return $this->get('test_mode', true)
            ? 'https://api.mondialrelay.com/Web_Services.asmx'
            : 'https://api.mondialrelay.com/Web_Services.asmx';
    }

    /**
     * Get appropriate API V2 URL based on environment.
     */
    public function getApiV2Url(): string
    {
        $customUrl = $this->get('api_v2.url');
        if (!empty($customUrl)) {
            return $customUrl;
        }

        return $this->get('test_mode', true)
            ? 'https://connect-api.mondialrelay.com/api'
            : 'https://connect-api.mondialrelay.com/api';
    }

    /**
     * Get security configuration.
     */
    public function getSecurityConfig(): array
    {
        return [
            'encrypt_credentials' => $this->get('security.encrypt_credentials', false),
            'validate_ssl' => $this->get('security.validate_ssl', true),
            'allowed_ips' => $this->get('security.allowed_ips', []),
            'rate_limit' => $this->get('security.rate_limit', 100), // requests per minute
        ];
    }

    /**
     * Get debug configuration.
     */
    public function getDebugConfig(): array
    {
        return [
            'enabled' => $this->get('debug.enabled', false),
            'log_to_file' => $this->get('debug.log_to_file', true),
            'display_in_browser' => $this->get('debug.display_in_browser', false),
            'log_level' => $this->get('debug.log_level', 'info'),
            'mask_sensitive_data' => $this->get('debug.mask_sensitive_data', true),
        ];
    }

    /**
     * Get cache configuration.
     */
    public function getCacheConfig(): array
    {
        return [
            'enabled' => $this->get('cache.enabled', true),
            'ttl' => $this->get('cache.ttl', 3600), // 1 hour
            'prefix' => $this->get('cache.prefix', 'mondial_relay_'),
            'store' => $this->get('cache.store', 'default'),
        ];
    }

    /**
     * Get retry configuration.
     */
    public function getRetryConfig(): array
    {
        return [
            'enabled' => $this->get('retry.enabled', true),
            'max_attempts' => $this->get('retry.max_attempts', 3),
            'delay' => $this->get('retry.delay', 1000), // milliseconds
            'backoff_multiplier' => $this->get('retry.backoff_multiplier', 2),
            'retry_on_codes' => $this->get('retry.retry_on_codes', [99]), // System errors
        ];
    }

    /**
     * Get timeout configuration.
     */
    public function getTimeoutConfig(): array
    {
        return [
            'connection_timeout' => $this->get('timeout.connection', 10),
            'request_timeout' => $this->get('timeout.request', 30),
            'soap_timeout' => $this->get('timeout.soap', 60),
            'rest_timeout' => $this->get('timeout.rest', 30),
        ];
    }

    /**
     * Export configuration for logging (with sensitive data masked).
     */
    public function toArray(bool $maskSensitive = true): array
    {
        $config = $this->config;

        if ($maskSensitive) {
            $sensitiveKeys = [
                'private_key',
                'api_v2.password',
                'security.encrypt_key',
            ];

            foreach ($sensitiveKeys as $key) {
                if (data_get($config, $key)) {
                    data_set($config, $key, '***MASKED***');
                }
            }
        }

        return $config;
    }

    /**
     * Create configuration from Laravel config.
     */
    public static function fromLaravelConfig(array $config): self
    {
        return new self($config);
    }

    /**
     * Create configuration from environment variables.
     */
    public static function fromEnvironment(): self
    {
        return new self([
            'enseigne' => env('MONDIAL_RELAY_ENSEIGNE'),
            'private_key' => env('MONDIAL_RELAY_PRIVATE_KEY'),
            'test_mode' => env('MONDIAL_RELAY_TEST_MODE', true),
            'api_url' => env('MONDIAL_RELAY_API_URL'),
            'debug' => [
                'enabled' => env('MONDIAL_RELAY_DEBUG', false),
                'log_to_file' => env('MONDIAL_RELAY_DEBUG_LOG_FILE', true),
                'display_in_browser' => env('MONDIAL_RELAY_DEBUG_BROWSER', false),
            ],
            'api_v2' => [
                'enabled' => env('MONDIAL_RELAY_API_V2_ENABLED', false),
                'url' => env('MONDIAL_RELAY_API_V2_URL'),
                'user' => env('MONDIAL_RELAY_API_V2_USER'),
                'password' => env('MONDIAL_RELAY_API_V2_PASSWORD'),
            ],
        ]);
    }

    /**
     * Get configuration summary for diagnostics.
     */
    public function getSummary(): array
    {
        return [
            'enseigne' => $this->get('enseigne') ? 'SET' : 'NOT SET',
            'private_key' => $this->get('private_key') ? 'SET' : 'NOT SET',
            'test_mode' => $this->get('test_mode', true) ? 'YES' : 'NO',
            'api_v2_enabled' => $this->get('api_v2.enabled', false) ? 'YES' : 'NO',
            'debug_enabled' => $this->get('debug.enabled', false) ? 'YES' : 'NO',
            'is_valid' => $this->isValid() ? 'YES' : 'NO',
            'validation_errors' => count($this->validationErrors),
        ];
    }
}
