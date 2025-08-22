# Données requises pour le bon fonctionnement

Ce document détaille toutes les données nécessaires pour utiliser efficacement le package Laravel Mondial Relay API.

## 🔧 Configuration initiale

### Variables d'environnement (.env)

```env
# OBLIGATOIRES
MONDIAL_RELAY_ENSEIGNE=VOTRE_ENSEIGNE          # Numéro d'enseigne fourni par Mondial Relay
MONDIAL_RELAY_PRIVATE_KEY=VOTRE_CLE_PRIVEE     # Clé privée fournie par Mondial Relay

# OPTIONNELLES (avec valeurs par défaut)
MONDIAL_RELAY_TEST_MODE=true                   # true = test, false = production
MONDIAL_RELAY_API_URL=https://api.mondialrelay.com/WebService.asmx
```

### Identifiants de test

Pour vos tests, utilisez ces identifiants fournis par Mondial Relay :

```env
MONDIAL_RELAY_ENSEIGNE=BDTEST13
MONDIAL_RELAY_PRIVATE_KEY=TestAPI1key
MONDIAL_RELAY_TEST_MODE=true
```

## 📦 Données d'expédition

### Structure de l'expéditeur (sender)

```php
$sender = [
    // OBLIGATOIRES
    'company' => 'Mon E-commerce',              // Nom de l'entreprise
    'lastname' => 'Dupont',                     // Nom de famille
    'firstname' => 'Jean',                      // Prénom
    'address' => '123 Rue du Commerce',         // Adresse complète
    'postal_code' => '75001',                   // Code postal (5 chiffres pour la France)
    'city' => 'Paris',                          // Ville
    'country' => 'FR',                          // Code pays ISO (FR, BE, ES, etc.)
    'phone' => '0123456789',                    // Téléphone (10 chiffres pour la France)
    'email' => 'contact@mon-ecommerce.fr',      // Email valide

    // OPTIONNELLES
    'address2' => 'Bâtiment A',                 // Complément d'adresse
];
```

### Structure du destinataire (recipient)

```php
$recipient = [
    // OBLIGATOIRES
    'lastname' => 'Martin',                     // Nom de famille
    'firstname' => 'Marie',                     // Prénom
    'address' => '456 Avenue de la Livraison',  // Adresse complète
    'postal_code' => '69000',                   // Code postal
    'city' => 'Lyon',                           // Ville
    'country' => 'FR',                          // Code pays ISO
    'phone' => '0987654321',                    // Téléphone
    'email' => 'marie.martin@example.com',      // Email valide

    // OPTIONNELLES
    'address2' => 'Appartement 12',             // Complément d'adresse
    'company' => 'Entreprise Martin',           // Nom d'entreprise (si applicable)
];
```

### Données du colis

```php
$packageData = [
    // OBLIGATOIRES
    'weight' => 1000,                           // Poids en grammes (min: 1g, max: 30kg)
    'description' => 'Vêtements - 2 articles', // Description du contenu

    // OPTIONNELLES
    'declared_value' => 50.00,                  // Valeur déclarée en euros
    'order_number' => 'CMD-2024-001',           // Numéro de commande
    'length' => 20,                             // Longueur en cm
    'width' => 15,                              // Largeur en cm  
    'height' => 10,                             // Hauteur en cm
];
```

## 🏪 Points relais

### Données essentielles d'un point relais

```php
// Après recherche de points relais
$relayPoint = $relayPoints[0];

// OBLIGATOIRE pour créer une expédition
$relayNumber = $relayPoint->number;             // Ex: "123456"

// INFORMATIONS UTILES pour l'utilisateur
$name = $relayPoint->name;                      // "TABAC DE LA GARE"
$address = $relayPoint->getFullAddress();       // "12 RUE DE LA GARE, 75001 PARIS"
$distance = $relayPoint->getFormattedDistance(); // "1.3 km"
$isOpen = $relayPoint->isOpenToday();           // true/false
```

### Critères de recherche

```php
// Paramètres de recherche de points relais
$searchParams = [
    'postal_code' => '75001',                   // OBLIGATOIRE - Code postal
    'country' => 'FR',                          // OBLIGATOIRE - Code pays
    'limit' => 10,                              // OPTIONNEL - Nombre de résultats (défaut: 10)
    'weight' => 1000,                           // OPTIONNEL - Poids en grammes
    'radius' => 20,                             // OPTIONNEL - Rayon de recherche en km
];
```

## 🚚 Modes de livraison

### Codes de livraison supportés

```php
$deliveryModes = [
    // POINT RELAIS
    '24R' => 'Livraison en point relais (24h-48h)',     // Le plus courant
    '24X' => 'Livraison express en point relais',
    
    // DOMICILE
    '24L' => 'Livraison à domicile (24h-48h)',
    'LD1' => 'Livraison à domicile (J+1)',
    'LDS' => 'Livraison à domicile le samedi',
    
    // AUTRES
    'DRI' => 'Drive',
];
```

