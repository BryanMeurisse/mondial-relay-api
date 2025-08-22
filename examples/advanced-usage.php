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

// Initialisation du client et service
$client = new MondialRelayClient($enseigne, $privateKey, $testMode);
$service = new MondialRelayService($client);

echo "=== Exemple avancé d'utilisation du package Mondial Relay API ===\n\n";

try {
    // Données d'exemple
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

    // 1. Recherche de points relais pour un envoi spécifique
    echo "1. Recherche de points relais pour un colis de 1.5kg\n";
    echo "---------------------------------------------------\n";

    // Note: En mode démonstration, nous simulons la recherche
    echo "Note: Cet exemple simule les appels API (mode démonstration)\n";
    echo "Dans un environnement Laravel réel, utilisez les facades MondialRelay ou MondialRelayService\n\n";

    // Simulation de points relais pour la démonstration
    echo "Exemple de points relais trouvés: 3\n";
    echo "1. Tabac de la Gare\n";
    echo "   Adresse: 123 Rue de la République, 69001 Lyon\n";
    echo "   Distance: 0.5km\n";
    echo "   Numéro: 123456\n";
    echo "   Ouvert aujourd'hui: Oui\n\n";

    echo "2. Épicerie du Coin\n";
    echo "   Adresse: 456 Avenue Jean Jaurès, 69001 Lyon\n";
    echo "   Distance: 0.8km\n";
    echo "   Numéro: 789012\n";
    echo "   Ouvert aujourd'hui: Oui\n\n";

    echo "3. Pressing Central\n";
    echo "   Adresse: 789 Place Bellecour, 69001 Lyon\n";
    echo "   Distance: 1.2km\n";
    echo "   Numéro: 345678\n";
    echo "   Ouvert aujourd'hui: Non\n\n";

    // Simulation d'un point relais sélectionné
    $selectedRelayNumber = '123456';

    // 2. Simulation de création d'expédition avec étiquette
    echo "2. Simulation de création d'expédition avec étiquette PDF\n";
    echo "--------------------------------------------------------\n";
    
    // Note: En mode simulation, nous ne créons pas réellement l'expédition
    echo "Données de l'expédition:\n";
    echo "- Expéditeur: {$senderData['name']}, {$senderData['city']}\n";
    echo "- Destinataire: {$recipientData['name']}, {$recipientData['city']}\n";
    echo "- Poids: " . MondialRelayHelper::formatWeight(1500) . "\n";
    echo "- Mode de livraison: " . MondialRelayHelper::getDeliveryModeLabel('24R') . "\n";
    
    echo "- Point relais sélectionné: Tabac de la Gare ({$selectedRelayNumber})\n";

    // Validation des paramètres
    $expeditionParams = [
        'delivery_mode' => '24R',
        'weight' => 1500,
        'sender' => $senderData,
        'recipient' => $recipientData,
        'relay_number' => $selectedRelayNumber,
        'relay_country' => 'FR',
    ];

    $errors = MondialRelayHelper::validateExpeditionParams($expeditionParams);
    echo "- Validation: " . (empty($errors) ? '✓ OK' : '✗ Erreurs: ' . implode(', ', $errors)) . "\n";

    if (empty($errors)) {
        echo "- Coût estimé: " . MondialRelayHelper::calculateShippingCost(1500, '24R') . "€\n";
        echo "\n";
        echo "En mode réel, cette expédition créerait:\n";
        echo "  • Un numéro d'expédition unique\n";
        echo "  • Des URLs d'étiquettes PDF en formats A4, A5 et 10x15\n";
        echo "  • La possibilité de télécharger directement les étiquettes\n";
    }
    echo "\n";

    // 3. Gestion des formats d'étiquettes
    echo "3. Formats d'étiquettes disponibles\n";
    echo "-----------------------------------\n";
    
    $formats = ['A4', 'A5', '10x15'];
    foreach ($formats as $format) {
        echo "• Format {$format}: ";
        switch ($format) {
            case 'A4':
                echo "210 × 297 mm - Idéal pour impression bureau\n";
                break;
            case 'A5':
                echo "148 × 210 mm - Format compact\n";
                break;
            case '10x15':
                echo "10 × 15 cm - Idéal pour étiquettes adhésives\n";
                break;
        }
    }
    echo "\n";

    // 4. Simulation de gestion d'étiquettes en lot
    echo "4. Simulation de gestion d'étiquettes en lot\n";
    echo "--------------------------------------------\n";
    
    $simulatedExpeditions = [
        '12345678901234',
        '56789012345678',
        '90123456789012'
    ];
    
    echo "Expéditions à traiter: " . count($simulatedExpeditions) . "\n";
    foreach ($simulatedExpeditions as $index => $expeditionNumber) {
        echo ($index + 1) . ". Expédition: {$expeditionNumber}\n";
        echo "   URL de suivi: " . MondialRelayHelper::getTrackingUrl($expeditionNumber) . "\n";
    }
    
    echo "\nEn mode réel, vous pourriez:\n";
    echo "• Récupérer toutes les étiquettes en un seul PDF\n";
    echo "• Choisir le format (A4, A5, 10x15)\n";
    echo "• Télécharger directement le fichier PDF\n";
    echo "• Vérifier quelles expéditions sont incluses dans le lot\n";
    echo "\n";

    // 5. Utilitaires de validation et formatage
    echo "5. Utilitaires de validation et formatage\n";
    echo "-----------------------------------------\n";
    
    $testData = [
        'codes_postaux' => ['75001', '69000', '1234', 'ABCDE'],
        'numeros_relais' => ['123456', '000001', '12345', 'ABCDEF'],
        'poids' => [500, 1000, 2500, 5000],
        'distances' => [0.3, 1.2, 5.8, 12.5],
        'telephones' => ['01 23 45 67 89', '0123456789', '123456789', '01.23.45.67.89'],
    ];
    
    echo "Validation des codes postaux:\n";
    foreach ($testData['codes_postaux'] as $code) {
        $isValid = MondialRelayHelper::isValidFrenchPostalCode($code);
        echo "  • '{$code}': " . ($isValid ? '✓ Valide' : '✗ Invalide') . "\n";
    }
    
    echo "\nValidation des numéros de relais:\n";
    foreach ($testData['numeros_relais'] as $numero) {
        $isValid = MondialRelayHelper::isValidRelayNumber($numero);
        echo "  • '{$numero}': " . ($isValid ? '✓ Valide' : '✗ Invalide') . "\n";
    }
    
    echo "\nFormatage des poids:\n";
    foreach ($testData['poids'] as $poids) {
        echo "  • {$poids}g = " . MondialRelayHelper::formatWeight($poids) . "\n";
    }
    
    echo "\nFormatage des distances:\n";
    foreach ($testData['distances'] as $distance) {
        echo "  • {$distance}km = " . MondialRelayHelper::formatDistance($distance) . "\n";
    }
    
    echo "\nFormatage des téléphones:\n";
    foreach ($testData['telephones'] as $telephone) {
        $formatted = MondialRelayHelper::formatPhoneNumber($telephone);
        echo "  • '{$telephone}' → '{$formatted}'\n";
    }
    echo "\n";

    // 6. Calcul des coûts de livraison
    echo "6. Calcul des coûts de livraison\n";
    echo "--------------------------------\n";
    
    $weights = [500, 1000, 1500, 2500];
    $modes = ['24R', '24L', '24X', 'LD1'];
    
    echo "Tarifs estimés (en €):\n";
    echo sprintf("%-8s", "Poids");
    foreach ($modes as $mode) {
        echo sprintf("%-8s", $mode);
    }
    echo "\n";
    
    foreach ($weights as $weight) {
        echo sprintf("%-8s", MondialRelayHelper::formatWeight($weight));
        foreach ($modes as $mode) {
            $cost = MondialRelayHelper::calculateShippingCost($weight, $mode);
            echo sprintf("%-8s", number_format($cost, 2) . '€');
        }
        echo "\n";
    }

} catch (MondialRelayException $e) {
    echo "\n❌ Erreur Mondial Relay:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Message utilisateur: " . $e->getUserMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Catégorie: " . $e->getCategory() . "\n";
    echo "Récupérable: " . ($e->isRecoverable() ? 'Oui' : 'Non') . "\n";
} catch (Exception $e) {
    echo "\n❌ Erreur générale: " . $e->getMessage() . "\n";
}

echo "\n=== Fin de l'exemple avancé ===\n";
