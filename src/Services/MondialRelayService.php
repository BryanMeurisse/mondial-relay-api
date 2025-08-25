<?php

namespace Bmwsly\MondialRelayApi\Services;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use Bmwsly\MondialRelayApi\Models\Expedition;
use Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel;
use Bmwsly\MondialRelayApi\Models\LabelBatch;
use Bmwsly\MondialRelayApi\Models\MultiParcelExpedition;
use Bmwsly\MondialRelayApi\Models\Parcel;
use Bmwsly\MondialRelayApi\Models\RelayPoint;
use Bmwsly\MondialRelayApi\Models\TrackingInfo;
use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Validation\MondialRelayValidator;
use Illuminate\Support\Facades\Log;

class MondialRelayService
{
    public function __construct(
        private MondialRelayClient $client
    ) {
    }

    /**
     * Find nearest relay points.
     * @return RelayPoint[]
     */
    public function findNearestRelayPoints(
        string $postalCode,
        string $country = 'FR',
        int $maxResults = 10,
        int $searchRadius = 20
    ): array {
        // Use new strict validation
        $postalErrors = MondialRelayValidator::validatePostalCode($postalCode, $country);
        if (!empty($postalErrors)) {
            throw MondialRelayException::validation('Code postal invalide: '.implode(', ', $postalErrors));
        }

        $countryErrors = MondialRelayValidator::validateCountryCode($country);
        if (!empty($countryErrors)) {
            throw MondialRelayException::validation('Code pays invalide: '.implode(', ', $countryErrors));
        }

        return $this->client->searchRelayPoints([
            'postal_code' => $postalCode,
            'country' => $country,
            'max_results' => $maxResults,
            'search_radius' => $searchRadius,
        ]);
    }

    /**
     * Find relay points for a specific weight and delivery mode.
     * @return RelayPoint[]
     */
    public function findRelayPointsForShipment(
        string $postalCode,
        int $weightInGrams,
        string $deliveryMode = '24R',
        string $country = 'FR',
        int $maxResults = 10
    ): array {
        return $this->client->searchRelayPoints([
            'postal_code' => $postalCode,
            'country' => $country,
            'weight' => $weightInGrams,
            'delivery_mode' => $deliveryMode,
            'max_results' => $maxResults,
        ]);
    }

    /**
     * Create a simple relay point expedition.
     */
    public function createRelayExpedition(
        array $sender,
        array $recipient,
        string $relayNumber,
        int $weightInGrams,
        string $deliveryMode = '24R',
        ?string $orderNumber = null
    ): Expedition {
        // Use new strict validation
        $relayErrors = MondialRelayValidator::validateRelayNumber($relayNumber);
        if (!empty($relayErrors)) {
            throw MondialRelayException::validation('Numéro de point relais invalide: '.implode(', ', $relayErrors));
        }

        // Format and validate data
        $expeditionData = [
            'delivery_mode' => $deliveryMode,
            'weight' => $weightInGrams,
            'order_number' => $orderNumber,
            'sender' => $this->formatSenderData($sender),
            'recipient' => $this->formatRecipientData($recipient),
            'relay_number' => $relayNumber,
            'relay_country' => $recipient['country'] ?? 'FR',
        ];

        // Use new validation approach - validate individual components
        $this->validateExpeditionData($expeditionData);

        return $this->client->createExpedition($expeditionData);
    }



    /**
     * Get detailed tracking information.
     */
    public function getTrackingInfo(string $expeditionNumber): TrackingInfo
    {
        return $this->client->trackPackage($expeditionNumber);
    }

    /**
     * Check if package is delivered.
     */
    public function isPackageDelivered(string $expeditionNumber): bool
    {
        $tracking = $this->getTrackingInfo($expeditionNumber);

        return $tracking->isDelivered();
    }

    /**
     * Get package status summary.
     */
    public function getPackageStatusSummary(string $expeditionNumber): array
    {
        $tracking = $this->getTrackingInfo($expeditionNumber);
        $latestEvent = $tracking->getLatestEvent();

        return [
            'status' => $tracking->getStatusMessage(),
            'is_delivered' => $tracking->isDelivered(),
            'is_in_transit' => $tracking->isInTransit(),
            'latest_event' => $latestEvent ? [
                'label' => $latestEvent->label,
                'date' => $latestEvent->getFormattedDateTime(),
                'location' => $latestEvent->location,
            ] : null,
            'relay_info' => $tracking->hasRelay() ? [
                'name' => $tracking->relayName,
                'number' => $tracking->relayNumber,
            ] : null,
            'tracking_url' => MondialRelayHelper::getTrackingUrl($expeditionNumber),
        ];
    }

