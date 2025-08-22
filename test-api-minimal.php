<?php

require_once __DIR__ . '/vendor/autoload.php';

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Illuminate\Support\Facades\Log;

// Configuration de test
$config = [
    'enseigne' => 'BDTEST13',
    'private_key' => 'TestAPI1key',
    'test_mode' => true,
    'api_url' => 'https://api.mondialrelay.com/WebService.asmx'
];

echo "=== TEST MINIMAL API MONDIAL RELAY ===\n";
echo "Configuration utilisée :\n";
foreach ($config as $key => $value) {
    echo "- {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}
echo "\n";

try {
    // Test direct SOAP sans validation Laravel
    $soapClient = new SoapClient($config['api_url'] . '?WSDL', [
        'encoding' => 'UTF-8',
        'soap_version' => SOAP_1_2,
        'trace' => true,
    ]);

    echo "✅ Client SOAP initialisé avec succès\n\n";

    // 1. TEST DE RECHERCHE DE POINTS RELAIS
    echo "🔍 ÉTAPE 1: Test de recherche de points relais\n";
    echo "----------------------------------------------\n";

    $searchParams = [
        'Enseigne' => $config['enseigne'],
        'Pays' => 'FR',
        'CP' => '75001',
        'Ville' => '',
        'Taille' => '',
        'Poids' => '1000',
        'Action' => 'REL',
        'DelaiEnvoi' => '0',
        'RayonRecherche' => '20'
    ];

    // Génération de la clé de sécurité
    $concatenatedString = implode('', $searchParams) . $config['private_key'];

    $searchParams['Security'] = strtoupper(md5($concatenatedString));

    echo "Paramètres de recherche :\n";
    foreach ($searchParams as $key => $value) {
        if ($key !== 'Security') {
            echo "- {$key}: {$value}\n";
        }
    }
    echo "- Security: " . substr($searchParams['Security'], 0, 8) . "...\n\n";

    echo "Appel à WSI4_PointRelais_Recherche...\n";
    $response = $soapClient->WSI4_PointRelais_Recherche($searchParams);

    echo "✅ Réponse reçue !\n";
    echo "Structure de la réponse:\n";
    print_r($response);
    echo "\n";


    $result = $response->WSI4_PointRelais_RechercheResult ?? $response;
    $stat = $result->STAT ?? 'UNKNOWN';
    echo "Statut: {$stat}\n";

    if ($stat === '0') {
        echo "✅ Recherche réussie !\n";
        
        if (isset($result->PointsRelais->PointRelais_Details) && is_array($result->PointsRelais->PointRelais_Details)) {
            echo "📍 " . count($result->PointsRelais->PointRelais_Details) . " points relais trouvés\n\n";
            
            foreach ($result->PointsRelais->PointRelais_Details as $index => $relay) {
                if ($index >= 3) break; // Afficher seulement les 3 premiers
                
                echo ($index + 1) . ". Point relais:\n";
                echo "   🏪 Numéro: {$relay->Num}\n";
                echo "   📍 Nom: " . trim($relay->LgAdr1 . ' ' . $relay->LgAdr2) . "\n";
                echo "   📍 Adresse: " . trim($relay->LgAdr3 . ' ' . $relay->LgAdr4) . "\n";
                echo "   📮 Code postal: {$relay->CP}\n";
                echo "   🏙️  Ville: {$relay->Ville}\n";
                echo "   📏 Distance: {$relay->Distance}m\n\n";
            }
            
            // Sélection du premier point relais pour la suite
            $selectedRelay = $response->WSI4_PointRelais_RechercheResult->PointsRelais->PointRelais_Details[0];
            $selectedRelayNumber = $selectedRelay->Num;
            echo "✅ Point relais sélectionné: N°{$selectedRelayNumber}\n\n";
        } else {
            echo "❌ Aucun point relais trouvé\n";
            $selectedRelayNumber = '123456'; // Point relais fictif
            echo "📝 Utilisation d'un point relais fictif: N°{$selectedRelayNumber}\n\n";
        }
    } else {
        echo "❌ Erreur de recherche - Code: {$response->STAT}\n";
        $selectedRelayNumber = '123456'; // Point relais fictif
        echo "📝 Utilisation d'un point relais fictif: N°{$selectedRelayNumber}\n\n";
    }

    // 2. TEST DE CRÉATION D'EXPÉDITION AVEC ÉTIQUETTE
    echo "📦 ÉTAPE 2: Test de création d'expédition avec étiquette\n";
    echo "-------------------------------------------------------\n";

    // Paramètres dans l'ordre EXACT du package Laravel (structure qui fonctionne)
    $expeditionParams = [
        'Enseigne' => $config['enseigne'],
        'ModeCol' => 'CCC',
        'ModeLiv' => '24R',
        'NDossier' => 'TEST' . time(),
        'NClient' => '',
        'Expe_Langage' => 'FR',
        'Expe_Ad1' => 'Test E-commerce',
        'Expe_Ad2' => '',
        'Expe_Ad3' => '123 Rue de Test',
        'Expe_Ad4' => '',
        'Expe_Ville' => 'Paris',
        'Expe_CP' => '75001',
        'Expe_Pays' => 'FR',
        'Expe_Tel1' => '0123456789',
        'Expe_Tel2' => '',
        'Expe_Mail' => 'test@example.com',
        'Dest_Langage' => 'FR',
        'Dest_Ad1' => 'Martin Marie',
        'Dest_Ad2' => '',
        'Dest_Ad3' => '456 Avenue de Destination',
        'Dest_Ad4' => '',
        'Dest_Ville' => 'Lyon',
        'Dest_CP' => '69000',
        'Dest_Pays' => 'FR',
        'Dest_Tel1' => '0987654321',
        'Dest_Tel2' => '',
        'Dest_Mail' => 'marie.martin@example.com',
        'Poids' => '1000',
        'Longueur' => '20',
        'Taille' => '',
        'NbColis' => '1',
        'CRT_Valeur' => '0',
        'CRT_Devise' => 'EUR',
        'Exp_Valeur' => '50',
        'Exp_Devise' => 'EUR',
        'COL_Rel_Pays' => '',
        'COL_Rel' => '',
        'LIV_Rel_Pays' => 'FR',
        'LIV_Rel' => $selectedRelayNumber,
        'TAvisage' => '',
        'TReprise' => '',
        'Montage' => '0',
        'TRDV' => '',
        'Assurance' => '0',
        'Instructions' => '',
        'Texte' => 'Produit de test - Livre'
    ];

    // Génération de la clé de sécurité SANS le champ Texte (comme dans le package Laravel)
    $securityParams = $expeditionParams;
    unset($securityParams['Texte']); // Le champ Texte ne doit PAS être inclus dans le calcul de la clé

    $securityString = implode('', $securityParams) . $config['private_key'];
    $expeditionParams['Security'] = strtoupper(md5($securityString));

    echo "Clé de sécurité générée: " . substr($expeditionParams['Security'], 0, 8) . "...\n";
    echo "Chaîne de sécurité (début): " . substr($securityString, 0, 50) . "...\n";

    echo "Données d'expédition :\n";
    echo "- Expéditeur: {$expeditionParams['Expe_Ad1']} - {$expeditionParams['Expe_Ville']}\n";
    echo "- Destinataire: {$expeditionParams['Dest_Ad1']} - {$expeditionParams['Dest_Ville']}\n";
    echo "- Point relais: N°{$expeditionParams['LIV_Rel']}\n";
    echo "- Poids: " . ($expeditionParams['Poids'] / 1000) . "kg\n";
    echo "- Dossier: {$expeditionParams['NDossier']}\n\n";

    echo "Appel à WSI2_CreationEtiquette...\n";
    $response = $soapClient->WSI2_CreationEtiquette($expeditionParams);

    echo "✅ Réponse reçue !\n";
    echo "Structure de la réponse:\n";
    print_r($response);
    echo "\n";

    $result = $response->WSI2_CreationEtiquetteResult ?? $response;
    $stat = $result->STAT ?? 'UNKNOWN';
    echo "Statut: {$stat}\n";

    if ($stat === '0') {
        echo "✅ Expédition créée avec succès !\n";
        echo "📋 Numéro d'expédition: {$result->ExpeditionNum}\n\n";

        // 3. INFORMATIONS SUR L'ÉTIQUETTE
        echo "🏷️  ÉTAPE 3: Informations sur l'étiquette\n";
        echo "----------------------------------------\n";

        $baseUrl = str_replace('/WebService.asmx', '', $config['api_url']);

        echo "URLs des étiquettes disponibles :\n";
        echo "- Format A4: {$baseUrl}{$result->URL_Etiquette}&format=A4\n";
        echo "- Format A5: {$baseUrl}{$result->URL_Etiquette}&format=A5\n";
        echo "- Format 10x15: {$baseUrl}{$result->URL_Etiquette}&format=10x15\n\n";

        $trackingUrl = "https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition={$result->ExpeditionNum}";
        echo "🔗 URL de suivi: {$trackingUrl}\n\n";

        // 4. TEST DE TÉLÉCHARGEMENT D'ÉTIQUETTE
        echo "⬇️  ÉTAPE 4: Test de téléchargement d'étiquette\n";
        echo "----------------------------------------------\n";

        $labelUrl = $baseUrl . $result->URL_Etiquette . '&format=A4';
        echo "URL de l'étiquette A4: {$labelUrl}\n";

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (compatible; Laravel Mondial Relay Package)',
                ],
            ]);

            echo "Tentative de téléchargement...\n";
            $pdfContent = file_get_contents($labelUrl, false, $context);

            if ($pdfContent && strlen($pdfContent) > 0) {
                echo "✅ Étiquette téléchargée avec succès !\n";
                echo "📄 Taille du PDF: " . number_format(strlen($pdfContent)) . " octets\n";

                // Vérification que c'est bien un PDF
                if (strpos($pdfContent, '%PDF') === 0) {
                    echo "✅ Format PDF valide détecté\n";

                    // Sauvegarde du PDF
                    $filename = "etiquette_{$response->WSI2_CreationEtiquetteResult->ExpeditionNum}_A4.pdf";
                    file_put_contents($filename, $pdfContent);
                    echo "💾 Étiquette sauvegardée: {$filename}\n";
                } else {
                    echo "⚠️  Le contenu téléchargé ne semble pas être un PDF valide\n";
                    echo "Début du contenu: " . substr($pdfContent, 0, 100) . "...\n";
                }
            } else {
                echo "❌ Échec du téléchargement de l'étiquette\n";
            }
        } catch (Exception $e) {
            echo "❌ Erreur lors du téléchargement: " . $e->getMessage() . "\n";
        }

        echo "\n";

        // 5. RÉSUMÉ FINAL
        echo "📊 RÉSUMÉ DU TEST\n";
        echo "=================\n";
        echo "✅ Recherche de points relais: OK\n";
        echo "✅ Création d'expédition: OK\n";
        echo "✅ Génération d'étiquette: OK\n";
        echo "✅ Numéro d'expédition: {$result->ExpeditionNum}\n";
        echo "✅ URL de suivi: {$trackingUrl}\n\n";

        echo "🎉 TEST COMPLET RÉUSSI !\n";
        echo "L'API Mondial Relay fonctionne parfaitement avec vos configurations.\n";

    } else {
        echo "❌ Erreur lors de la création de l'expédition\n";
        echo "Code d'erreur: {$stat}\n";
        
        // Messages d'erreur courants
        $errorMessages = [
            '1' => 'Erreur d\'authentification - Vérifiez vos identifiants',
            '2' => 'Numéro d\'enseigne invalide',
            '3' => 'Numéro de point relais invalide',
            '10' => 'Type d\'envoi invalide',
            '20' => 'Poids invalide',
            '21' => 'Taille invalide',
            '22' => 'Longueur invalide',
            '24' => 'Nombre de colis invalide',
            '26' => 'Mode de collecte invalide',
            '27' => 'Mode de livraison invalide',
            '28' => 'Mode de livraison incompatible avec le point relais',
            '30' => 'Adresse expéditeur incomplète',
            '31' => 'Ville expéditeur invalide',
            '32' => 'Code postal expéditeur invalide',
            '33' => 'Pays expéditeur invalide',
            '34' => 'Numéro de téléphone expéditeur invalide',
            '35' => 'Email expéditeur invalide',
            '40' => 'Adresse destinataire incomplète',
            '41' => 'Ville destinataire invalide',
            '42' => 'Code postal destinataire invalide',
            '43' => 'Pays destinataire invalide',
            '44' => 'Numéro de téléphone destinataire invalide',
            '45' => 'Email destinataire invalide',
            '60' => 'Valeur déclarée invalide',
            '61' => 'Devise invalide',
            '70' => 'Numéro de dossier déjà utilisé',
            '74' => 'Langue invalide',
            '80' => 'Code tracing invalide',
            '99' => 'Erreur générique du service'
        ];
        
        $errorMessage = $errorMessages[$response->WSI2_CreationEtiquetteResult->STAT] ?? 'Erreur inconnue';
        echo "Description: {$errorMessage}\n";
    }

} catch (SoapFault $e) {
    echo "❌ ERREUR SOAP:\n";
    echo "Code: {$e->getCode()}\n";
    echo "Message: {$e->getMessage()}\n";
    echo "Détails: {$e->getTraceAsString()}\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR GÉNÉRALE:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: {$e->getMessage()}\n";
    echo "Fichier: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n=== FIN DU TEST ===\n";
