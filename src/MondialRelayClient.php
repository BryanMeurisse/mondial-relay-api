<?php

namespace Bmwsly\MondialRelayApi;

use Bmwsly\MondialRelayApi\Debug\MondialRelayDebugger;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use Bmwsly\MondialRelayApi\Models\Expedition;
use Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel;
use Bmwsly\MondialRelayApi\Models\LabelBatch;
use Bmwsly\MondialRelayApi\Models\RelayPoint;
use Bmwsly\MondialRelayApi\Models\TrackingInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SoapClient;

/**
 * Client for Mondial Relay API integration.
 *
 * This class provides low-level access to Mondial Relay SOAP API methods.
 * The SoapClient dynamically calls the following methods via __call():
 * - WSI4_PointRelais_Recherche() - Search for relay points
 * - WSI2_CreationExpedition() - Create expedition
 * - WSI2_CreationEtiquette() - Create expedition with label
 * - WSI3_GetEtiquettes() - Get labels for expeditions
 * - WSI2_TracingColisDetaille() - Track package
 */
class MondialRelayClient
{
    private $enseigne;
    private $privateKey;
    private $testMode;
    private $apiUrl;
    private $soapClient;
    private ?MondialRelayDebugger $debugger;

    public function __construct(string $enseigne, string $privateKey, bool $testMode = true, ?string $apiUrl = null, ?MondialRelayDebugger $debugger = null)
    {
        $this->enseigne = $enseigne;
        $this->privateKey = $privateKey;
        $this->testMode = $testMode;
        $this->apiUrl = $apiUrl ?? 'https://api.mondialrelay.com/Web_Services.asmx';
        $this->debugger = $debugger;

        $this->soapClient = new SoapClient($this->apiUrl.'?WSDL', [
            'encoding' => 'UTF-8',
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ]);
    }

