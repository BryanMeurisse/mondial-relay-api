# 🔍 Guide de Debug et Gestion d'Erreurs Améliorée

## 🎯 Problème Résolu

**AVANT** : Quand un code postal n'existait pas, vous receviez simplement :
```
❌ "Erreur inconnue"
```

**MAINTENANT** : Vous recevez des informations détaillées :
```
✅ "Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR - Enseigne: CC23KDJZ [Code erreur API: 9]"
```

## 🚀 Nouvelles Fonctionnalités

### 1. Messages d'Erreur Détaillés

Les exceptions `MondialRelayException` incluent maintenant :
- **Code d'erreur API exact** (1, 9, 10, etc.)
- **Méthode qui a échoué** (searchRelayPoints, createExpedition, etc.)
- **Paramètres utilisés** (code postal, enseigne, pays, etc.)
- **Contexte complet** pour le debugging

### 2. Gestion d'Erreurs par Type

```php
try {
    $relayPoints = $client->searchRelayPoints([
        'postal_code' => '99999', // Code inexistant
        'country' => 'FR',
    ]);
} catch (MondialRelayException $e) {
    // Informations détaillées disponibles
    echo "Erreur: " . $e->getMessage();
    echo "Code API: " . $e->getCode();
    echo "Est une erreur API: " . ($e->isApiError() ? 'Oui' : 'Non');
    
    // Contexte complet
    $context = $e->getContext();
    if (isset($context['postal_code'])) {
        echo "Code postal testé: " . $context['postal_code'];
    }
}
```

### 3. Informations de Debug Complètes

```php
try {
    // Votre appel API
} catch (MondialRelayException $e) {
    $debugInfo = $e->getDebugInfo();
    
    // Toutes les informations pour le debug
    error_log(json_encode($debugInfo, JSON_PRETTY_PRINT));
}
```

## 📋 Types d'Erreurs Détectées

### 🔐 Erreurs d'Authentification
- **Code 1** : Enseigne invalide
- **Code 2** : Numéro d'enseigne vide  
- **Code 8** : Mot de passe ou hash incorrect

**Exemple** :
```
"Enseigne invalide (Méthode: searchRelayPoints) - Enseigne: INVALID123 [Code erreur API: 1]"
```

### 📍 Erreurs de Localisation
- **Code 9** : Ville inconnue ou non unique
- **Code 11** : Code postal incorrect
- **Code 12** : Pays incorrect

**Exemple** :
```
"Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR [Code erreur API: 9]"
```

### 📦 Erreurs d'Expédition
- **Code 10** : Type de collecte incorrect
- **Code 13** : Poids incorrect
- **Code 20** : Taille incorrecte

### 🌐 Erreurs SOAP/Réseau
- **SoapFault** : Erreurs de connexion SOAP
- **Exception générale** : Erreurs réseau ou serveur

## 🛠️ Utilisation avec vos Données

Avec vos identifiants :
- **URL API** : `https://api.mondialrelay.com/WebService.asmx`
- **Code Enseigne** : `CC23KDJZ`
- **Clé privée** : `dUqJrThE`

```php
use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;

$client = new MondialRelayClient(
    'CC23KDJZ',
    'dUqJrThE',
    false, // Mode production
    'https://api.mondialrelay.com/Web_Services.asmx'
);

try {
    $relayPoints = $client->searchRelayPoints([
        'postal_code' => '99999', // Code inexistant pour tester
        'country' => 'FR',
        'max_results' => 5,
    ]);
} catch (MondialRelayException $e) {
    // Vous obtiendrez maintenant :
    // "Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR - Enseigne: CC23KDJZ [Code erreur API: 9]"
    
    $context = $e->getContext();
    
    // Log pour investigation
    error_log("Erreur Mondial Relay: " . $e->getMessage());
    error_log("Contexte: " . json_encode($context));
}
```

## 🔧 Debug Avancé

### Activation du Debug Complet

```php
use Bmwsly\MondialRelayApi\Debug\MondialRelayDebugger;

$debugger = new MondialRelayDebugger();
$client = new MondialRelayClient(
    'CC23KDJZ',
    'dUqJrThE',
    false,
    'https://api.mondialrelay.com/Web_Services.asmx',
    $debugger
);

// Maintenant toutes les requêtes/réponses SOAP sont loggées
```

### Récupération des Logs

```php
$logs = $debugger->getLogs();
foreach ($logs as $log) {
    echo "Type: " . $log['type'] . "\n";
    echo "Message: " . $log['message'] . "\n";
    echo "Data: " . json_encode($log['data']) . "\n";
}
```

## 📊 Exemples de Messages d'Erreur

### Code Postal Inexistant
```
AVANT: "Erreur inconnue"
APRÈS: "Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR - Enseigne: CC23KDJZ [Code erreur API: 9]"
```

### Enseigne Invalide
```
AVANT: "Erreur inconnue"
APRÈS: "Enseigne invalide (Méthode: searchRelayPoints) - Code postal: 75001 - Pays: FR - Enseigne: INVALID123 [Code erreur API: 1]"
```

### Erreur SOAP
```
AVANT: "API call failed: [message générique]"
APRÈS: "SOAP API Error during relay points search: Could not connect to host [Code erreur: 0]"
```

## 🎯 Avantages

### ✅ Pour le Développement
- **Debug rapide** : Identifiez immédiatement le problème
- **Contexte complet** : Tous les paramètres utilisés sont disponibles
- **Logs structurés** : Informations JSON pour les outils de monitoring

### ✅ Pour la Production
- **Monitoring amélioré** : Alertes précises sur les erreurs
- **Support client** : Réponses rapides avec des informations exactes
- **Maintenance** : Identification proactive des problèmes

### ✅ Pour l'Investigation
- **Codes d'erreur API** : Référence directe à la documentation Mondial Relay
- **Paramètres exacts** : Reproduction facile des erreurs
- **Stack trace** : Localisation précise dans le code

## 🚀 Prochaines Étapes

1. **Testez avec vos données** : Utilisez le script `test-error-simple.php`
2. **Intégrez dans votre code** : Remplacez les anciens try/catch
3. **Configurez les logs** : Ajoutez les nouvelles informations à vos logs
4. **Monitoring** : Utilisez les codes d'erreur pour des alertes précises

---

**Maintenant vous ne verrez plus jamais "Erreur inconnue" ! 🎉**
