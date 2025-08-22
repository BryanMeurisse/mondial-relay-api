<?php

namespace Bmwsly\MondialRelayApi\Services;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use Bmwsly\MondialRelayApi\Models\Expedition;
use Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel;
use Bmwsly\MondialRelayApi\Models\LabelBatch;
use Bmwsly\MondialRelayApi\Models\RelayPoint;
use Bmwsly\MondialRelayApi\Models\TrackingInfo;
use Bmwsly\MondialRelayApi\MondialRelayClient;

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
        if (!MondialRelayHelper::isValidFrenchPostalCode($postalCode)) {
            throw new MondialRelayException('Code postal invalide');
        }

        if (!MondialRelayHelper::isValidCountryCode($country)) {
            throw new MondialRelayException('Code pays invalide');
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

        // Validate before sending
        $errors = MondialRelayHelper::validateExpeditionParams($expeditionData);
        if (!empty($errors)) {
            throw new MondialRelayException('Données invalides: '.implode(', ', $errors));
        }

        return $this->client->createExpedition($expeditionData);
    }

    /**
     * Create a home delivery expedition.
     */
    public function createHomeDeliveryExpedition(
        array $sender,
        array $recipient,
        int $weightInGrams,
        string $deliveryMode = '24L',
        ?string $orderNumber = null
    ): Expedition {
        $expeditionData = [
            'delivery_mode' => $deliveryMode,
            'weight' => $weightInGrams,
            'order_number' => $orderNumber,
            'sender' => $this->formatSenderData($sender),
            'recipient' => $this->formatRecipientData($recipient),
        ];

        // Validate before sending
        $errors = MondialRelayHelper::validateExpeditionParams($expeditionData);
        if (!empty($errors)) {
            throw new MondialRelayException('Données invalides: '.implode(', ', $errors));
        }

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
            'name' => MondialRelayHelper::formatAddress($sender['name']),
            'company' => MondialRelayHelper::formatAddress($sender['company'] ?? ''),
            'address' => MondialRelayHelper::formatAddress($sender['address']),
            'address_complement' => MondialRelayHelper::formatAddress($sender['address_complement'] ?? ''),
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
            'name' => MondialRelayHelper::formatAddress($recipient['name']),
            'company' => MondialRelayHelper::formatAddress($recipient['company'] ?? ''),
            'address' => MondialRelayHelper::formatAddress($recipient['address']),
            'address_complement' => MondialRelayHelper::formatAddress($recipient['address_complement'] ?? ''),
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

        if ($articlesDescription) {
            $expeditionData['articles_description'] = $articlesDescription;
        }

        // Validate before sending
        $errors = MondialRelayHelper::validateExpeditionParams($expeditionData);
        if (!empty($errors)) {
            throw new MondialRelayException('Données invalides: '.implode(', ', $errors));
        }

        return $this->client->createExpeditionWithLabel($expeditionData);
    }

    /**
     * Create a home delivery expedition with PDF label.
     */
    public function createHomeDeliveryExpeditionWithLabel(
        array $sender,
        array $recipient,
        int $weightInGrams,
        string $deliveryMode = '24L',
        ?string $orderNumber = null,
        ?string $articlesDescription = null
    ): ExpeditionWithLabel {
        $expeditionData = [
            'delivery_mode' => $deliveryMode,
            'weight' => $weightInGrams,
            'order_number' => $orderNumber,
            'sender' => $this->formatSenderData($sender),
            'recipient' => $this->formatRecipientData($recipient),
        ];

        if ($articlesDescription) {
            $expeditionData['articles_description'] = $articlesDescription;
        }

        // Validate before sending
        $errors = MondialRelayHelper::validateExpeditionParams($expeditionData);
        if (!empty($errors)) {
            throw new MondialRelayException('Données invalides: '.implode(', ', $errors));
        }

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
}