    /**
     * Generate basic tracking URL.
     */
    public function generateTrackingUrl(string $expeditionNumber): string
    {
        return $this->client->generateTrackingUrl($expeditionNumber);
    }

    /**
     * Generate secure connect tracing link for professional extranet.
     * Requires API V2 credentials.
     */
    public function generateConnectTracingLink(string $expeditionNumber, string $userLogin): string
    {
        return $this->client->generateConnectTracingLink($expeditionNumber, $userLogin);
    }

    /**
     * Generate secure permalink tracing link for public tracking.
     */
    public function generatePermalinkTracingLink(
        string $expeditionNumber,
        string $language = 'fr',
        string $country = 'fr'
    ): string {
        return $this->client->generatePermalinkTracingLink($expeditionNumber, $language, $country);
    }

    /**
     * Calculate shipping cost.
     */
    public function calculateShippingCost(int $weightInGrams, string $deliveryMode): float
    {
        return MondialRelayHelper::calculateShippingCost($weightInGrams, $deliveryMode);
    }

    /**
     * Get available delivery modes.
     */
    public function getAvailableDeliveryModes(): array
    {
        return MondialRelayHelper::getDeliveryModes();
    }

    /**
     * Format sender data.
     */
    private function formatSenderData(array $sender): array
    {
        return [
            'line1' => MondialRelayHelper::formatAddress($sender['name'] ?? $sender['company'] ?? ''),
            'line2' => MondialRelayHelper::formatAddress($sender['company'] ?? ''),
            'line3' => MondialRelayHelper::formatAddress($sender['address'] ?? ''),
            'line4' => MondialRelayHelper::formatAddress($sender['address_complement'] ?? $sender['address2'] ?? ''),
            'city' => MondialRelayHelper::formatAddress($sender['city']),
            'postal_code' => $sender['postal_code'],
            'country' => strtoupper($sender['country'] ?? 'FR'),
            'phone' => MondialRelayHelper::formatPhoneNumber($sender['phone']),
            'email' => $sender['email'] ?? '',
        ];
    }

    /**
     * Format recipient data.
     */
    private function formatRecipientData(array $recipient): array
    {
        return [
            'line1' => MondialRelayHelper::formatAddress($recipient['name'] ?? ''),
            'line2' => MondialRelayHelper::formatAddress($recipient['company'] ?? ''),
            'line3' => MondialRelayHelper::formatAddress($recipient['address'] ?? ''),
            'line4' => MondialRelayHelper::formatAddress($recipient['address_complement'] ?? $recipient['address2'] ?? ''),
            'city' => MondialRelayHelper::formatAddress($recipient['city']),
            'postal_code' => $recipient['postal_code'],
            'country' => strtoupper($recipient['country'] ?? 'FR'),
            'phone' => MondialRelayHelper::formatPhoneNumber($recipient['phone']),
            'email' => $recipient['email'] ?? '',
        ];
    }

    /**
     * Create an expedition with PDF label.
     */
    public function createExpeditionWithLabel(
        array $sender,
        array $recipient,
        string $relayNumber,
        int $weightInGrams,
        string $deliveryMode = '24R',
        ?string $orderNumber = null,
        ?string $articlesDescription = null
    ): ExpeditionWithLabel {
        // Validate relay number
        if (!MondialRelayHelper::isValidRelayNumber($relayNumber)) {
            throw new MondialRelayException('Numéro de point relais invalide');
        }

        // Format and validate data
        $expeditionData = [
            'delivery_mode' => $deliveryMode,
            'weight' => $weightInGrams,
            'order_number' => $orderNumber,
            'sender' => $this->formatSenderData($sender),
            'recipient' => $this->formatRecipientData($recipient),
            'relay_number' => $relayNumber,
            'relay_country' => $recipient['country'] ?? 'FR',
        ];

        Log::info('Expedition data: ' . json_encode($expeditionData));

        if ($articlesDescription) {
            $expeditionData['articles_description'] = $articlesDescription;
        }

        // Use new validation approach
        $this->validateExpeditionData($expeditionData);

        return $this->client->createExpeditionWithLabel($expeditionData);
    }



    /**
     * Get labels for multiple expeditions.
     */
    public function getLabelsForExpeditions(array $expeditionNumbers): LabelBatch
    {
        if (empty($expeditionNumbers)) {
            throw new MondialRelayException('Au moins un numéro d\'expédition est requis');
        }

        return $this->client->getLabels($expeditionNumbers);
    }

