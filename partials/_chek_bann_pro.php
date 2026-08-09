<?php
/**
 * Nom du script : _chek_bann_pro.php
 * Révision     : v1.2 - Suppression physique des images associées avant l'effacement BDD
 * DESCRIPTION  : Nettoyage automatique en arrière-plan des bannières Pro expirées.
 *                Supprime les fichiers physiques dans uploads/bannieres/ puis 
 *                nettoie les enregistrements de 'jevend_bannieres_actives_pro'.
 */

// Récupération automatique de la variable de connexion
$db_handle = $bdd ?? $pdo ?? $conn ?? null;

if ($db_handle) {
    try {
        if ($db_handle instanceof PDO) {
            // 1. Récupérer les chemins des images des bannières arrivées à expiration
            $stmt_select = $db_handle->prepare("SELECT id_banniere_pro, image_url FROM jevend_bannieres_actives_pro WHERE date_fin <= NOW()");
            $stmt_select->execute();
            $bannieres_expirees = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($bannieres_expirees)) {
                foreach ($bannieres_expirees as $bann) {
                    $chemin_relatif = trim($bann['image_url'] ?? '');
                    if (!empty($chemin_relatif)) {
                        // Test multi-chemins pour s'assurer de trouver le fichier peu importe d'où est inclus le script
                        $chemins_possibles = [
                            __DIR__ . '/../' . $chemin_relatif, // Si le script est dans /partials/
                            __DIR__ . '/' . $chemin_relatif,   // Si le script est à la racine
                            $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($chemin_relatif, '/') // Chemin absolu web
                        ];

                        foreach ($chemins_possibles as $chemin_physique) {
                            if (file_exists($chemin_physique) && is_file($chemin_physique)) {
                                @unlink($chemin_physique);
                                break;
                            }
                        }
                    }
                }

                // 2. Supprimer les enregistrements de la base de données une fois les fichiers purgés
                $sql = "DELETE FROM jevend_bannieres_actives_pro WHERE date_fin <= NOW()";
                $stmt = $db_handle->prepare($sql);
                $stmt->execute();
            }
        } elseif ($db_handle instanceof mysqli) {
            // Prise en charge alternative MySQLi
            $result = $db_handle->query("SELECT id_banniere_pro, image_url FROM jevend_bannieres_actives_pro WHERE date_fin <= NOW()");
            if ($result) {
                while ($bann = $result->fetch_assoc()) {
                    $chemin_relatif = trim($bann['image_url'] ?? '');
                    if (!empty($chemin_relatif)) {
                        $chemins_possibles = [
                            __DIR__ . '/../' . $chemin_relatif,
                            __DIR__ . '/' . $chemin_relatif,
                            $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($chemin_relatif, '/')
                        ];

                        foreach ($chemins_possibles as $chemin_physique) {
                            if (file_exists($chemin_physique) && is_file($chemin_physique)) {
                                @unlink($chemin_physique);
                                break;
                            }
                        }
                    }
                }
            }
            $sql = "DELETE FROM jevend_bannieres_actives_pro WHERE date_fin <= NOW()";
            $db_handle->query($sql);
        }
    } catch (Throwable $e) {
        // Enregistre l'erreur silencieusement dans le log du serveur pour ne pas bloquer l'affichage
        error_log("Erreur de nettoyage des bannières (_chek_bann_pro.php) : " . $e->getMessage());
    }
}
?>
