<?php

namespace Bmwsly\MondialRelayApi\Clients;

use Bmwsly\MondialRelayApi\Debug\MondialRelayDebugger;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use DOMDocument;
use SimpleXMLElement;

class MondialRelayRestClient
{
    private string $apiUrl;
    private string $user;
    private string $password;
    private string $customerId;
    private ?MondialRelayDebugger $debugger;

    public function __construct(
        string $apiUrl,
        string $user,
        string $password,
        string $customerId,
        ?MondialRelayDebugger $debugger = null
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->user = $user;
        $this->password = $password;
        $this->customerId = $customerId;
        $this->debugger = $debugger;
    }

    /**
     * Create shipment using API V2.
     */
    public function createShipment(array $shipmentData): array
    {
        $xml = $this->buildShipmentXml($shipmentData);
        $response = $this->callRestApi('/shipment', $xml);

        return $this->parseShipmentResponse($response);
    }

    /**
     * Build XML for shipment creation.
     */
    private function buildShipmentXml(array $shipmentData): string
    {
        $xml = new DOMDocument('1.0', 'utf-8');

        // Root element
        $shipmentRequest = $xml->createElement('ShipmentCreationRequest');
        $shipmentRequest->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $shipmentRequest->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $shipmentRequest->setAttribute('xmlns', 'http://www.example.org/Request');

        // Context
        $context = $xml->createElement('Context');
        $context->appendChild($xml->createElement('Login', $this->user));
        $context->appendChild($xml->createElement('Password', $this->password));
        $context->appendChild($xml->createElement('CustomerId', $this->customerId));
        $context->appendChild($xml->createElement('Culture', 'fr-FR'));
        $context->appendChild($xml->createElement('VersionAPI', '1.0'));
        $shipmentRequest->appendChild($context);

        // Output options
        $outputOptions = $xml->createElement('OutputOptions');
        $outputOptions->appendChild($xml->createElement('OutputFormat', $shipmentData['output_format'] ?? '10x15'));
        $outputOptions->appendChild($xml->createElement('OutputType', $shipmentData['output_type'] ?? 'PdfUrl'));
        $shipmentRequest->appendChild($outputOptions);

        // Shipments list
        $shipmentsList = $xml->createElement('ShipmentsList');
        $shipment = $xml->createElement('Shipment');

        // Basic shipment info
        $shipment->appendChild($xml->createElement('OrderNo', $shipmentData['order_number'] ?? ''));
        $shipment->appendChild($xml->createElement('CustomerNo', $shipmentData['customer_reference'] ?? ''));
        $shipment->appendChild($xml->createElement('ParcelCount', count($shipmentData['parcels'] ?? [1])));
        $shipment->appendChild($xml->createElement('DeliveryInstruction', $shipmentData['delivery_instruction'] ?? ''));

        // Delivery mode
        $deliveryMode = $xml->createElement('DeliveryMode');
        $deliveryMode->setAttribute('Mode', $shipmentData['delivery_mode'] ?? '24R');
        if (!empty($shipmentData['relay_number'])) {
            $deliveryMode->setAttribute('Location', ($shipmentData['relay_country'] ?? 'FR').$shipmentData['relay_number']);
        }
        $shipment->appendChild($deliveryMode);

        // Collection mode
        $collectionMode = $xml->createElement('CollectionMode');
        $collectionMode->setAttribute('Mode', $shipmentData['collection_mode'] ?? 'CCC');
        $shipment->appendChild($collectionMode);

        // Options
        if (!empty($shipmentData['cod_amount'])) {
            $shipment->appendChild($this->buildOptionNode('CRT', $shipmentData['cod_amount'], $xml));
        }
        if (!empty($shipmentData['insurance_level'])) {
            $shipment->appendChild($this->buildOptionNode('ASS', $shipmentData['insurance_level'], $xml));
        }

        $shipment->appendChild($this->buildOptionNode('LNG', $shipmentData['recipient']['language'] ?? 'FR', $xml));

        // Parcels
        $parcels = $xml->createElement('Parcels');
        foreach ($shipmentData['parcels'] ?? [['weight' => $shipmentData['weight'], 'content' => 'Produit e-commerce']] as $parcel) {
            $parcels->appendChild($this->buildParcelNode($parcel, $xml));
        }
        $shipment->appendChild($parcels);

        // Addresses
        $shipment->appendChild($this->buildAddressNode('Sender', $shipmentData['sender'], $xml));
        $shipment->appendChild($this->buildAddressNode('Recipient', $shipmentData['recipient'], $xml));

        $shipmentsList->appendChild($shipment);
        $shipmentRequest->appendChild($shipmentsList);
        $xml->appendChild($shipmentRequest);

        return $xml->saveXML();
    }

    /**
     * Build option node.
     */
    private function buildOptionNode(string $key, string $value, DOMDocument $xml): \DOMElement
    {
        $option = $xml->createElement('Option');
        $option->setAttribute('Key', $key);
        $option->setAttribute('Value', $value);

        return $option;
    }

