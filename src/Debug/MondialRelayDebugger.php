<?php

namespace Bmwsly\MondialRelayApi\Debug;

use Illuminate\Support\Facades\Log;

class MondialRelayDebugger
{
    private bool $enabled = false;
    private bool $logToFile = true;
    private bool $displayInBrowser = false;
    private array $requests = [];
    private array $responses = [];

    public function __construct(bool $enabled = false, bool $logToFile = true, bool $displayInBrowser = false)
    {
        $this->enabled = $enabled;
        $this->logToFile = $logToFile;
        $this->displayInBrowser = $displayInBrowser;
    }

    /**
     * Enable debug mode.
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable debug mode.
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Check if debug is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable logging to file.
     */
    public function enableFileLogging(): self
    {
        $this->logToFile = true;

        return $this;
    }

    /**
     * Enable browser display.
     */
    public function enableBrowserDisplay(): self
    {
        $this->displayInBrowser = true;

        return $this;
    }

    /**
     * Log a SOAP request.
     */
    public function logSoapRequest(string $method, array $parameters, string $endpoint): void
    {
        if (!$this->enabled) {
            return;
        }

        $requestData = [
            'type' => 'SOAP',
            'method' => $method,
            'endpoint' => $endpoint,
            'parameters' => $this->sanitizeParameters($parameters),
            'timestamp' => now()->toISOString(),
        ];

        $this->requests[] = $requestData;

        if ($this->logToFile) {
            Log::channel('mondial_relay')->info('SOAP Request', $requestData);
        }

        if ($this->displayInBrowser) {
            $this->displayRequest($requestData);
        }
    }

    /**
     * Log a SOAP response.
     */
    public function logSoapResponse(string $method, $response, ?string $rawRequest = null, ?string $rawResponse = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $responseData = [
            'type' => 'SOAP',
            'method' => $method,
            'response' => $response,
            'raw_request' => $rawRequest,
            'raw_response' => $rawResponse,
            'timestamp' => now()->toISOString(),
        ];

        $this->responses[] = $responseData;

        if ($this->logToFile) {
            Log::channel('mondial_relay')->info('SOAP Response', $responseData);
        }

        if ($this->displayInBrowser) {
            $this->displayResponse($responseData);
        }
    }

    /**
     * Log a REST request.
     */
    public function logRestRequest(string $method, string $url, string $xmlData): void
    {
        if (!$this->enabled) {
            return;
        }

        $requestData = [
            'type' => 'REST',
            'method' => $method,
            'url' => $url,
            'xml_data' => $xmlData,
            'timestamp' => now()->toISOString(),
        ];

        $this->requests[] = $requestData;

        if ($this->logToFile) {
            Log::channel('mondial_relay')->info('REST Request', $requestData);
        }

        if ($this->displayInBrowser) {
            $this->displayRequest($requestData);
        }
    }

    /**
     * Log a REST response.
     */
    public function logRestResponse(string $method, string $url, string $response): void
    {
        if (!$this->enabled) {
            return;
        }

        $responseData = [
            'type' => 'REST',
            'method' => $method,
            'url' => $url,
            'response' => $response,
            'timestamp' => now()->toISOString(),
        ];

        $this->responses[] = $responseData;

        if ($this->logToFile) {
            Log::channel('mondial_relay')->info('REST Response', $responseData);
        }

        if ($this->displayInBrowser) {
            $this->displayResponse($responseData);
        }
    }

    /**
     * Log an error.
     */
    public function logError(string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $errorData = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        if ($this->logToFile) {
            Log::channel('mondial_relay')->error('Mondial Relay Error', $errorData);
        }

        if ($this->displayInBrowser) {
            $this->displayError($errorData);
        }
    }

    /**
     * Get all logged requests.
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get all logged responses.
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * Clear all logs.
     */
    public function clear(): void
    {
        $this->requests = [];
        $this->responses = [];
    }

    /**
     * Get debug summary.
     */
    public function getSummary(): array
    {
        return [
            'enabled' => $this->enabled,
            'total_requests' => count($this->requests),
            'total_responses' => count($this->responses),
            'soap_requests' => count(array_filter($this->requests, fn ($r) => $r['type'] === 'SOAP')),
            'rest_requests' => count(array_filter($this->requests, fn ($r) => $r['type'] === 'REST')),
        ];
    }

    /**
     * Sanitize parameters to remove sensitive data.
     */
    private function sanitizeParameters(array $parameters): array
    {
        $sanitized = $parameters;

        // Remove or mask sensitive fields
        $sensitiveFields = ['Security', 'password', 'key', 'token'];

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '***MASKED***';
            }
        }

        return $sanitized;
    }

    /**
     * Display request in browser.
     */
    private function displayRequest(array $requestData): void
    {
        if (!app()->runningInConsole()) {
            echo $this->formatForBrowser('REQUEST', $requestData);
        }
    }

    /**
     * Display response in browser.
     */
    private function displayResponse(array $responseData): void
    {
        if (!app()->runningInConsole()) {
            echo $this->formatForBrowser('RESPONSE', $responseData);
        }
    }

    /**
     * Display error in browser.
     */
    private function displayError(array $errorData): void
    {
        if (!app()->runningInConsole()) {
            echo $this->formatForBrowser('ERROR', $errorData, 'error');
        }
    }

    /**
     * Format data for browser display.
     */
    private function formatForBrowser(string $type, array $data, string $level = 'info'): string
    {
        $color = match ($level) {
            'error' => '#ff4444',
            'warning' => '#ffaa00',
            default => '#0066cc',
        };

        $html = "<div style='border: 1px solid {$color}; margin: 10px; padding: 10px; font-family: monospace; background: #f9f9f9;'>";
        $html .= "<h3 style='color: {$color}; margin: 0 0 10px 0;'>Mondial Relay {$type}</h3>";
        $html .= "<pre style='margin: 0; white-space: pre-wrap; word-wrap: break-word;'>";
        $html .= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $html .= '</pre>';
        $html .= '</div>';

        return $html;
    }
}
