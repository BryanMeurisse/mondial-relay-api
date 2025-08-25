# Laravel Mondial Relay API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bmwsly/mondial-relay-api.svg?style=flat-square)](https://packagist.org/packages/bmwsly/mondial-relay-api)
[![Total Downloads](https://img.shields.io/packagist/dt/bmwsly/mondial-relay-api.svg?style=flat-square)](https://packagist.org/packages/bmwsly/mondial-relay-api)

Un package Laravel pour intégrer facilement l'API Mondial Relay dans vos applications e-commerce. Ce package se concentre sur les fonctionnalités essentielles : recherche de points relais, création d'expéditions et suivi de colis.

## ✨ Nouvelles Fonctionnalités v2.0

🎉 **Version majeure avec de nombreuses améliorations basées sur l'API PHP officielle !**

- **Gestion d'erreurs complète** - 99+ codes d'erreur avec messages explicites et actions suggérées
- **Mode debug avancé** - Logging détaillé des requêtes/réponses avec masquage des données sensibles
- **Validation renforcée** - Règles strictes basées sur l'API officielle pour tous les paramètres
- **Support API V2 (REST)** - Client hybride SOAP/REST avec basculement automatique
- **Configuration avancée** - Sécurité, cache, timeouts, retry, environnements
- **Gestion multi-colis** - Support complet des expéditions avec plusieurs colis
- **Commande de diagnostic** - `php artisan mondialrelay:diagnose` pour tester votre configuration
- **DTOs enrichis** - Modèles complets avec toutes les propriétés de l'API

👉 **[Voir toutes les nouvelles fonctionnalités](NOUVELLES-FONCTIONNALITES.md)**

## 🚀 Démarrage rapide

```bash
# 1. Installation
composer require bmwsly/mondial-relay-api

# 2. Configuration
php artisan vendor:publish --provider="Bmwsly\MondialRelayApi\MondialRelayServiceProvider" --tag="config"

# 3. Variables d'environnement (.env)
MONDIAL_RELAY_ENSEIGNE=BDTEST13
MONDIAL_RELAY_PRIVATE_KEY=TestAPI1key
MONDIAL_RELAY_TEST_MODE=true

# 4. Test de la configuration (NOUVEAU!)
php artisan mondialrelay:diagnose --test-api
```

```php
// 5. Utilisation dans votre code Laravel
use Bmwsly\MondialRelayApi\Facades\MondialRelayService;

// Rechercher des points relais
$relayPoints = MondialRelayService::findNearestRelayPoints('75001', 'FR', 5);

// Créer une expédition avec étiquette PDF
$expedition = MondialRelayService::createExpeditionWithLabel(
    sender: $senderData,
    recipient: $recipientData,
    relayNumber: $relayPoints[0]->number,
    weightInGrams: 1000,
    deliveryMode: '24R'
);

// Télécharger l'étiquette
$pdfContent = $expedition->downloadLabel('A4');
file_put_contents('etiquette.pdf', $pdfContent);

echo "✅ Expédition créée : " . $expedition->expeditionNumber;
echo "🔗 Suivi : " . $expedition->getTrackingUrl();
```

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
MONDIAL_RELAY_BRAND_ID=VOTRE_BRAND_ID
MONDIAL_RELAY_TEST_MODE=true
MONDIAL_RELAY_API_URL=https://api.mondialrelay.com/WebService.asmx

# Pour les liens de tracking sécurisés (optionnel)
MONDIAL_RELAY_API_V2_ENABLED=true
MONDIAL_RELAY_API_V2_USER=VOTRE_USER_API_V2
MONDIAL_RELAY_API_V2_PASSWORD=VOTRE_PASSWORD_API_V2
```

### Test de la configuration

Pour vérifier que votre configuration fonctionne, utilisez le script de test inclus :

```bash
php test-api-minimal.php
```

Ce script teste :
- ✅ La connexion à l'API
- ✅ La recherche de points relais
- ✅ La création d'expédition avec étiquette
- ✅ Le téléchargement d'étiquette PDF

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

// Génération d'URLs de suivi
$basicUrl = MondialRelayService::generateTrackingUrl('12345678901234');
echo "URL publique: " . $basicUrl . "\n";

// Lien sécurisé pour l'extranet professionnel (nécessite API V2)
$connectUrl = MondialRelayService::generateConnectTracingLink('12345678901234', 'user@example.com');
echo "URL extranet: " . $connectUrl . "\n";

// Lien permalink sécurisé pour le suivi public
$permalinkUrl = MondialRelayService::generatePermalinkTracingLink('12345678901234', 'fr', 'fr');
echo "URL permalink: " . $permalinkUrl . "\n";
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

## Modèles de données

Le package utilise des modèles de données typés pour une meilleure expérience développeur et une validation automatique.

### RelayPoint

Représente un point relais Mondial Relay avec toutes ses informations utiles :

```php
$relayPoint = $relayPoints[0]; // Premier point relais trouvé

// Propriétés principales (OBLIGATOIRES pour les expéditions)
echo $relayPoint->number;        // "123456" - Numéro unique du point relais
echo $relayPoint->name;          // "TABAC DE LA GARE" - Nom commercial
echo $relayPoint->address;       // "12 RUE DE LA GARE" - Adresse
echo $relayPoint->postalCode;    // "75001" - Code postal
echo $relayPoint->city;          // "PARIS" - Ville
echo $relayPoint->country;       // "FR" - Code pays

// Géolocalisation
echo $relayPoint->latitude;      // 48.8566 - Latitude GPS
echo $relayPoint->longitude;     // 2.3522 - Longitude GPS
echo $relayPoint->distance;      // 1250 - Distance en mètres

// Méthodes utiles
echo $relayPoint->getFullAddress();           // "12 RUE DE LA GARE, 75001 PARIS"
echo $relayPoint->getFormattedDistance();     // "1.3 km"
echo $relayPoint->isOpenToday();              // true/false
echo $relayPoint->isCurrentlyOpen();          // true/false
echo $relayPoint->getGoogleMapsUrl();         // URL Google Maps

// Horaires d'ouverture
$todayHours = $relayPoint->getTodayOpeningHours();
// [['open' => '0900', 'close' => '1800'], ['open' => '1400', 'close' => '1900']]
```

### ExpeditionWithLabel

Représente une expédition avec son étiquette PDF générée :

```php
$expeditionWithLabel = MondialRelayService::createExpeditionWithLabel(...);

// Propriétés principales
echo $expeditionWithLabel->expeditionNumber;  // "12345678901234" - Numéro unique
$label = $expeditionWithLabel->label;         // Objet Label

// Méthodes utiles
echo $expeditionWithLabel->getTrackingUrl();  // URL de suivi public
echo $expeditionWithLabel->getLabelUrl('A4'); // URL étiquette A4

// Téléchargement d'étiquettes
$pdfContent = $expeditionWithLabel->downloadLabel('A4');
$expeditionWithLabel->saveLabelToFile('etiquette.pdf', 'A4');

// Tous les formats disponibles
$allUrls = $expeditionWithLabel->getAllLabelUrls();
// ['A4' => 'url...', 'A5' => 'url...', '10x15' => 'url...']
```

### Label

Représente une étiquette PDF avec ses différents formats :

```php
$label = $expeditionWithLabel->label;

// URLs de téléchargement
echo $label->labelUrlA4;      // URL format A4
echo $label->labelUrlA5;      // URL format A5
echo $label->labelUrl10x15;   // URL format 10x15

// Méthodes utiles
echo $label->getUrlByFormat('A4');           // URL pour format spécifique
$formats = $label->getAvailableFormats();    // ['A4', 'A5', '10x15']
$hasA4 = $label->hasFormat('A4');            // true

// Informations détaillées sur les formats
$formatInfo = $label->getFormatInfo();
/*
[
    'A4' => [
        'name' => 'A4',
        'description' => 'Format A4 standard (210x297mm)',
        'url' => 'https://...',
        'recommended_for' => 'Impression bureau standard'
    ],
    // ...
]
*/
```

### TrackingInfo

Informations de suivi d'un colis :

```php
$trackingInfo = MondialRelayService::trackPackage('12345678901234');

echo $trackingInfo->expeditionNumber;  // Numéro d'expédition
echo $trackingInfo->status;            // Code statut (ex: "24")
echo $trackingInfo->statusLabel;       // Libellé français du statut
echo $trackingInfo->relayNumber;       // Numéro du point relais
echo $trackingInfo->relayName;         // Nom du point relais

// Événements de suivi
foreach ($trackingInfo->trackingEvents as $event) {
    echo $event->getFormattedDateTime(); // "22/08/2024 14:30"
    echo $event->label;                  // "Colis pris en charge"
    echo $event->location;               // "PARIS"
}
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
