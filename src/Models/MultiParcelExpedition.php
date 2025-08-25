<?php

namespace Bmwsly\MondialRelayApi\Models;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;

class MultiParcelExpedition
{
    /** @var Parcel[] */
    private array $parcels = [];

    public function __construct(
        private array $sender,
        private array $recipient,
        private string $deliveryMode = '24R',
        private ?string $relayNumber = null,
        private ?string $relayCountry = null,
        private ?string $orderNumber = null,
        private ?string $customerReference = null,
        private ?string $deliveryInstruction = null,
        private float $codAmount = 0,
        private string $codCurrency = 'EUR',
        private string $insuranceLevel = '0'
    ) {
    }

    /**
     * Add a parcel to the expedition.
     */
    public function addParcel(Parcel $parcel): self
    {
        $this->parcels[] = $parcel;

        return $this;
    }

    /**
     * Add multiple parcels.
     */
    public function addParcels(array $parcels): self
    {
        foreach ($parcels as $parcel) {
            if (is_array($parcel)) {
                $parcel = Parcel::fromArray($parcel);
            }

            if (!$parcel instanceof Parcel) {
                throw new MondialRelayException('Invalid parcel data');
            }

            $this->addParcel($parcel);
        }

        return $this;
    }

    /**
     * Remove a parcel by index.
     */
    public function removeParcel(int $index): self
    {
        if (isset($this->parcels[$index])) {
            unset($this->parcels[$index]);
            $this->parcels = array_values($this->parcels); // Re-index
        }

        return $this;
    }

    /**
     * Get all parcels.
     */
    public function getParcels(): array
    {
        return $this->parcels;
    }

    /**
     * Get parcel count.
     */
    public function getParcelCount(): int
    {
        return count($this->parcels);
    }

    /**
     * Get total weight of all parcels.
     */
    public function getTotalWeight(): int
    {
        return array_sum(array_map(fn (Parcel $p) => $p->weightInGrams, $this->parcels));
    }

    /**
     * Get total billable weight of all parcels.
     */
    public function getTotalBillableWeight(): int
    {
        return array_sum(array_map(fn (Parcel $p) => $p->getBillableWeightInGrams(), $this->parcels));
    }

    /**
     * Get total value of all parcels.
     */
    public function getTotalValue(): float
    {
        return array_sum(array_map(fn (Parcel $p) => $p->value ?? 0, $this->parcels));
    }

    /**
     * Get formatted total weight.
     */
    public function getFormattedTotalWeight(): string
    {
        $weight = $this->getTotalWeight();

        if ($weight < 1000) {
            return $weight.'g';
        }

        return number_format($weight / 1000, 2).'kg';
    }

    /**
     * Validate the expedition.
     */
    public function validate(): array
    {
        $errors = [];

        // Check if we have parcels
        if (empty($this->parcels)) {
            $errors[] = 'Au moins un colis est requis';

            return $errors;
        }

        // Check parcel count limits
        if (count($this->parcels) > 10) {
            $errors[] = 'Maximum 10 colis par expédition';
        }

        // Validate each parcel
        foreach ($this->parcels as $index => $parcel) {
            $parcelErrors = $parcel->validate();
            foreach ($parcelErrors as $error) {
                $errors[] = 'Colis '.($index + 1).": {$error}";
            }
        }

        // Check total weight
        $totalWeight = $this->getTotalWeight();
        if ($totalWeight > 30000) {
            $errors[] = 'Poids total maximum dépassé (30kg)';
        }

        // Check delivery mode compatibility with multi-parcel
        if (count($this->parcels) > 1 && in_array($this->deliveryMode, ['24R', '24X'])) {
            $errors[] = 'Les expéditions multi-colis ne sont pas autorisées en Point Relais';
        }

        // Validate addresses
        if (empty($this->sender)) {
            $errors[] = 'Adresse expéditeur requise';
        }

        if (empty($this->recipient)) {
            $errors[] = 'Adresse destinataire requise';
        }

        // Validate relay point if required
        if (in_array($this->deliveryMode, ['24R', '24X']) && empty($this->relayNumber)) {
            $errors[] = 'Numéro de point relais requis pour ce mode de livraison';
        }

        return $errors;
    }

