<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Services\MondialRelayService;
use Bmwsly\MondialRelayApi\Helpers\MondialRelayHelper;
use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;

// Configuration
$enseigne = 'BDTEST13';
$privateKey = 'PrivateK';
$testMode = true;

// Initialisation du client
$client = new MondialRelayClient($enseigne, $privateKey, $testMode);
$service = new MondialRelayService($client);

echo "=== Exemple d'utilisation du package Mondial Relay API ===\n\n";

try {
    // 1. Recherche de points relais
    echo "1. Recherche de points relais près de Paris (75001)\n";
    echo "---------------------------------------------------\n";

    // Note: Dans un vrai environnement, cette requête ferait appel à l'API Mondial Relay
    // Ici nous simulons le comportement pour la démonstration
    echo "Note: Cet exemple simule les appels API (mode démonstration)\n";
    echo "Dans un environnement Laravel réel, utilisez les facades MondialRelay ou MondialRelayService\n\n";

    // Simulation de points relais pour la démonstration
    echo "Exemple de points relais trouvés:\n";
    echo "• Tabac de la Gare - 123 Rue de Rivoli, 75001 Paris\n";
    echo "  Distance: 0.5km\n";
    echo "  Numéro: 123456\n";
    echo "  Ouvert aujourd'hui: Oui\n\n";

    echo "• Épicerie du Coin - 456 Avenue de l'Opéra, 75001 Paris\n";
    echo "  Distance: 0.8km\n";
    echo "  Numéro: 789012\n";
    echo "  Ouvert aujourd'hui: Oui\n\n";

    // 2. Calcul des frais de port
    echo "2. Calcul des frais de port\n";
    echo "----------------------------\n";
    
    $modes = MondialRelayHelper::getDeliveryModes();
    $weight = 1500; // 1.5kg
    
    foreach ($modes as $mode => $label) {
        $cost = MondialRelayHelper::calculateShippingCost($weight, $mode);
        echo "• {$label}: {$cost}€\n";
    }
    echo "\n";

    // 3. Validation des données
    echo "3. Validation des données\n";
    echo "-------------------------\n";
    
    $postalCodes = ['75001', '7500', 'ABCDE'];
    foreach ($postalCodes as $code) {
        $isValid = MondialRelayHelper::isValidFrenchPostalCode($code);
        echo "• Code postal '{$code}': " . ($isValid ? 'Valide' : 'Invalide') . "\n";
    }
    
    $relayNumbers = ['123456', '12345', 'ABCDEF'];
    foreach ($relayNumbers as $number) {
        $isValid = MondialRelayHelper::isValidRelayNumber($number);
        echo "• Numéro relais '{$number}': " . ($isValid ? 'Valide' : 'Invalide') . "\n";
    }
    echo "\n";

    // 4. Formatage des données
    echo "4. Formatage des données\n";
    echo "------------------------\n";
    
    $weights = [500, 1000, 2500];
    foreach ($weights as $weight) {
        echo "• {$weight}g = " . MondialRelayHelper::formatWeight($weight) . "\n";
    }
    
    $distances = [0.5, 1.2, 5.8];
    foreach ($distances as $distance) {
        echo "• {$distance}km = " . MondialRelayHelper::formatDistance($distance) . "\n";
    }
    echo "\n";

    // 5. Exemple de création d'expédition (simulation)
    echo "5. Exemple de données pour création d'expédition\n";
    echo "-----------------------------------------------\n";
    
    $senderData = [
        'name' => 'Mon E-commerce',
        'company' => 'Ma Société SARL',
        'address' => '123 Rue du Commerce',
        'city' => 'Paris',
        'postal_code' => '75001',
        'country' => 'FR',
        'phone' => '01 23 45 67 89',
        'email' => 'contact@mon-ecommerce.fr',
    ];
    
    $recipientData = [
        'name' => 'Jean Dupont',
        'address' => '456 Avenue de la Paix',
        'city' => 'Lyon',
        'postal_code' => '69001',
        'country' => 'FR',
        'phone' => '09 87 65 43 21',
        'email' => 'jean.dupont@email.fr',
    ];
    
    echo "Expéditeur: {$senderData['name']}, {$senderData['city']}\n";
    echo "Destinataire: {$recipientData['name']}, {$recipientData['city']}\n";
    echo "Téléphone formaté: " . MondialRelayHelper::formatPhoneNumber($recipientData['phone']) . "\n";
    
    // Validation des paramètres d'expédition
    $expeditionParams = [
        'delivery_mode' => '24R',
        'weight' => 1000,
        'sender' => $senderData,
        'recipient' => $recipientData,
        'relay_number' => '123456',
        'relay_country' => 'FR',
    ];
    
    $errors = MondialRelayHelper::validateExpeditionParams($expeditionParams);
    echo "Validation des paramètres: " . (empty($errors) ? 'OK' : 'Erreurs: ' . implode(', ', $errors)) . "\n";
    echo "\n";

    // 6. URL de suivi
    echo "6. URL de suivi\n";
    echo "---------------\n";
    $expeditionNumber = '12345678901234';
    $trackingUrl = MondialRelayHelper::getTrackingUrl($expeditionNumber);
    echo "URL de suivi pour {$expeditionNumber}:\n{$trackingUrl}\n\n";

} catch (MondialRelayException $e) {
    echo "Erreur Mondial Relay:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Message utilisateur: " . $e->getUserMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Catégorie: " . $e->getCategory() . "\n";
    echo "Récupérable: " . ($e->isRecoverable() ? 'Oui' : 'Non') . "\n";
} catch (Exception $e) {
    echo "Erreur générale: " . $e->getMessage() . "\n";
}

echo "=== Fin de l'exemple ===\n";
