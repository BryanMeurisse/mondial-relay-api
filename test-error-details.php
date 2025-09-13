<?php

require_once 'vendor/autoload.php';

use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Debug\MondialRelayDebugger;

// Mock Laravel's now() function for standalone usage
if (!function_exists('now')) {
    function now() {
        return new class {
            public function toISOString() {
                return date('c');
            }
        };
    }
}

// Configuration avec vos vraies données
$enseigne = 'CC23KDJZ';
$privateKey = 'dUqJrThE';
$apiUrl = 'https://api.mondialrelay.com/Web_Services.asmx';

// Créer le client avec debug activé
$debugger = new MondialRelayDebugger();
$client = new MondialRelayClient($enseigne, $privateKey, false, $apiUrl, $debugger);

echo "=== Test de Gestion d'Erreurs Détaillées ===\n\n";

// Test 1: Code postal inexistant
echo "1. Test avec un code postal inexistant (99999):\n";
echo "-------------------------------------------\n";
try {
    $relayPoints = $client->searchRelayPoints([
        'postal_code' => '99999',
        'country' => 'FR',
        'max_results' => 5,
    ]);
    echo "✅ Succès (inattendu): " . count($relayPoints) . " points trouvés\n";
} catch (MondialRelayException $e) {
    echo "❌ Erreur capturée:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
    echo "   Est une erreur API: " . ($e->isApiError() ? 'Oui' : 'Non') . "\n";
    
    $context = $e->getContext();
    if (isset($context['api_error_code'])) {
        echo "   Code erreur API: " . $context['api_error_code'] . "\n";
    }
    if (isset($context['base_message'])) {
        echo "   Message de base: " . $context['base_message'] . "\n";
    }
    if (isset($context['method'])) {
        echo "   Méthode: " . $context['method'] . "\n";
    }
    if (isset($context['postal_code'])) {
        echo "   Code postal testé: " . $context['postal_code'] . "\n";
    }
    
    echo "\n   Informations de debug complètes:\n";
    $debugInfo = $e->getDebugInfo();
    foreach ($debugInfo as $key => $value) {
        if ($key === 'trace') {
            echo "   $key: [trace disponible]\n";
        } elseif ($key === 'context') {
            echo "   $key: " . count($value) . " éléments de contexte\n";
        } else {
            echo "   $key: $value\n";
        }
    }
}

echo "\n\n";

// Test 2: Enseigne invalide
echo "2. Test avec une enseigne invalide:\n";
echo "-----------------------------------\n";
$invalidClient = new MondialRelayClient('INVALID123', 'wrongkey', false, $apiUrl, $debugger);

try {
    $relayPoints = $invalidClient->searchRelayPoints([
        'postal_code' => '75001',
        'country' => 'FR',
        'max_results' => 5,
    ]);
    echo "✅ Succès (inattendu): " . count($relayPoints) . " points trouvés\n";
} catch (MondialRelayException $e) {
    echo "❌ Erreur capturée:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
    
    $context = $e->getContext();
    if (isset($context['enseigne'])) {
        echo "   Enseigne testée: " . $context['enseigne'] . "\n";
    }
}

echo "\n\n";

// Test 3: Code postal valide pour vérifier que ça marche
echo "3. Test avec un code postal valide (75001):\n";
echo "-------------------------------------------\n";
try {
    $relayPoints = $client->searchRelayPoints([
        'postal_code' => '75001',
        'country' => 'FR',
        'max_results' => 3,
    ]);
    echo "✅ Succès: " . count($relayPoints) . " points trouvés\n";
    if (count($relayPoints) > 0) {
        echo "   Premier point: " . $relayPoints[0]->getName() . " - " . $relayPoints[0]->getAddress() . "\n";
    }
} catch (MondialRelayException $e) {
    echo "❌ Erreur inattendue:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
}

echo "\n=== Fin des tests ===\n";
