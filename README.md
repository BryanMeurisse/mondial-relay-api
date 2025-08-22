# Laravel Mondial Relay API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bmwsly/mondial-relay-api.svg?style=flat-square)](https://packagist.org/packages/bmwsly/mondial-relay-api)
[![Total Downloads](https://img.shields.io/packagist/dt/bmwsly/mondial-relay-api.svg?style=flat-square)](https://packagist.org/packages/bmwsly/mondial-relay-api)

Un package Laravel pour intégrer facilement l'API Mondial Relay dans vos applications e-commerce. Ce package se concentre sur les fonctionnalités essentielles : recherche de points relais, création d'expéditions et suivi de colis.

## Fonctionnalités

- **Recherche de points relais** - Trouvez les points relais les plus proches
- **Création d'expéditions** - Créez vos expéditions Mondial Relay
- **Génération d'étiquettes PDF** - Créez des expéditions avec étiquettes PDF intégrées
- **Gestion des étiquettes en lot** - Récupérez plusieurs étiquettes en un seul PDF
- **Suivi de colis** - Suivez vos colis en temps réel
- **Sécurisé** - Génération automatique des clés de sécurité MD5
- **Validation** - Validation automatique des paramètres
- **Multi-formats** - Support des formats A4, A5 et 10x15 pour les étiquettes

## Installation

Installez le package via Composer :

```bash
composer require bmwsly/mondial-relay-api
```

Le package sera automatiquement découvert par Laravel grâce à l'auto-discovery.

## Configuration

Publiez le fichier de configuration :

```bash
php artisan vendor:publish --provider="Bmwsly\MondialRelayApi\MondialRelayServiceProvider" --tag="config"
```

Ajoutez vos identifiants Mondial Relay dans votre fichier `.env` :

```env
MONDIAL_RELAY_ENSEIGNE=VOTRE_ENSEIGNE
MONDIAL_RELAY_PRIVATE_KEY=VOTRE_CLE_PRIVEE
MONDIAL_RELAY_TEST_MODE=true
MONDIAL_RELAY_API_URL=https://api.mondialrelay.com/Web_Services.asmx
```

## Utilisation

Le package offre deux façons d'interagir avec l'API Mondial Relay :

1. **Client bas niveau** (`MondialRelay` facade) - Accès direct aux méthodes API
2. **Service haut niveau** (`MondialRelayService` facade) - Interface simplifiée avec validation et formatage automatiques

### Recherche de points relais

#### Avec le service haut niveau (recommandé)

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Recherche simple
$relayPoints = MondialRelayService::findNearestRelayPoints('75001');

// Recherche pour un envoi spécifique
$relayPoints = MondialRelayService::findRelayPointsForShipment(
    postalCode: '75001',
    weightInGrams: 1000,
    deliveryMode: '24R',
    country: 'FR',
    maxResults: 10
);

foreach ($relayPoints as $relay) {
    echo $relay->name . ' - ' . $relay->getFullAddress() . "\n";
    echo 'Distance: ' . $relay->distance . ' km' . "\n";
    echo 'Numéro: ' . $relay->number . "\n";
    echo 'Ouvert aujourd\'hui: ' . ($relay->isOpenToday() ? 'Oui' : 'Non') . "\n\n";
}
```

#### Avec le client bas niveau

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelay;

// Recherche basique par code postal
$relayPoints = MondialRelay::searchRelayPoints([
    'postal_code' => '75001',
]);

// Recherche avancée
$relayPoints = MondialRelay::searchRelayPoints([
    'postal_code' => '75001',
    'country' => 'FR',
    'weight' => 1000, // en grammes
    'delivery_mode' => '24R',
    'search_radius' => 20, // en km
    'max_results' => 10,
]);

foreach ($relayPoints as $relay) {
    echo $relay->name . ' - ' . $relay->getFullAddress() . "\n";
    echo 'Distance: ' . $relay->distance . ' km' . "\n";
    echo 'Numéro: ' . $relay->number . "\n\n";
}
```

### Création d'une expédition

#### Avec le service haut niveau (recommandé)

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Expédition vers un point relais
$expedition = MondialRelayService::createRelayExpedition(
    sender: [
        'name' => 'Mon E-commerce',
        'company' => 'Ma Société SARL',
        'address' => '123 Rue du Commerce',
        'city' => 'Paris',
        'postal_code' => '75001',
        'country' => 'FR',
        'phone' => '01 23 45 67 89',
        'email' => 'contact@mon-ecommerce.fr',
    ],
    recipient: [
        'name' => 'Jean Dupont',
        'address' => '456 Avenue de la Paix',
        'city' => 'Lyon',
        'postal_code' => '69001',
        'country' => 'FR',
        'phone' => '09 87 65 43 21',
        'email' => 'jean.dupont@email.fr',
    ],
    relayNumber: '123456',
    weightInGrams: 1000,
    deliveryMode: '24R',
    orderNumber: 'CMD-2024-001'
);

// Expédition à domicile
$expedition = MondialRelayService::createHomeDeliveryExpedition(
    sender: $senderData,
    recipient: $recipientData,
    weightInGrams: 1000,
    deliveryMode: '24L',
    orderNumber: 'CMD-2024-002'
);

echo "Numéro d'expédition: " . $expedition->expeditionNumber . "\n";
echo "URL de suivi: " . $expedition->getTrackingUrl() . "\n";
echo "Mode de livraison: " . $expedition->getDeliveryModeLabel() . "\n";
```

#### Avec le client bas niveau

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelay;

$expedition = MondialRelay::createExpedition([
    'delivery_mode' => '24R', // Livraison en point relais
    'weight' => 1000, // Poids en grammes
    'order_number' => 'CMD-2024-001',
    'customer_id' => 'CLIENT-123',

    // Informations expéditeur
    'sender' => [
        'name' => 'Mon E-commerce',
        'company' => 'Ma Société SARL',
        'address' => '123 Rue du Commerce',
        'city' => 'Paris',
        'postal_code' => '75001',
        'country' => 'FR',
        'phone' => '0123456789',
        'email' => 'contact@mon-ecommerce.fr',
    ],

    // Informations destinataire
    'recipient' => [
        'name' => 'Jean Dupont',
        'address' => '456 Avenue de la Paix',
        'city' => 'Lyon',
        'postal_code' => '69001',
        'country' => 'FR',
        'phone' => '0987654321',
        'email' => 'jean.dupont@email.fr',
    ],

    // Point relais (requis pour les modes 24R, 24L, 24X)
    'relay_number' => '123456',
    'relay_country' => 'FR',

    // Optionnel
    'declared_value' => 50.00,
    'instructions' => 'Fragile - Manipuler avec précaution',
]);

echo "Numéro d'expédition: " . $expedition->expeditionNumber;
```

### Création d'expédition avec étiquette PDF

#### Avec le service haut niveau (recommandé)

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Expédition vers un point relais avec étiquette PDF
$expeditionWithLabel = MondialRelayService::createExpeditionWithLabel(
    sender: $senderData,
    recipient: $recipientData,
    relayNumber: '123456',
    weightInGrams: 1000,
    deliveryMode: '24R',
    orderNumber: 'CMD-2024-001',
    articlesDescription: 'Vêtements - 2 articles'
);

// Expédition à domicile avec étiquette PDF
$expeditionWithLabel = MondialRelayService::createHomeDeliveryExpeditionWithLabel(
    sender: $senderData,
    recipient: $recipientData,
    weightInGrams: 1000,
    deliveryMode: '24L',
    orderNumber: 'CMD-2024-002',
    articlesDescription: 'Électronique - 1 article'
);

echo "Numéro d'expédition: " . $expeditionWithLabel->expeditionNumber . "\n";
echo "URL étiquette A4: " . $expeditionWithLabel->getLabelUrl('A4') . "\n";
echo "URL étiquette A5: " . $expeditionWithLabel->getLabelUrl('A5') . "\n";
echo "URL étiquette 10x15: " . $expeditionWithLabel->getLabelUrl('10x15') . "\n";

// Télécharger l'étiquette PDF
$pdfContent = $expeditionWithLabel->downloadLabel('A4');
file_put_contents('etiquette.pdf', $pdfContent);
```

#### Avec le client bas niveau

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelay;

$expeditionWithLabel = MondialRelay::createExpeditionWithLabel([
    'delivery_mode' => '24R',
    'weight' => 1000,
    'order_number' => 'CMD-2024-001',
    'articles_description' => 'Vêtements - 2 articles',
    'sender' => $senderData,
    'recipient' => $recipientData,
    'relay_number' => '123456',
    'relay_country' => 'FR',
]);

echo "Numéro d'expédition: " . $expeditionWithLabel->expeditionNumber;
echo "URL étiquette A4: " . $expeditionWithLabel->getLabelUrl('A4');
```

### Gestion des étiquettes en lot

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Récupérer les étiquettes pour plusieurs expéditions
$expeditionNumbers = ['12345678901234', '56789012345678', '90123456789012'];
$labelBatch = MondialRelayService::getLabelsForExpeditions($expeditionNumbers);

echo "Nombre d'expéditions: " . $labelBatch->getExpeditionCount() . "\n";
echo "PDF A4: " . $labelBatch->getPdfUrlByFormat('A4') . "\n";
echo "PDF A5: " . $labelBatch->getPdfUrlByFormat('A5') . "\n";
echo "PDF 10x15: " . $labelBatch->getPdfUrlByFormat('10x15') . "\n";

// Vérifier si une expédition est dans le lot
if ($labelBatch->containsExpedition('12345678901234')) {
    echo "L'expédition 12345678901234 est incluse dans le lot\n";
}

// Télécharger le PDF du lot
$batchPdfContent = MondialRelayService::downloadBatchLabels($labelBatch, 'A4');
file_put_contents('etiquettes_lot.pdf', $batchPdfContent);

// Ou directement avec l'URL
$pdfContent = MondialRelayService::downloadLabelPdf($labelBatch->getPdfUrlByFormat('A4'));
file_put_contents('etiquettes_lot_direct.pdf', $pdfContent);
```

### Suivi de colis

#### Avec le service haut niveau (recommandé)

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Suivi simple
$isDelivered = MondialRelayService::isPackageDelivered('12345678901234');

// Résumé du statut
$summary = MondialRelayService::getPackageStatusSummary('12345678901234');
echo "Statut: " . $summary['status'] . "\n";
echo "Livré: " . ($summary['is_delivered'] ? 'Oui' : 'Non') . "\n";
echo "URL de suivi: " . $summary['tracking_url'] . "\n";

if ($summary['latest_event']) {
    echo "Dernier événement: " . $summary['latest_event']['label'] . "\n";
    echo "Date: " . $summary['latest_event']['date'] . "\n";
}

// Suivi détaillé
$tracking = MondialRelayService::getTrackingInfo('12345678901234');
echo "Statut: " . $tracking->getStatusMessage() . "\n";
echo "Point relais: " . $tracking->relayName . "\n";
echo "Livré: " . ($tracking->isDelivered() ? 'Oui' : 'Non') . "\n";

foreach ($tracking->trackingEvents as $event) {
    echo $event->getFormattedDateTime() . ' - ' . $event->label . "\n";
    if ($event->location) {
        echo "  Lieu: " . $event->location . "\n";
    }
}
```

#### Avec le client bas niveau

```php
use Bmwsly\MondialRelayApi\Facades\MondialRelay;

$tracking = MondialRelay::trackPackage('12345678901234');

echo "Statut: " . $tracking->statusLabel . "\n";
echo "Point relais: " . $tracking->relayName . "\n";

foreach ($tracking->trackingEvents as $event) {
    echo $event->getFormattedDateTime() . ' - ' . $event->label . "\n";
}
```

### Utilitaires et helpers

```php
use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Calculer les frais de port
$cost = MondialRelayService::calculateShippingCost(1000, '24R'); // 4.90€

// Obtenir les modes de livraison disponibles
$modes = MondialRelayService::getAvailableDeliveryModes();

// Valider un code postal français
$isValid = MondialRelayHelper::isValidFrenchPostalCode('75001'); // true

// Valider un numéro de point relais
$isValid = MondialRelayHelper::isValidRelayNumber('123456'); // true

// Formater un poids
$formatted = MondialRelayHelper::formatWeight(1500); // "1.50kg"

// Formater une distance
$formatted = MondialRelayHelper::formatDistance(2.5); // "2.5km"

// Obtenir l'URL de suivi
$url = MondialRelayHelper::getTrackingUrl('12345678901234');
```

## Formats d'étiquettes

Le package supporte trois formats d'étiquettes PDF :

- **A4** : Format standard A4 (210 × 297 mm) - Idéal pour impression bureau
- **A5** : Format A5 (148 × 210 mm) - Format compact
- **10x15** : Format 10x15 cm - Idéal pour étiquettes adhésives

```php
// Obtenir les formats disponibles
$formats = $expeditionWithLabel->label->getAvailableFormats(); // ['A4', 'A5', '10x15']

// Vérifier si un format est supporté
$isSupported = $expeditionWithLabel->label->hasFormat('A4'); // true

// Obtenir l'URL pour un format spécifique
$urlA4 = $expeditionWithLabel->getLabelUrl('A4');
$urlA5 = $expeditionWithLabel->getLabelUrl('A5');
$url10x15 = $expeditionWithLabel->getLabelUrl('10x15');
```

## Modes de livraison

- `24R` : Livraison en point relais (24h-48h)
- `24L` : Livraison à domicile (24h-48h)
- `24X` : Livraison express en point relais
- `LD1` : Livraison à domicile (J+1)
- `LDS` : Livraison à domicile le samedi
- `DRI` : Drive

## Gestion des erreurs

Le package lance des exceptions `MondialRelayException` en cas d'erreur :

```php
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;

try {
    $relayPoints = MondialRelay::searchRelayPoints([
        'postal_code' => '75001',
    ]);
} catch (MondialRelayException $e) {
    echo 'Erreur API: ' . $e->getMessage();
    echo 'Code erreur: ' . $e->getCode();
}
```

## Tests

```bash
composer test
```

## Changelog

Consultez le [CHANGELOG](CHANGELOG.md) pour voir les dernières modifications.

## Contribuer

Les contributions sont les bienvenues ! Consultez le [guide de contribution](CONTRIBUTING.md).

## Sécurité

Si vous découvrez une faille de sécurité, envoyez un email à contact@virage-numerique.com.

## Crédits

- [Bryan M](https://github.com/bmwsly)

## Licence

The MIT License (MIT). Consultez le [fichier de licence](LICENSE.md) pour plus de détails.