### Données requises selon le mode

```php
// Pour les modes point relais (24R, 24X)
$expeditionData = [
    'relay_number' => '123456',                 // OBLIGATOIRE
    'relay_country' => 'FR',                    // OBLIGATOIRE
    // ... autres données
];

// Pour les modes domicile (24L, LD1, LDS)
$expeditionData = [
    // Pas de relay_number requis
    // Adresse destinataire complète OBLIGATOIRE
    // ... autres données
];
```

## 📋 Validation des données

### Règles de validation automatique

Le package valide automatiquement :

```php
// Codes postaux français
'postal_code' => '75001',                       // ✅ Valide (5 chiffres)
'postal_code' => '7500',                        // ❌ Invalide (4 chiffres)

// Poids
'weight' => 1000,                               // ✅ Valide (1kg)
'weight' => 35000,                              // ❌ Invalide (>30kg)

// Numéros de téléphone français
'phone' => '0123456789',                        // ✅ Valide
'phone' => '123456789',                         // ❌ Invalide (9 chiffres)

// Emails
'email' => 'test@example.com',                  // ✅ Valide
'email' => 'test@',                             // ❌ Invalide

// Numéros de point relais
'relay_number' => '123456',                     // ✅ Valide (6 chiffres)
'relay_number' => '12345',                      // ❌ Invalide (5 chiffres)
```

## 🔍 Données de suivi

### Informations retournées par le suivi

```php
$trackingInfo = MondialRelayService::trackPackage('12345678901234');

// Données principales
$expeditionNumber = $trackingInfo->expeditionNumber;  // Numéro d'expédition
$status = $trackingInfo->status;                      // Code statut (ex: "24")
$statusLabel = $trackingInfo->statusLabel;            // "Livré en point relais"
$relayNumber = $trackingInfo->relayNumber;            // Numéro du point relais
$relayName = $trackingInfo->relayName;                // Nom du point relais

// Événements de suivi
foreach ($trackingInfo->trackingEvents as $event) {
    $date = $event->getFormattedDateTime();           // "22/08/2024 14:30"
    $label = $event->label;                           // "Colis pris en charge"
    $location = $event->location;                     // "PARIS"
}
```

## 🏷️ Formats d'étiquettes

### Formats supportés

```php
$formats = [
    'A4' => [
        'size' => '210x297mm',
        'description' => 'Format standard bureau',
        'recommended_for' => 'Impression laser/jet d\'encre'
    ],
    'A5' => [
        'size' => '148x210mm', 
        'description' => 'Format compact',
        'recommended_for' => 'Économie de papier'
    ],
    '10x15' => [
        'size' => '100x150mm',
        'description' => 'Format étiquette',
        'recommended_for' => 'Étiquettes adhésives'
    ]
];
```

## ⚠️ Erreurs courantes

### Erreurs de configuration

```php
// Erreur 1 : Identifiants incorrects
'code' => 1,
'message' => 'Erreur d\'authentification',
'solution' => 'Vérifiez MONDIAL_RELAY_ENSEIGNE et MONDIAL_RELAY_PRIVATE_KEY'

// Erreur 97 : Clé de sécurité invalide  
'code' => 97,
'message' => 'Clé de sécurité invalide',
'solution' => 'Vérifiez l\'ordre des paramètres et la clé privée'
```

### Erreurs de données

```php
// Erreur 10 : Numéro d'enseigne invalide
'code' => 10,
'message' => 'Numéro d\'enseigne invalide',
'solution' => 'Contactez Mondial Relay pour valider votre enseigne'

// Erreur 20 : Code postal invalide
'code' => 20, 
'message' => 'Code postal invalide',
'solution' => 'Utilisez un code postal français valide (5 chiffres)'
```

## 🧪 Données de test

### Jeu de données complet pour les tests

```php
$testData = [
    'sender' => [
        'company' => 'Test E-commerce',
        'lastname' => 'Dupont',
        'firstname' => 'Jean',
        'address' => '123 Rue de Test',
        'postal_code' => '75001',
        'city' => 'Paris',
        'country' => 'FR',
        'phone' => '0123456789',
        'email' => 'test@example.com'
    ],
    'recipient' => [
        'lastname' => 'Martin',
        'firstname' => 'Marie',
        'address' => '456 Avenue de Test',
        'postal_code' => '69000',
        'city' => 'Lyon',
        'country' => 'FR',
        'phone' => '0987654321',
        'email' => 'marie.martin@example.com'
    ],
    'package' => [
        'weight' => 1000,
        'description' => 'Produit de test'
    ]
];
```

## 📞 Support

- **Documentation API officielle** : [Mondial Relay API](https://www.mondialrelay.fr/solutionspro/documentation-technique/)
- **Issues GitHub** : [Signaler un problème](https://github.com/BryanMeurisse/mondial-relay-api/issues)
- **Email** : meurisse.bryan@gmail.com
