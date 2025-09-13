# 🚀 Release v1.4.0 - Enhanced Error Handling and Debugging

## 🔍 Major Improvement: Detailed Error Messages

Cette version **v1.4.0** résout complètement le problème des messages d'erreur peu informatifs. Fini les "Erreur inconnue" ! Chaque erreur fournit maintenant un contexte détaillé pour un debugging efficace.

## 🎯 Problème Résolu

### AVANT (Problématique)
```
❌ "Erreur inconnue"
```

### MAINTENANT (Informatif)
```
✅ "Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR - Enseigne: CC23KDJZ [Code erreur API: 9]"
```

## ✨ Nouvelles Fonctionnalités

### 🔐 Messages d'Erreur Détaillés
- **Code d'erreur API exact** (1, 9, 10, etc.)
- **Méthode qui a échoué** (searchRelayPoints, createExpedition, etc.)
- **Paramètres utilisés** (code postal, enseigne, pays, etc.)
- **Contexte complet** pour le debugging

### 🛠️ Gestion d'Erreurs Améliorée
- **Erreurs SOAP** : Détails de connexion et serveur
- **Erreurs API** : Codes spécifiques avec contexte
- **Erreurs de validation** : Informations sur les paramètres
- **Erreurs réseau** : Détails techniques

### 🔧 Nouvelles Méthodes
- `getDebugInfo()` : Informations complètes pour le debug
- `isApiError()` : Identification des erreurs API
- `getUserMessage()` : Messages conviviaux pour l'utilisateur
- Contexte structuré pour les logs et monitoring

## 🧪 Exemples d'Utilisation

### Gestion d'Erreurs Basique
```php
use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;

$client = new MondialRelayClient('CC23KDJZ', 'dUqJrThE', false);

try {
    $relayPoints = $client->searchRelayPoints([
        'postal_code' => '99999', // Code inexistant
        'country' => 'FR',
    ]);
} catch (MondialRelayException $e) {
    // Message détaillé au lieu de "Erreur inconnue"
    echo $e->getMessage();
    // "Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR - Enseigne: CC23KDJZ [Code erreur API: 9]"
}
```

### Debug Avancé
```php
try {
    // Votre appel API
} catch (MondialRelayException $e) {
    // Informations complètes pour le debug
    $debugInfo = $e->getDebugInfo();
    error_log(json_encode($debugInfo, JSON_PRETTY_PRINT));
    
    // Contexte spécifique
    $context = $e->getContext();
    if (isset($context['postal_code'])) {
        echo "Code postal testé: " . $context['postal_code'];
    }
    
    // Type d'erreur
    if ($e->isApiError()) {
        echo "Erreur API avec code: " . $e->getCode();
    }
}
```

## 📊 Types d'Erreurs Détectées

### 🔐 Erreurs d'Authentification
- **Code 1** : Enseigne invalide → `"Enseigne invalide (Méthode: searchRelayPoints) - Enseigne: INVALID123 [Code erreur API: 1]"`
- **Code 8** : Hash incorrect → `"Mot de passe ou hash incorrect (Méthode: createExpedition) - Enseigne: CC23KDJZ [Code erreur API: 8]"`

### 📍 Erreurs de Localisation
- **Code 9** : Ville inconnue → `"Ville inconnue ou non unique (Méthode: searchRelayPoints) - Code postal: 99999 - Pays: FR [Code erreur API: 9]"`
- **Code 11** : Code postal incorrect → `"Code postal incorrect (Méthode: searchRelayPoints) - Code postal: INVALID [Code erreur API: 11]"`

### 🌐 Erreurs Réseau
- **SoapFault** : `"SOAP API Error during relay points search: Could not connect to host"`
- **Timeout** : `"Unexpected error during expedition creation: Connection timeout"`

## 🔧 Améliorations Techniques

### Exception Handling
- **Préservation du contexte** à travers la chaîne d'erreurs
- **Gestion séparée** pour SoapFault, MondialRelayException, et exceptions génériques
- **Informations structurées** pour les outils de monitoring

### Debugging
- **Logs détaillés** avec toutes les informations nécessaires
- **Stack trace** complet pour localiser les problèmes
- **Contexte JSON** pour les outils d'analyse

## 📚 Documentation et Tests

### 🧪 Tests Complets
- **5 nouveaux tests** pour la gestion d'erreurs détaillée
- **Scripts de test** avec vos vraies données (CC23KDJZ/dUqJrThE)
- **Validation** du formatage des messages et préservation du contexte

### 📖 Documentation
- **`DEBUG-ERROR-HANDLING.md`** : Guide complet avec exemples
- **Scripts de test** : `test-error-simple.php` pour tester rapidement
- **Exemples pratiques** avec vos identifiants

## 🎯 Avantages

### ✅ Pour le Développement
- **Debug rapide** : Identifiez immédiatement le problème
- **Contexte complet** : Tous les paramètres utilisés sont disponibles
- **Messages clairs** : Plus de "Erreur inconnue"

### ✅ Pour la Production
- **Monitoring amélioré** : Alertes précises sur les erreurs
- **Support client** : Réponses rapides avec informations exactes
- **Logs structurés** : Informations JSON pour les outils d'analyse

### ✅ Pour la Maintenance
- **Identification proactive** des problèmes
- **Codes d'erreur API** : Référence directe à la documentation
- **Reproduction facile** des erreurs avec paramètres exacts

## 🚀 Migration

### ✅ Rétrocompatibilité Totale
- **Aucun changement breaking** dans l'API existante
- **Migration transparente** : vos try/catch existants fonctionnent
- **Adoption progressive** des nouvelles fonctionnalités

### 📈 Mise à Jour Recommandée
```bash
composer require bmwsly/mondial-relay-api:^1.4.0
```

## 🔮 Impact

Cette version transforme complètement l'expérience de debugging :

- ❌ **Avant** : Perte de temps à deviner le problème
- ✅ **Maintenant** : Identification immédiate avec contexte complet

**Vous ne verrez plus jamais "Erreur inconnue" !** 🎉

## 📦 Installation

```bash
composer require bmwsly/mondial-relay-api:^1.4.0
```

---

**Version 1.4.0** : La gestion d'erreurs que vous attendiez ! 🚀