    /**
     * Download label PDF content.
     */
    public function downloadLabelPdf(string $labelUrl): string
    {
        return $this->client->downloadLabel($labelUrl);
    }

    /**
     * Download label PDF for a specific expedition and format.
     */
    public function downloadExpeditionLabel(ExpeditionWithLabel $expedition, string $format = 'A4'): string
    {
        $labelUrl = $expedition->getLabelUrl($format);

        return $this->downloadLabelPdf($labelUrl);
    }

    /**
     * Download batch labels PDF for a specific format.
     */
    public function downloadBatchLabels(LabelBatch $labelBatch, string $format = 'A4'): string
    {
        $pdfUrl = $labelBatch->getPdfUrlByFormat($format);

        return $this->downloadLabelPdf($pdfUrl);
    }

    /**
     * Create multi-parcel expedition.
     */
    public function createMultiParcelExpedition(MultiParcelExpedition $expedition): ExpeditionWithLabel
    {
        // Validate expedition
        $errors = $expedition->validate();
        if (!empty($errors)) {
            throw new MondialRelayException('Expédition multi-colis invalide: '.implode(', ', $errors));
        }

        return $this->client->createExpeditionWithLabel($expedition->toArray());
    }



    /**
     * Calculate total shipping cost for multi-parcel expedition.
     */
    public function calculateMultiParcelShippingCost(MultiParcelExpedition $expedition): float
    {
        $totalWeight = $expedition->getTotalBillableWeight();

        return $this->calculateShippingCost($totalWeight, $expedition->getDeliveryMode());
    }

    /**
     * Validate multi-parcel expedition without creating it.
     */
    public function validateMultiParcelExpedition(MultiParcelExpedition $expedition): array
    {
        return $expedition->validate();
    }

    /**
     * Get multi-parcel expedition summary.
     */
    public function getMultiParcelSummary(MultiParcelExpedition $expedition): array
    {
        $summary = $expedition->getSummary();
        $summary['estimated_cost'] = $this->calculateMultiParcelShippingCost($expedition);
        $summary['parcel_details'] = $expedition->getParcelDetails();

        return $summary;
    }

    /**
     * Validate expedition data using new strict validation.
     */
    private function validateExpeditionData(array $expeditionData): void
    {
        $errors = [];

        // Validate delivery mode
        if (!empty($expeditionData['delivery_mode'])) {
            $errors = array_merge($errors, MondialRelayValidator::validateDeliveryMode($expeditionData['delivery_mode']));
        } else {
            $errors[] = 'Mode de livraison requis';
        }

        // Validate weight
        if (!empty($expeditionData['weight'])) {
            $errors = array_merge($errors, MondialRelayValidator::validateWeight($expeditionData['weight']));
        } else {
            $errors[] = 'Poids requis';
        }

        // Validate sender address
        if (!empty($expeditionData['sender'])) {
            $senderErrors = MondialRelayValidator::validateAddress($expeditionData['sender'], false);
            foreach ($senderErrors as $error) {
                $errors[] = "Expéditeur: {$error}";
            }
        } else {
            $errors[] = 'Adresse expéditeur requise';
        }

        // Validate recipient address
        if (!empty($expeditionData['recipient'])) {
            $recipientErrors = MondialRelayValidator::validateAddress($expeditionData['recipient'], true);
            foreach ($recipientErrors as $error) {
                $errors[] = "Destinataire: {$error}";
            }
        } else {
            $errors[] = 'Adresse destinataire requise';
        }

        // Validate relay point for relay delivery modes
        if (!empty($expeditionData['delivery_mode']) && MondialRelayHelper::requiresRelayPoint($expeditionData['delivery_mode'])) {
            if (!empty($expeditionData['relay_number'])) {
                $errors = array_merge($errors, MondialRelayValidator::validateRelayNumber($expeditionData['relay_number']));
            } else {
                $errors[] = 'Numéro de point relais requis pour ce mode de livraison';
            }

            if (!empty($expeditionData['relay_country'])) {
                $errors = array_merge($errors, MondialRelayValidator::validateCountryCode($expeditionData['relay_country']));
            } else {
                $errors[] = 'Pays du point relais requis pour ce mode de livraison';
            }
        }

        if (!empty($errors)) {
            Log::error('Validation errors: ' . json_encode($errors));
            throw MondialRelayException::validation('Données d\'expédition invalides', $errors);
        }
    }
}
