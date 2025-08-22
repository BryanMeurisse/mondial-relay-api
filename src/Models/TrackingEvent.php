<?php

namespace Bmwsly\MondialRelayApi\Models;

use Carbon\Carbon;

class TrackingEvent
{
    public function __construct(
        public readonly string $label,
        public readonly string $date,
        public readonly string $time,
        public readonly string $location,
        public readonly string $relayNumber,
        public readonly string $country,
    ) {
    }

    public static function fromApiResponse(object $event): self
    {
        return new self(
            label: $event->Tracing_Libelle ?? '',
            date: $event->Tracing_Date ?? '',
            time: $event->Tracing_Heure ?? '',
            location: $event->Tracing_Lieu ?? '',
            relayNumber: $event->Tracing_Relais ?? '',
            country: $event->Tracing_Pays ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'date' => $this->date,
            'time' => $this->time,
            'location' => $this->location,
            'relay_number' => $this->relayNumber,
            'country' => $this->country,
            'datetime' => $this->getDateTime()?->toISOString(),
        ];
    }

    public function getDateTime(): ?Carbon
    {
        if (empty($this->date) || empty($this->time)) {
            return null;
        }

        try {
            // Format attendu: DDMMYYYY pour la date et HHMM pour l'heure
            $dateTime = Carbon::createFromFormat('dmY Hi', $this->date.' '.$this->time);

            return $dateTime;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getFormattedDateTime(): ?string
    {
        $dateTime = $this->getDateTime();

        return $dateTime ? $dateTime->format('d/m/Y H:i') : null;
    }

    public function isDelivered(): bool
    {
        $deliveredKeywords = ['livré', 'delivered', 'remis', 'distribué'];

        foreach ($deliveredKeywords as $keyword) {
            if (stripos($this->label, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public function isInTransit(): bool
    {
        $transitKeywords = ['transit', 'acheminement', 'transport', 'expédié'];

        foreach ($transitKeywords as $keyword) {
            if (stripos($this->label, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public function isAtRelay(): bool
    {
        $relayKeywords = ['point relais', 'relay', 'disponible', 'arrivé'];

        foreach ($relayKeywords as $keyword) {
            if (stripos($this->label, $keyword) !== false) {
                return true;
            }
        }

        return !empty($this->relayNumber);
    }
}
