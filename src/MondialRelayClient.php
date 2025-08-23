<?php

namespace Bmwsly\MondialRelayApi;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Models\Expedition;
use Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel;
use Bmwsly\MondialRelayApi\Models\LabelBatch;
use Bmwsly\MondialRelayApi\Models\RelayPoint;
use Bmwsly\MondialRelayApi\Models\TrackingInfo;
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

    public function __construct(string $enseigne, string $privateKey, bool $testMode = true, ?string $apiUrl = null)
    {
        $this->enseigne = $enseigne;
        $this->privateKey = $privateKey;
        $this->testMode = $testMode;
        $this->apiUrl = $apiUrl ?? 'https://api.mondialrelay.com/Web_Services.asmx';

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
            $response = $this->soapClient->WSI4_PointRelais_Recherche($searchParams);

            if ($response->STAT !== '0') {
                throw new MondialRelayException($this->getErrorMessage($response->STAT), (int) $response->STAT);
            }

            return $this->formatRelayPoints($response);
        } catch (\Exception $e) {
            throw new MondialRelayException('API call failed: '.$e->getMessage());
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
            'Expe_Ad1' => $params['sender']['name'],
            'Expe_Ad2' => $params['sender']['company'] ?? '',
            'Expe_Ad3' => $params['sender']['address'],
            'Expe_Ad4' => $params['sender']['address_complement'] ?? '',
            'Expe_Ville' => $params['sender']['city'],
            'Expe_CP' => $params['sender']['postal_code'],
            'Expe_Pays' => $params['sender']['country'],
            'Expe_Tel1' => $params['sender']['phone'],
            'Expe_Tel2' => '',
            'Expe_Mail' => $params['sender']['email'] ?? '',
            'Dest_Langage' => 'FR',
            'Dest_Ad1' => $params['recipient']['name'],
            'Dest_Ad2' => $params['recipient']['company'] ?? '',
            'Dest_Ad3' => $params['recipient']['address'],
            'Dest_Ad4' => $params['recipient']['address_complement'] ?? '',
            'Dest_Ville' => $params['recipient']['city'],
            'Dest_CP' => $params['recipient']['postal_code'],
            'Dest_Pays' => $params['recipient']['country'],
            'Dest_Tel1' => $params['recipient']['phone'],
            'Dest_Tel2' => '',
            'Dest_Mail' => $params['recipient']['email'] ?? '',
            'Poids' => $params['weight'],
            'Longueur' => $params['length'] ?? '',
            'Taille' => '',
            'NbColis' => $params['package_count'] ?? '1',
            'CRT_Valeur' => '0',
            'CRT_Devise' => 'EUR',
            'Exp_Valeur' => $params['declared_value'] ?? '0',
            'Exp_Devise' => 'EUR',
            'COL_Rel_Pays' => '',
            'COL_Rel' => '',
            'LIV_Rel_Pays' => $params['relay_country'] ?? '',
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
            $response = $this->soapClient->WSI2_CreationExpedition($expeditionParams);

            if ($response->STAT !== '0') {
                throw new MondialRelayException($this->getErrorMessage($response->STAT), (int) $response->STAT);
            }

            return Expedition::fromApiResponse($response);
        } catch (\Exception $e) {
            throw new MondialRelayException('API call failed: '.$e->getMessage());
        }
    }

    /**
     * Create expedition with PDF label.
     */
    public function createExpeditionWithLabel(array $params): ExpeditionWithLabel
    {
        $this->validateExpeditionParams($params);

        $expeditionParams = $this->buildExpeditionParams($params);

        // Add optional text field for label (articles description)
        if (isset($params['articles_description'])) {
            $expeditionParams['Texte'] = $params['articles_description'];
        }

        // Don't include 'Texte' field in security hash calculation
        $securityParams = $expeditionParams;
        unset($securityParams['Texte']);
        $expeditionParams['Security'] = $this->generateSecurityKey($securityParams);

        try {
            $response = $this->soapClient->WSI2_CreationEtiquette($expeditionParams);

            if ($response->STAT !== '0') {
                throw new MondialRelayException($this->getErrorMessage($response->STAT), (int) $response->STAT);
            }

            $baseUrl = str_replace('/Web_Services.asmx', '', $this->apiUrl);

            return ExpeditionWithLabel::fromApiResponse($response, $baseUrl);
        } catch (\Exception $e) {
            throw new MondialRelayException('API call failed: '.$e->getMessage());
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
            $response = $this->soapClient->WSI3_GetEtiquettes($labelParams);

            if ($response->STAT !== '0') {
                throw new MondialRelayException($this->getErrorMessage($response->STAT), (int) $response->STAT);
            }

            $baseUrl = str_replace('/Web_Services.asmx', '', $this->apiUrl);

            return LabelBatch::fromApiResponse($expeditionNumbers, $response, $baseUrl);
        } catch (\Exception $e) {
            throw new MondialRelayException('API call failed: '.$e->getMessage());
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
            $response = $this->soapClient->WSI2_TracingColisDetaille($trackingParams);

            if ($response->STAT !== '0' && !in_array($response->STAT, ['80', '81', '82', '83'])) {
                throw new MondialRelayException($this->getErrorMessage($response->STAT), (int) $response->STAT);
            }

            return TrackingInfo::fromApiResponse($response);
        } catch (\Exception $e) {
            throw new MondialRelayException('API call failed: '.$e->getMessage());
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
            'delivery_mode' => 'required|string|in:24R,24L,24X,LD1,LDS,DRI',
            'weight' => 'required|integer|min:1',
            'sender.name' => 'required|string|max:32',
            'sender.address' => 'required|string|max:32',
            'sender.city' => 'required|string|max:26',
            'sender.postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'sender.country' => 'required|string|size:2',
            'sender.phone' => 'required|string',
            'recipient.name' => 'required|string|max:32',
            'recipient.address' => 'required|string|max:32',
            'recipient.city' => 'required|string|max:26',
            'recipient.postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'recipient.country' => 'required|string|size:2',
            'recipient.phone' => 'required|string',
            'relay_number' => 'required_if:delivery_mode,24R,24L,24X|string|regex:/^[0-9]{6}$/',
            'relay_country' => 'required_if:delivery_mode,24R,24L,24X|string|size:2',
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
        if (!isset($response->PointsRelais) || !is_array($response->PointsRelais)) {
            return [];
        }

        return array_map(
            fn ($relayPoint) => RelayPoint::fromApiResponse($relayPoint),
            $response->PointsRelais
        );
    }

    /**
     * Get error message from status code.
     */
    private function getErrorMessage(string $statusCode): string
    {
        $errorMessages = [
            "0" => "Opération effectuée avec succès",
            "1" => "Enseigne invalide",
            "2"=> "Numéro d'enseigne vide ou inexistant",
            "3"=> "Numéro de compte enseigne invalide",
            "5"=> "Numéro de dossier enseigne invalide",
            "7"=> "Numéro de client enseigne invalide",
            "8"=> "Mot de passe ou hachage invalide",
            "9"=> "Ville non reconnu ou non unique",
            "10"=> "Type de collecte invalide",
            "11"=> "Numéro de Relais de Collecte invalide",
            "12"=> "Pays de Relais de collecte invalide",
            "13"=> "Type de livraison invalide",
            "14"=> "Numéro de Relais de livraison invalide",
            "15"=> "Pays de Relais de livraison invalide",
            "20"=> "Poids du colis invalide",
            "21"=> "Taille (Longueur + Hauteur) du colis invalide",
            "22"=> "Taille du Colis invalide",
            "24"=> "Numéro d'expédition ou de suivi invalide",
            "26"=> "Temps de montage invalide",
            "27"=> "Mode de collecte ou de livraison invalide",
            "28"=> "Mode de collecte invalide",
            "29"=> "Mode de livraison invalide",
            "30"=> "Adresse (L1) invalide",
            "31"=> "Adresse (L2) invalide",
            "33"=> "Adresse (L3) invalide",
            "34"=> "Adresse (L4) invalide",
            "35"=> "Ville invalide",
            "36"=> "Code postal invalide",
            "37"=> "Pays invalide",
            "38"=> "Numéro de téléphone invalide",
            "39"=> "Adresse e-mail invalide",
            "40"=> "Paramètres manquants",
            "42"=> "Montant CRT invalide",
            "43"=> "Devise CRT invalide",
            "44"=> "Valeur du colis invalide",
            "45"=> "Devise de la valeur du colis invalide",
            "46"=> "Plage de numéro d'expédition épuisée",
            "47"=> "Nombre de colis invalide",
            "48"=> "Multi-Colis Relais Interdit",
            "Code"=> "retour Libellé",
            "49"=> "Action invalide",
            "60"=> "Champ texte libre invalide (Ce code erreur n'est pas invalidant)",
            "61"=> "Top avisage invalide",
            "62"=> "Instruction de livraison invalide",
            "63"=> "Assurance invalide",
            "64"=> "Temps de montage invalide",
            "65"=> "Top rendez-vous invalide",
            "66"=> "Top reprise invalide",
            "67"=> "Latitude invalide",
            "68"=> "Longitude invalide",
            "69"=> "Code Enseigne invalide",
            "70"=> "Numéro de Point Relais invalide",
            "71"=> "Nature de point de vente non valide",
            "74"=> "Langue invalide",
            "78"=> "Pays de Collecte invalide",
            "79"=> "Pays de Livraison invalide",
            "80"=> "Code tracing : Colis enregistré",
            "81"=> "Code tracing : Colis en traitement chez Mondial Relay",
            "82"=> "Code tracing : Colis livré",
            "83"=> "Code tracing : Anomalie",
            "84"=> "(Réservé Code Tracing)",
            "85"=> "(Réservé Code Tracing)",
            "86"=> "(Réservé Code Tracing)",
            "87"=> "(Réservé Code Tracing)",
            "88"=> "(Réservé Code Tracing)",
            "89"=> "(Réservé Code Tracing)",
            "92"=> "Le code pays du destinataire et le code pays du Point Relais doivent être identiques. Ou Solde insuffisant (comptes prépayés)",
            "93"=> "Aucun élément retourné par le plan de tri. Si vous effectuez une collecte ou une livraison en Point Relais, vérifiez que les Point Relais sont bien disponibles. Si vous effectuez une livraison à domicile, il est probable que le code postal que vous avez indiqué n'existe pas.",
            "94"=> "Colis Inexistant",
            "95"=> "Compte Enseigne non activé",
            "96"=> "Type d'enseigne incorrect en Base",
            "97"=> "Clé de sécurité invalide, Cf. : § « Génération de la clé de sécurité »",
            "98"=> "Erreur générique (Paramètres invalides).Cette erreur masque une autre erreur de la liste et ne peut se produire que dans le cas où lecompte utilisé est en mode « Production ».Cf. : § « Fonctionnement normal et débogage »",
            "99"=> "Erreur générique du service.Cette erreur peut être due à un problème technique du service. Veuillez notifier cette erreur à Mondial Relay en précisant la date et l'heure de la requête ainsi que les paramètres envoyés afin d'effectuer une vérification."
        ];

        return $errorMessages[$statusCode] ?? 'Unknown error (Code: '.$statusCode.')';
    }
}