    /**
     * Check if expedition is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Convert to array for API calls.
     */
    public function toArray(): array
    {
        return [
            'delivery_mode' => $this->deliveryMode,
            'relay_number' => $this->relayNumber,
            'relay_country' => $this->relayCountry ?? 'FR',
            'order_number' => $this->orderNumber,
            'customer_reference' => $this->customerReference,
            'delivery_instruction' => $this->deliveryInstruction,
            'cod_amount' => $this->codAmount,
            'cod_currency' => $this->codCurrency,
            'insurance_level' => $this->insuranceLevel,
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'parcels' => array_map(fn (Parcel $p) => $p->toArray(), $this->parcels),
            'total_weight' => $this->getTotalWeight(),
            'parcel_count' => $this->getParcelCount(),
        ];
    }

    /**
     * Create expedition from array.
     */
    public static function fromArray(array $data): self
    {
        $expedition = new self(
            sender: $data['sender'] ?? [],
            recipient: $data['recipient'] ?? [],
            deliveryMode: $data['delivery_mode'] ?? '24R',
            relayNumber: $data['relay_number'] ?? null,
            relayCountry: $data['relay_country'] ?? null,
            orderNumber: $data['order_number'] ?? null,
            customerReference: $data['customer_reference'] ?? null,
            deliveryInstruction: $data['delivery_instruction'] ?? null,
            codAmount: $data['cod_amount'] ?? 0,
            codCurrency: $data['cod_currency'] ?? 'EUR',
            insuranceLevel: $data['insurance_level'] ?? '0'
        );

        if (!empty($data['parcels'])) {
            $expedition->addParcels($data['parcels']);
        }

        return $expedition;
    }

    /**
     * Get expedition summary.
     */
    public function getSummary(): array
    {
        return [
            'parcel_count' => $this->getParcelCount(),
            'total_weight' => $this->getFormattedTotalWeight(),
            'total_value' => $this->getTotalValue(),
            'delivery_mode' => $this->deliveryMode,
            'relay_number' => $this->relayNumber,
            'order_number' => $this->orderNumber,
            'is_valid' => $this->isValid(),
        ];
    }

    /**
     * Get detailed parcel information.
     */
    public function getParcelDetails(): array
    {
        return array_map(function (Parcel $parcel, int $index) {
            return [
                'index' => $index + 1,
                'weight' => $parcel->getFormattedWeight(),
                'content' => $parcel->content,
                'dimensions' => $parcel->getDimensionsString(),
                'value' => $parcel->value,
                'billable_weight' => $parcel->getBillableWeightInGrams(),
            ];
        }, $this->parcels, array_keys($this->parcels));
    }

    // Getters and setters
    public function getSender(): array
    {
        return $this->sender;
    }

    public function setSender(array $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipient(): array
    {
        return $this->recipient;
    }

    public function setRecipient(array $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getDeliveryMode(): string
    {
        return $this->deliveryMode;
    }

    public function setDeliveryMode(string $deliveryMode): self
    {
        $this->deliveryMode = $deliveryMode;

        return $this;
    }

    public function getRelayNumber(): ?string
    {
        return $this->relayNumber;
    }

    public function setRelayNumber(?string $relayNumber): self
    {
        $this->relayNumber = $relayNumber;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getDeliveryInstruction(): ?string
    {
        return $this->deliveryInstruction;
    }

    public function setDeliveryInstruction(?string $instruction): self
    {
        $this->deliveryInstruction = $instruction;

        return $this;
    }

    public function getCodAmount(): float
    {
        return $this->codAmount;
    }

    public function setCodAmount(float $amount): self
    {
        $this->codAmount = $amount;

        return $this;
    }

    public function getInsuranceLevel(): string
    {
        return $this->insuranceLevel;
    }

    public function setInsuranceLevel(string $level): self
    {
        $this->insuranceLevel = $level;

        return $this;
    }
}
