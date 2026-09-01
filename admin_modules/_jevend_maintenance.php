<?php
// =============================================================================
// NOM DU SCRIPT : _jevend_maintenance.php
// REVISION     : 2.0 - Déverrouillage Automatique selon l'horaire
// =============================================================================

if (!isset($bdd)) {
    return;
}

try {
    $stmt_maint = $bdd->prepare("
        SELECT cle_parametre, valeur_parametre 
        FROM jevend_parametres 
        WHERE cle_parametre IN ('maintenance_actif', 'maintenance_heure_ouverture', 'maintenance_message')
    ");
    $stmt_maint->execute();
    $params_maint = $stmt_maint->fetchAll(PDO::FETCH_KEY_PAIR);

    $est_en_maintenance = ($params_maint['maintenance_actif'] ?? '0') === '1';

    if ($est_en_maintenance) {
        $heure_cible_brute = trim($params_maint['maintenance_heure_ouverture'] ?? '');
        $maintenant = new DateTime('now', new DateTimeZone('America/Montreal'));
        $ouvert_automatiquement = false;

        if (!empty($heure_cible_brute)) {
            // Cas 1 : Format "HH:MM" (ex: 14:00) -> réouverture aujourd'hui à cette heure
            if (preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $heure_cible_brute)) {
                $heure_cible = DateTime::createFromFormat('Y-m-d H:i', $maintenant->format('Y-m-d') . ' ' . $heure_cible_brute, new DateTimeZone('America/Montreal'));
                if ($heure_cible && $maintenant >= $heure_cible) {
                    $ouvert_automatiquement = true;
                }
            } 
            // Cas 2 : Format complet "YYYY-MM-DD HH:MM" (ex: 2026-08-31 14:00)
            else {
                $heure_cible = DateTime::createFromFormat('Y-m-d H:i', $heure_cible_brute, new DateTimeZone('America/Montreal'));
                if ($heure_cible && $maintenant >= $heure_cible) {
                    $ouvert_automatiquement = true;
                }
            }
        }

        // Si l'heure est atteinte, on réouvre automatiquement le site et on met à jour la BDD
        if ($ouvert_automatiquement) {
            $bdd->query("UPDATE jevend_parametres SET valeur_parametre = '0' WHERE cle_parametre = 'maintenance_actif'");
            $est_en_maintenance = false;
        }
    }

    // Blocage si toujours en maintenance et utilisateur non-admin
    if ($est_en_maintenance) {
        $est_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

        if (!$est_admin) {
            $heure_ouverture = htmlspecialchars($params_maint['maintenance_heure_ouverture'] ?? 'Bientôt');
            $message_maintenance = htmlspecialchars($params_maint['maintenance_message'] ?? 'Maintenance en cours.');
            
            include_once 'maintenance.php';
            exit();
        }
    }
} catch (Exception $e) {
    // Silencieux en dev/prod
}
