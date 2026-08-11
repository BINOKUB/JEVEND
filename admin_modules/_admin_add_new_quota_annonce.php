<?php
// Script : _admin_add_new_quota_annonce.php
// Objectif : Incrémenter ou créer le compteur d'annonces pour la date du jour
try {
    $date_du_jour = date('Y-m-d');
    $stmt_quota = $bdd->prepare("
        INSERT INTO jevend_quota_annonces (date_jour, nombre_annonces) 
        VALUES (?, 1) 
        ON DUPLICATE KEY UPDATE nombre_annonces = nombre_annonces + 1
    ");
    $stmt_quota->execute([$date_du_jour]);
} catch (PDOException $e) {
    // Gestion silencieuse pour ne pas perturber l'expérience utilisateur en cas de pépin
}
?>
