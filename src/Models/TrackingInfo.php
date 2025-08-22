<?php

namespace Bmwsly\MondialRelayApi\Models;

class TrackingInfo
{
    public function __construct(
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $relayName,
        public readonly string $relayNumber,
        /** @var TrackingEvent[] */
        public readonly array $trackingEvents,
    ) {
    }

    public static function fromApiResponse(object $response): self
    {
        $events = [];
        if (isset($response->Tracing) && is_array($response->Tracing)) {
            $events = array_map(
                fn ($event) => TrackingEvent::fromApiResponse($event),
                $response->Tracing
            );
        }

        return new self(
            status: $response->STAT,
            statusLabel: $response->Libelle01 ?? '',
            relayName: $response->Relais_Libelle ?? '',
            relayNumber: $response->Relais_Num ?? '',
            trackingEvents: $events,
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'relay_name' => $this->relayName,
            'relay_number' => $this->relayNumber,
            'tracking_events' => array_map(fn ($event) => $event->toArray(), $this->trackingEvents),
        ];
    }

    public function isDelivered(): bool
    {
        return in_array($this->status, ['80', '81', '82', '83']) ||
               !empty($this->trackingEvents) && $this->trackingEvents[0]->isDelivered();
    }

    public function isInTransit(): bool
    {
        return $this->status === '0' && !$this->isDelivered();
    }

    public function getLatestEvent(): ?TrackingEvent
    {
        return $this->trackingEvents[0] ?? null;
    }

    public function getDeliveryEvent(): ?TrackingEvent
    {
        foreach ($this->trackingEvents as $event) {
            if ($event->isDelivered()) {
                return $event;
            }
        }

        return null;
    }

    public function getStatusMessage(): string
    {
        $statusMessages = [
            '0' => 'Colis en cours de traitement',
            '80' => 'Colis livré',
            '81' => 'Colis livré avec signature',
            '82' => 'Colis livré sans signature',
            '83' => 'Colis livré avec anomalie',
        ];

        return $statusMessages[$this->status] ?? $this->statusLabel;
    }

    public function hasRelay(): bool
    {
        return !empty($this->relayNumber);
    }

    public function getEventsCount(): int
    {
        return count($this->trackingEvents);
    }
}
