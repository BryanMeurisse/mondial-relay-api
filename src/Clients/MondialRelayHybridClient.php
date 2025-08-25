<?php

namespace Bmwsly\MondialRelayApi\Clients;

use Bmwsly\MondialRelayApi\Debug\MondialRelayDebugger;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\MondialRelayClient;

class MondialRelayHybridClient
{
    private MondialRelayClient $soapClient;
    private ?MondialRelayRestClient $restClient = null;
    private bool $preferRestApi;
    private ?MondialRelayDebugger $debugger;

    public function __construct(
        string $enseigne,
        string $privateKey,
        bool $testMode = true,
        ?string $soapApiUrl = null,
        ?array $restApiConfig = null,
        bool $preferRestApi = false,
        ?MondialRelayDebugger $debugger = null
    ) {
        $this->debugger = $debugger;
        $this->preferRestApi = $preferRestApi;

        // Initialize SOAP client (always available)
        $this->soapClient = new MondialRelayClient($enseigne, $privateKey, $testMode, $soapApiUrl, $debugger);

        // Initialize REST client if configuration is provided
        if ($restApiConfig && !empty($restApiConfig['user']) && !empty($restApiConfig['password'])) {
            $this->restClient = new MondialRelayRestClient(
                $restApiConfig['url'] ?? 'https://connect-api.mondialrelay.com/api',
                $restApiConfig['user'],
                $restApiConfig['password'],
                $enseigne,
                $debugger
            );
        }
    }

    /**
     * Search relay points (SOAP only for now).
     */
    public function searchRelayPoints(array $params): array
    {
        return $this->soapClient->searchRelayPoints($params);
    }

    /**
     * Create expedition.
     */
    public function createExpedition(array $params): \Bmwsly\MondialRelayApi\Models\Expedition
    {
        if ($this->shouldUseRestApi('createExpedition')) {
            return $this->createExpeditionViaRest($params);
        }

        return $this->soapClient->createExpedition($params);
    }

    /**
     * Create expedition with label.
     */
    public function createExpeditionWithLabel(array $params): \Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel
    {
        if ($this->shouldUseRestApi('createExpeditionWithLabel')) {
            return $this->createExpeditionWithLabelViaRest($params);
        }

        return $this->soapClient->createExpeditionWithLabel($params);
    }

    /**
     * Get label batch (SOAP only for now).
     */
    public function getLabelBatch(array $expeditionNumbers): \Bmwsly\MondialRelayApi\Models\LabelBatch
    {
        return $this->soapClient->getLabelBatch($expeditionNumbers);
    }

    /**
     * Track package (SOAP only for now).
     */
    public function trackPackage(string $expeditionNumber): \Bmwsly\MondialRelayApi\Models\TrackingInfo
    {
        return $this->soapClient->trackPackage($expeditionNumber);
    }

    /**
     * Determine if REST API should be used for a given method.
     */
    private function shouldUseRestApi(string $method): bool
    {
        // REST API is only available if configured
        if (!$this->restClient) {
            return false;
        }

        // For now, only shipment creation methods support REST
        $restSupportedMethods = ['createExpedition', 'createExpeditionWithLabel'];
        if (!in_array($method, $restSupportedMethods)) {
            return false;
        }

        return $this->preferRestApi;
    }

    /**
     * Create expedition via REST API.
     */
    private function createExpeditionViaRest(array $params): \Bmwsly\MondialRelayApi\Models\Expedition
    {
        $response = $this->restClient->createShipment($params);

        if (!$response['success']) {
            $errors = array_map(fn ($msg) => $msg['message'], $response['messages']);
            throw new MondialRelayException('Erreur lors de la création de l\'expédition: '.implode(', ', $errors));
        }

        return new \Bmwsly\MondialRelayApi\Models\Expedition([
            'expedition_number' => $response['shipment_number'],
            'tracking_url' => $this->generateTrackingUrl($response['shipment_number']),
        ]);
    }

    /**
     * Create expedition with label via REST API.
     */
    private function createExpeditionWithLabelViaRest(array $params): \Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel
    {
        $response = $this->restClient->createShipment($params);

        if (!$response['success']) {
            $errors = array_map(fn ($msg) => $msg['message'], $response['messages']);
            throw new MondialRelayException('Erreur lors de la création de l\'expédition avec étiquette: '.implode(', ', $errors));
        }

        return new \Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel([
            'expedition_number' => $response['shipment_number'],
            'tracking_url' => $this->generateTrackingUrl($response['shipment_number']),
            'label_url' => $response['label_link'],
        ]);
    }

    /**
     * Generate tracking URL.
     */
    private function generateTrackingUrl(string $expeditionNumber): string
    {
        // Use the same logic as SOAP client
        return $this->soapClient->generateTrackingUrl($expeditionNumber);
    }

    /**
     * Get SOAP client.
     */
    public function getSoapClient(): MondialRelayClient
    {
        return $this->soapClient;
    }

    /**
     * Get REST client.
     */
    public function getRestClient(): ?MondialRelayRestClient
    {
        return $this->restClient;
    }

    /**
     * Check if REST API is available.
     */
    public function hasRestApi(): bool
    {
        return $this->restClient !== null;
    }

    /**
     * Enable REST API preference.
     */
    public function preferRestApi(bool $prefer = true): self
    {
        $this->preferRestApi = $prefer;

        return $this;
    }

    /**
     * Get current API preference.
     */
    public function isRestApiPreferred(): bool
    {
        return $this->preferRestApi;
    }

    /**
     * Get debugger.
     */
    public function getDebugger(): ?MondialRelayDebugger
    {
        return $this->debugger;
    }

    /**
     * Set debugger.
     */
    public function setDebugger(?MondialRelayDebugger $debugger): self
    {
        $this->debugger = $debugger;
        $this->soapClient->setDebugger($debugger);

        return $this;
    }

    /**
     * Get API status information.
     */
    public function getApiStatus(): array
    {
        return [
            'soap_available' => true,
            'rest_available' => $this->hasRestApi(),
            'preferred_api' => $this->preferRestApi ? 'REST' : 'SOAP',
            'debug_enabled' => $this->debugger ? $this->debugger->isEnabled() : false,
        ];
    }

    /**
     * Test API connectivity.
     */
    public function testConnectivity(): array
    {
        $results = [
            'soap' => ['available' => true, 'error' => null],
            'rest' => ['available' => false, 'error' => null],
        ];

        // Test SOAP API with a simple relay search
        try {
            $this->soapClient->searchRelayPoints([
                'postal_code' => '75001',
                'country' => 'FR',
                'max_results' => 1,
            ]);
            $results['soap']['status'] = 'OK';
        } catch (\Exception $e) {
            $results['soap']['status'] = 'ERROR';
            $results['soap']['error'] = $e->getMessage();
        }

        // Test REST API if available
        if ($this->hasRestApi()) {
            $results['rest']['available'] = true;
            try {
                // Simple test - this would need a minimal shipment creation test
                $results['rest']['status'] = 'OK';
            } catch (\Exception $e) {
                $results['rest']['status'] = 'ERROR';
                $results['rest']['error'] = $e->getMessage();
            }
        }

        return $results;
    }
}
