<?php
/**
 * Nom du script : _chek_bann_pro.php
 * Révision     : v1.1 - Prise en charge de la variable de connexion $bdd
 * NOM DU SCRIPT : partials/_chek_bann_pro.php
 * DESCRIPTION  : Nettoyage automatique en arrière-plan des bannières Pro expirées.
 *                Supprime les enregistrements de 'jevend_bannieres_actives_pro'
 *                dès que 'date_fin' est égale ou antérieure à la date/heure actuelle (NOW()).
 */

// Récupération automatique de la variable de connexion ($bdd est la variable officielle dans config.php)
$db_handle = $bdd ?? $pdo ?? $conn ?? null;

if ($db_handle) {
    try {
        if ($db_handle instanceof PDO) {
            $sql = "DELETE FROM jevend_bannieres_actives_pro WHERE date_fin <= NOW()";
            $stmt = $db_handle->prepare($sql);
            $stmt->execute();
        } elseif ($db_handle instanceof mysqli) {
            $sql = "DELETE FROM jevend_bannieres_actives_pro WHERE date_fin <= NOW()";
            $db_handle->query($sql);
        }
    } catch (Throwable $e) {
        // Enregistre l'erreur silencieusement dans le log du serveur pour ne pas bloquer la page
        error_log("Erreur de nettoyage (_chek_bann_pro.php) : " . $e->getMessage());
    }
}
?>
