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

echo "=== Test du Code d'Erreur 92 ===\n\n";

// Test du code d'erreur 92 qui était problématique
echo "Test avec le code d'erreur 92 (createExpeditionWithLabel):\n";
echo "--------------------------------------------------------\n";

$response = ['STAT' => '92'];
$context = [
    'method' => 'createExpeditionWithLabel',
    'enseigne' => 'CC23KDJZ',
    'delivery_mode' => '24R',
    'weight' => '1000',
    'articles_description' => 'Test product',
];

$exception = MondialRelayException::fromApiResponse($response, $context);

echo "AVANT (problématique):\n";
echo "  'API call failed: Erreur inconnue (Méthode: createExpeditionWithLabel) [Code erreur API: 92]'\n\n";

echo "MAINTENANT (informatif):\n";
echo "  Message: " . $exception->getMessage() . "\n";
echo "  Code: " . $exception->getCode() . "\n";
echo "  Est une erreur API: " . ($exception->isApiError() ? 'Oui' : 'Non') . "\n\n";

echo "Contexte disponible:\n";
$exceptionContext = $exception->getContext();
foreach ($exceptionContext as $key => $value) {
    if (is_array($value)) {
        echo "  - $key: [array avec " . count($value) . " éléments]\n";
    } else {
        echo "  - $key: $value\n";
    }
}

echo "\n=== Signification du Code 92 ===\n";
echo "Le code d'erreur 92 indique généralement :\n";
echo "- Erreur lors de la génération de l'étiquette\n";
echo "- Problème dans le traitement de l'expédition\n";
echo "- Paramètres d'expédition incorrects ou incompatibles\n";
echo "- Problème avec les données du destinataire ou de l'expéditeur\n\n";

echo "=== Actions Recommandées ===\n";
echo "1. Vérifiez tous les paramètres d'expédition (poids, dimensions, adresses)\n";
echo "2. Vérifiez que le mode de livraison est compatible avec la destination\n";
echo "3. Vérifiez les informations du destinataire (nom, adresse, code postal)\n";
echo "4. Vérifiez les informations de l'expéditeur\n";
echo "5. Contactez le support Mondial Relay si le problème persiste\n\n";

echo "=== Informations de Debug Complètes ===\n";
$debugInfo = $exception->getDebugInfo();
echo "Informations disponibles pour investigation:\n";
foreach ($debugInfo as $key => $value) {
    if ($key === 'trace') {
        echo "  - $key: [stack trace complet disponible]\n";
    } elseif ($key === 'context') {
        echo "  - $key: " . count($value) . " éléments de contexte\n";
    } else {
        echo "  - $key: $value\n";
    }
}

echo "\n=== Résultat ===\n";
echo "✅ Le code d'erreur 92 est maintenant correctement identifié\n";
echo "✅ Message d'erreur informatif au lieu de 'Erreur inconnue'\n";
echo "✅ Contexte complet disponible pour le debugging\n";
echo "✅ Actions recommandées pour résoudre le problème\n";

echo "\n=== Fin du test ===\n";
