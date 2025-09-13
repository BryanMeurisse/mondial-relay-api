<?php

require_once 'vendor/autoload.php';

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;

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

echo "=== Test des Messages d'Erreur Détaillés ===\n\n";

// Test 1: Erreur avec code postal inexistant
echo "1. Test avec code postal inexistant (erreur code 9):\n";
echo "---------------------------------------------------\n";

$response = ['STAT' => '9'];
$context = [
    'method' => 'searchRelayPoints',
    'postal_code' => '99999',
    'country' => 'FR',
    'enseigne' => 'CC23KDJZ',
];

$exception = MondialRelayException::fromApiResponse($response, $context);

echo "Message complet: " . $exception->getMessage() . "\n";
echo "Code d'erreur: " . $exception->getCode() . "\n";
echo "Est une erreur API: " . ($exception->isApiError() ? 'Oui' : 'Non') . "\n";

$exceptionContext = $exception->getContext();
echo "Contexte disponible:\n";
foreach ($exceptionContext as $key => $value) {
    if (is_array($value)) {
        echo "  - $key: [array avec " . count($value) . " éléments]\n";
    } else {
        echo "  - $key: $value\n";
    }
}

echo "\n";

// Test 2: Erreur avec enseigne invalide
echo "2. Test avec enseigne invalide (erreur code 1):\n";
echo "-----------------------------------------------\n";

$response2 = ['STAT' => '1'];
$context2 = [
    'method' => 'searchRelayPoints',
    'postal_code' => '75001',
    'country' => 'FR',
    'enseigne' => 'INVALID123',
];

$exception2 = MondialRelayException::fromApiResponse($response2, $context2);

echo "Message complet: " . $exception2->getMessage() . "\n";
echo "Code d'erreur: " . $exception2->getCode() . "\n";

echo "\n";

// Test 3: Erreur inconnue
echo "3. Test avec code d'erreur inconnu (erreur code 999):\n";
echo "----------------------------------------------------\n";

$response3 = ['STAT' => '999'];
$context3 = [
    'method' => 'createExpedition',
    'enseigne' => 'CC23KDJZ',
];

$exception3 = MondialRelayException::fromApiResponse($response3, $context3);

echo "Message complet: " . $exception3->getMessage() . "\n";
echo "Code d'erreur: " . $exception3->getCode() . "\n";

echo "\n";

// Test 4: Informations de debug
echo "4. Test des informations de debug:\n";
echo "----------------------------------\n";

$debugInfo = $exception->getDebugInfo();
echo "Informations de debug disponibles:\n";
foreach ($debugInfo as $key => $value) {
    if ($key === 'trace') {
        echo "  - $key: [stack trace disponible]\n";
    } elseif ($key === 'context') {
        echo "  - $key: " . count($value) . " éléments de contexte\n";
    } else {
        echo "  - $key: $value\n";
    }
}

echo "\n=== Comparaison Avant/Après ===\n";
echo "AVANT: 'Erreur inconnue'\n";
echo "APRÈS: '" . $exception->getMessage() . "'\n";
echo "\nMaintenant vous avez:\n";
echo "✅ Le code d'erreur exact de l'API\n";
echo "✅ La méthode qui a échoué\n";
echo "✅ Les paramètres utilisés (code postal, enseigne, etc.)\n";
echo "✅ Le contexte complet pour le debug\n";
echo "✅ Des informations structurées pour les logs\n";

echo "\n=== Fin des tests ===\n";