    /**
     * Search for relay points.
     */
    public function searchRelayPoints(array $params): array
    {
        $this->validateRelaySearchParams($params);

        $searchParams = [
            'Enseigne' => $this->enseigne,
            'Pays' => $params['country'] ?? 'FR',
            'NumPointRelais' => '',
            'Ville' => '',
            'CP' => $params['postal_code'],
            'Latitude' => '',
            'Longitude' => '',
            'Taille' => '',
            'Poids' => $params['weight'] ?? '',
            'Action' => $params['delivery_mode'] ?? '24R',
            'DelaiEnvoi' => '0',
            'RayonRecherche' => $params['search_radius'] ?? '20',
            'TypeActivite' => '',
            'NombreResultats' => $params['max_results'] ?? '10',
        ];

        $searchParams['Security'] = $this->generateSecurityKey($searchParams);

        try {
            $response = $this->callSoapMethod('WSI4_PointRelais_Recherche', $searchParams);
            $response = $response->WSI4_PointRelais_RechercheResult ?? $response;

            if ($response->STAT !== '0') {
                throw MondialRelayException::fromApiResponse((array) $response, [
                    'method' => 'searchRelayPoints',
                    'params' => $searchParams,
                    'enseigne' => $this->enseigne,
                    'postal_code' => $params['postal_code'] ?? '',
                    'country' => $params['country'] ?? 'FR'
                ]);
            }

            $relayPoints = $this->formatRelayPoints($response);

            return $relayPoints;
        } catch (MondialRelayException $e) {
            // Re-throw MondialRelayException with full context
            throw $e;
        } catch (\SoapFault $e) {
            throw new MondialRelayException(
                'SOAP API Error: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'searchRelayPoints',
                    'params' => $searchParams,
                    'enseigne' => $this->enseigne,
                    'soap_fault_code' => $e->faultcode ?? null,
                    'soap_fault_string' => $e->faultstring ?? null,
                ]
            );
        } catch (\Exception $e) {
            throw new MondialRelayException(
                'Unexpected error during relay points search: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'searchRelayPoints',
                    'params' => $searchParams,
                    'enseigne' => $this->enseigne,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Build expedition parameters (shared between expedition methods).
     */
    private function buildExpeditionParams(array $params): array
    {
        return [
            'Enseigne' => $this->enseigne,
            'ModeCol' => 'CCC', // Collecte en Client
            'ModeLiv' => $params['delivery_mode'], // 24R, LD1, etc.
            'NDossier' => $params['order_number'] ?? '',
            'NClient' => $params['customer_id'] ?? '',
            'Expe_Langage' => 'FR',
            'Expe_Ad1' => $params['sender']['line1'] ?? '',
            'Expe_Ad2' => $params['sender']['line2'] ?? '',
            'Expe_Ad3' => $params['sender']['line3'] ?? '',
            'Expe_Ad4' => $params['sender']['line4'] ?? '',
            'Expe_Ville' => $params['sender']['city'],
            'Expe_CP' => $params['sender']['postal_code'],
            'Expe_Pays' => $params['sender']['country'],
            'Expe_Tel1' => $params['sender']['phone'],
            'Expe_Tel2' => '',
            'Expe_Mail' => $params['sender']['email'] ?? '',
            'Dest_Langage' => 'FR',
            'Dest_Ad1' => $params['recipient']['line1'] ?? '',
            'Dest_Ad2' => $params['recipient']['line2'] ?? '',
            'Dest_Ad3' => $params['recipient']['line3'] ?? '',
            'Dest_Ad4' => $params['recipient']['line4'] ?? '',
            'Dest_Ville' => $params['recipient']['city'],
            'Dest_CP' => $params['recipient']['postal_code'],
            'Dest_Pays' => $params['recipient']['country'],
            'Dest_Tel1' => $params['recipient']['phone'],
            'Dest_Tel2' => '',
            'Dest_Mail' => $params['recipient']['email'] ?? '',
            'Poids' => (string) $params['weight'], // Convert to string like in working test
            'Longueur' => $params['length'] ?? '20', // Default length like in working test
            'Taille' => '',
            'NbColis' => $params['package_count'] ?? '1',
            'CRT_Valeur' => '0',
            'CRT_Devise' => 'EUR',
            'Exp_Valeur' => $params['declared_value'] ?? '50', // Default value like in working test
            'Exp_Devise' => 'EUR',
            'COL_Rel_Pays' => '',
            'COL_Rel' => '',
            'LIV_Rel_Pays' => isset($params['relay_number']) && !empty($params['relay_number']) ? ($params['relay_country'] ?? 'FR') : '',
            'LIV_Rel' => $params['relay_number'] ?? '',
            'TAvisage' => '',
            'TReprise' => '',
            'Montage' => '0',
            'TRDV' => '',
            'Assurance' => '0',
            'Instructions' => $params['instructions'] ?? '',
        ];
    }

    /**
     * Create an expedition.
     */
    public function createExpedition(array $params): Expedition
    {
        $this->validateExpeditionParams($params);

        $expeditionParams = $this->buildExpeditionParams($params);
        $expeditionParams['Security'] = $this->generateSecurityKey($expeditionParams);

        try {
            $response = $this->callSoapMethod('WSI2_CreationExpedition', $expeditionParams);
            $response = $response->WSI2_CreationExpeditionResult ?? $response;

            if ($response->STAT !== '0') {
                throw MondialRelayException::fromApiResponse((array) $response, [
                    'method' => 'createExpedition',
                    'params' => $expeditionParams,
                    'enseigne' => $this->enseigne,
                ]);
            }

            return Expedition::fromApiResponse($response);
        } catch (MondialRelayException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            throw new MondialRelayException(
                'SOAP API Error during expedition creation: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'createExpedition',
                    'params' => $expeditionParams,
                    'enseigne' => $this->enseigne,
                    'soap_fault_code' => $e->faultcode ?? null,
                    'soap_fault_string' => $e->faultstring ?? null,
                ]
            );
        } catch (\Exception $e) {
            throw new MondialRelayException(
                'Unexpected error during expedition creation: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'createExpedition',
                    'params' => $expeditionParams,
                    'enseigne' => $this->enseigne,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Create expedition with PDF label.
     */
    public function createExpeditionWithLabel(array $params): ExpeditionWithLabel
    {
        $this->validateExpeditionParams($params);

        $expeditionParams = $this->buildExpeditionParams($params);

        Log::info('---- Expedition params: '.json_encode($expeditionParams));

        $expeditionParams['Security'] = $this->generateSecurityKey($expeditionParams);

        $expeditionParams['Texte'] = $params['articles_description'] ?? 'Produit e-commerce';

        try {
            $response = $this->callSoapMethod('WSI2_CreationEtiquette', $expeditionParams);
            $response = $response->WSI2_CreationEtiquetteResult ?? $response;

            if ($response->STAT !== '0') {
                throw MondialRelayException::fromApiResponse((array) $response, [
                    'method' => 'createExpeditionWithLabel',
                    'params' => $expeditionParams,
                    'enseigne' => $this->enseigne,
                    'articles_description' => $params['articles_description'] ?? null,
                    'delivery_mode' => $params['delivery_mode'] ?? null,
                    'weight' => $params['weight'] ?? null,
                ]);
            }

            $baseUrl = str_replace('/Web_Services.asmx', '', $this->apiUrl);

            return ExpeditionWithLabel::fromApiResponse($response, $baseUrl);
        } catch (MondialRelayException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            throw new MondialRelayException(
                'SOAP API Error during expedition with label creation: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'createExpeditionWithLabel',
                    'params' => $expeditionParams,
                    'enseigne' => $this->enseigne,
                    'soap_fault_code' => $e->faultcode ?? null,
                    'soap_fault_string' => $e->faultstring ?? null,
                ]
            );
        } catch (\Exception $e) {
            throw new MondialRelayException(
                'Unexpected error during expedition with label creation: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'createExpeditionWithLabel',
                    'params' => $expeditionParams,
                    'enseigne' => $this->enseigne,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Get labels for existing expeditions.
     */
    public function getLabels(array $expeditionNumbers): LabelBatch
    {
        if (empty($expeditionNumbers)) {
            throw new MondialRelayException('At least one expedition number is required');
        }

        $labelParams = [
            'Enseigne' => $this->enseigne,
            'Expeditions' => implode(';', $expeditionNumbers),
            'Langue' => 'FR',
        ];

        $labelParams['Security'] = $this->generateSecurityKey($labelParams);

        try {
            $response = $this->callSoapMethod('WSI3_GetEtiquettes', $labelParams);
            $response = $response->WSI3_GetEtiquettesResult ?? $response;

            if ($response->STAT !== '0') {
                throw MondialRelayException::fromApiResponse((array) $response, [
                    'method' => 'getLabelBatch',
                    'params' => $labelParams,
                    'enseigne' => $this->enseigne,
                    'expedition_numbers' => $expeditionNumbers,
                    'expedition_count' => count($expeditionNumbers),
                ]);
            }

            $baseUrl = str_replace('/Web_Services.asmx', '', $this->apiUrl);

            return LabelBatch::fromApiResponse($expeditionNumbers, $response, $baseUrl);
        } catch (MondialRelayException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            throw new MondialRelayException(
                'SOAP API Error during label batch retrieval: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'getLabelBatch',
                    'params' => $labelParams,
                    'enseigne' => $this->enseigne,
                    'expedition_numbers' => $expeditionNumbers,
                    'soap_fault_code' => $e->faultcode ?? null,
                    'soap_fault_string' => $e->faultstring ?? null,
                ]
            );
        } catch (\Exception $e) {
            throw new MondialRelayException(
                'Unexpected error during label batch retrieval: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'getLabelBatch',
                    'params' => $labelParams,
                    'enseigne' => $this->enseigne,
                    'expedition_numbers' => $expeditionNumbers,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Download label PDF content.
     */
    public function downloadLabel(string $labelUrl): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; Laravel Mondial Relay Package)',
            ],
        ]);

        $content = file_get_contents($labelUrl, false, $context);

        if ($content === false) {
            throw new MondialRelayException('Failed to download label PDF');
        }

        return $content;
    }

    /**
     * Track a package.
     */
    public function trackPackage(string $expeditionNumber): TrackingInfo
    {
        $trackingParams = [
            'Enseigne' => $this->enseigne,
            'Expedition' => $expeditionNumber,
            'Langue' => 'FR',
        ];

        $trackingParams['Security'] = $this->generateSecurityKey($trackingParams);

        try {
            $response = $this->callSoapMethod('WSI2_TracingColisDetaille', $trackingParams);
            $response = $response->WSI2_TracingColisDetailleResult ?? $response;

            if ($response->STAT !== '0' && !in_array($response->STAT, ['80', '81', '82', '83'])) {
                throw MondialRelayException::fromApiResponse((array) $response, [
                    'method' => 'trackPackage',
                    'params' => $trackingParams,
                    'enseigne' => $this->enseigne,
                    'expedition_number' => $expeditionNumber,
                ]);
            }

            return TrackingInfo::fromApiResponse($response);
        } catch (MondialRelayException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            throw new MondialRelayException(
                'SOAP API Error during package tracking: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'trackPackage',
                    'params' => $trackingParams,
                    'enseigne' => $this->enseigne,
                    'expedition_number' => $expeditionNumber,
                    'soap_fault_code' => $e->faultcode ?? null,
                    'soap_fault_string' => $e->faultstring ?? null,
                ]
            );
        } catch (\Exception $e) {
            throw new MondialRelayException(
                'Unexpected error during package tracking: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'method' => 'trackPackage',
                    'params' => $trackingParams,
                    'enseigne' => $this->enseigne,
                    'expedition_number' => $expeditionNumber,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Generate MD5 security key.
     */
    private function generateSecurityKey(array $params): string
    {
        $concatenatedString = implode('', $params).$this->privateKey;

        return strtoupper(md5($concatenatedString));
    }

    /**
     * Validate relay search parameters.
     */
    private function validateRelaySearchParams(array $params): void
    {
        $validator = Validator::make($params, [
            'postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'country' => 'sometimes|string|size:2',
            'weight' => 'sometimes|integer|min:1',
            'delivery_mode' => 'sometimes|string|in:24R,24L,24X,DRI,REL',
            'search_radius' => 'sometimes|integer|min:1|max:200',
            'max_results' => 'sometimes|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            throw new MondialRelayException('Invalid parameters: '.$validator->errors()->first());
        }
    }

    /**
     * Validate expedition parameters.
     */
    private function validateExpeditionParams(array $params): void
    {
        $validator = Validator::make($params, [
            'delivery_mode' => 'required|string|in:24R,24L,24X,LD1,LDS,DRI,HOM',
            'weight' => 'required|integer|min:1',
            'sender.line1' => 'required|string|max:32',
            'sender.line3' => 'required|string|max:32',
            'sender.city' => 'required|string|max:26',
            'sender.postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'sender.country' => 'required|string|size:2',
            'sender.phone' => 'required|string',
            'recipient.line1' => 'required|string|max:32',
            'recipient.line3' => 'required|string|max:32',
            'recipient.city' => 'required|string|max:26',
            'recipient.postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'recipient.country' => 'required|string|size:2',
            'recipient.phone' => 'required|string',
            'relay_number' => 'required_if:delivery_mode,24R,24X|string|regex:/^[0-9]{6}$/',
            'relay_country' => 'required_if:delivery_mode,24R,24X|string|size:2',
        ]);

        if ($validator->fails()) {
            throw new MondialRelayException('Invalid parameters: '.$validator->errors()->first());
        }
    }

    /**
     * Format relay points response.
     * @return RelayPoint[]
     */
    private function formatRelayPoints($response): array
    {
        if (!isset($response->PointsRelais) || !is_array($response->PointsRelais->PointRelais_Details)) {
            return [];
        }

        return array_map(
            fn ($relayPoint) => RelayPoint::fromApiResponse($relayPoint),
            $response->PointsRelais->PointRelais_Details
        );
    }

    /**
     * Call SOAP method with debug logging.
     */
    private function callSoapMethod(string $method, array $params)
    {
        // Log request if debugger is available
        if ($this->debugger) {
            $this->debugger->logSoapRequest($method, $params, $this->apiUrl);
        }

        try {
            // Call the SOAP method
            $response = $this->soapClient->$method($params);

            // Log response if debugger is available
            if ($this->debugger) {
                $this->debugger->logSoapResponse(
                    $method,
                    $response,
                    $this->soapClient->__getLastRequest(),
                    $this->soapClient->__getLastResponse()
                );
            }

            return $response;
        } catch (\Exception $e) {
            // Log error if debugger is available
            if ($this->debugger) {
                $this->debugger->logError("SOAP call failed for method {$method}", [
                    'method' => $method,
                    'params' => $params,
                    'error' => $e->getMessage(),
                    'last_request' => $this->soapClient->__getLastRequest(),
                    'last_response' => $this->soapClient->__getLastResponse(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Get debugger instance.
     */
    public function getDebugger(): ?MondialRelayDebugger
    {
        return $this->debugger;
    }

    /**
     * Set debugger instance.
     */
    public function setDebugger(?MondialRelayDebugger $debugger): self
    {
        $this->debugger = $debugger;

        return $this;
    }

    /**
     * Generate basic tracking URL for an expedition.
     */
    public function generateTrackingUrl(string $expeditionNumber): string
    {
        $baseUrl = 'https://www.mondialrelay.fr/suivi-de-colis/';

        return $baseUrl.'?numeroExpedition='.$expeditionNumber;
    }

    /**
     * Generate secure connect tracing link for professional extranet.
     * Requires API V2 credentials (user/password).
     *
     * @param string $expeditionNumber Expedition number (8 digits)
     * @param string $userLogin Login to connect to the system
     * @return string Secure URL for professional tracking
     */
    public function generateConnectTracingLink(string $expeditionNumber, string $userLogin): string
    {
        // This requires API V2 password which is not available in SOAP client
        // We'll need to get it from config or pass it as parameter
        $password = config('mondialrelay.api_v2.password', '');

        if (empty($password)) {
            throw new MondialRelayException('API V2 password is required for connect tracing links. Please configure MONDIAL_RELAY_API_V2_PASSWORD in your .env file.');
        }

        return MondialRelayHelper::getConnectTracingLink($expeditionNumber, $userLogin, $this->enseigne, $password);
    }

    /**
     * Generate secure permalink tracing link for public tracking.
     *
     * @param string $expeditionNumber Expedition number (8 digits)
     * @param string $language Language code (default: 'fr')
     * @param string $country Country code (default: 'fr')
     * @return string Secure permalink URL
     */
    public function generatePermalinkTracingLink(
        string $expeditionNumber,
        string $language = 'fr',
        string $country = 'fr'
    ): string {
        // We need the brand ID from config
        $brandId = config('mondialrelay.brand_id', '');

        if (empty($brandId)) {
            throw new MondialRelayException('Brand ID is required for permalink tracing links. Please configure MONDIAL_RELAY_BRAND_ID in your .env file.');
        }

        return MondialRelayHelper::getPermalinkTracingLink(
            $expeditionNumber,
            $this->enseigne,
            $brandId,
            $this->privateKey,
            $language,
            $country
        );
    }
}