    /**
     * Build parcel node.
     */
    private function buildParcelNode(array $parcel, DOMDocument $xml): \DOMElement
    {
        $parcelNode = $xml->createElement('Parcel');
        $parcelNode->appendChild($xml->createElement('Content', $parcel['content'] ?? 'Produit e-commerce'));

        $weight = $xml->createElement('Weight');
        $weight->setAttribute('Value', (string) ($parcel['weight'] ?? 1000));
        $weight->setAttribute('Unit', 'gr');
        $parcelNode->appendChild($weight);

        return $parcelNode;
    }

    /**
     * Build address node.
     */
    private function buildAddressNode(string $type, array $address, DOMDocument $xml): \DOMElement
    {
        $addressNode = $xml->createElement($type);
        $addressElement = $xml->createElement('Address');

        $addressElement->appendChild($xml->createElement('Title', ''));
        $addressElement->appendChild($xml->createElement('Firstname', $address['line1'] ?? ''));
        $addressElement->appendChild($xml->createElement('Lastname', ''));
        $addressElement->appendChild($xml->createElement('Streetname', $address['line3'] ?? ''));
        $addressElement->appendChild($xml->createElement('HouseNo', ''));
        $addressElement->appendChild($xml->createElement('CountryCode', $address['country'] ?? 'FR'));
        $addressElement->appendChild($xml->createElement('PostCode', $address['postal_code'] ?? ''));
        $addressElement->appendChild($xml->createElement('City', $address['city'] ?? ''));
        $addressElement->appendChild($xml->createElement('AddressAdd1', $address['line2'] ?? ''));
        $addressElement->appendChild($xml->createElement('AddressAdd2', ''));
        $addressElement->appendChild($xml->createElement('AddressAdd3', $address['line4'] ?? ''));
        $addressElement->appendChild($xml->createElement('PhoneNo', $address['phone'] ?? ''));
        $addressElement->appendChild($xml->createElement('MobileNo', $address['phone2'] ?? ''));
        $addressElement->appendChild($xml->createElement('Email', $address['email'] ?? ''));

        $addressNode->appendChild($addressElement);

        return $addressNode;
    }

    /**
     * Call REST API.
     */
    private function callRestApi(string $endpoint, string $xmlData): SimpleXMLElement
    {
        $url = $this->apiUrl.$endpoint;

        // Log request if debugger is available
        if ($this->debugger) {
            $this->debugger->logRestRequest('POST', $url, $xmlData);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: text/xml',
                    'Accept: application/xml',
                ],
                'content' => $xmlData,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $error = 'Failed to call REST API';
            if ($this->debugger) {
                $this->debugger->logError($error, ['url' => $url, 'xml_data' => $xmlData]);
            }
            throw new MondialRelayException($error, 99);
        }

        // Log response if debugger is available
        if ($this->debugger) {
            $this->debugger->logRestResponse('POST', $url, $response);
        }

        return new SimpleXMLElement($response);
    }

    /**
     * Parse shipment creation response.
     */
    private function parseShipmentResponse(SimpleXMLElement $response): array
    {
        $result = [
            'success' => false,
            'messages' => [],
            'shipment_number' => null,
            'tracking_link' => null,
            'label_link' => null,
            'parcels' => [],
        ];

        // Check for errors
        if (isset($response->StatusList->Status) && count($response->StatusList->Status) > 0) {
            foreach ($response->StatusList->Status as $status) {
                $result['messages'][] = [
                    'message' => (string) $status->attributes()->Message,
                    'code' => (string) $status->attributes()->Code,
                    'severity' => (string) $status->attributes()->Level,
                ];
            }

            return $result;
        }

        // Success case
        $result['success'] = true;

        if (isset($response->ShipmentsList->Shipment[0]->LabelList->Label[0])) {
            $label = $response->ShipmentsList->Shipment[0]->LabelList->Label[0];

            // Extract shipment number
            if (isset($label->RawContent->LabelValues)) {
                foreach ($label->RawContent->LabelValues as $labelValue) {
                    if ((string) $labelValue->attributes()->Key === 'MR.Expedition.NumeroExpedition') {
                        $result['shipment_number'] = (string) $labelValue->attributes()->Value;
                        break;
                    }
                }
            }

            // Label URL
            $result['label_link'] = (string) $label->Output;
        }

        // Extract parcel barcodes
        if (isset($response->ShipmentsList->Shipment[0]->LabelList->Barcodes)) {
            foreach ($response->ShipmentsList->Shipment[0]->LabelList->Barcodes as $barcode) {
                if (isset($barcode->Barcodes->Barcode)) {
                    $result['parcels'][] = [
                        'cab' => (string) $barcode->Barcodes->Barcode->attributes()->Value,
                    ];
                }
            }
        }

        return $result;
    }
}
