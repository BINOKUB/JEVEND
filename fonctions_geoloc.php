<?php
// =============================================================================
// SCRIPT : fonctions_geoloc.php
// REVISION : 1.2 - Calculateur Haversine Modulaire (Logique stricte Vendeur/Acheteur)
// =============================================================================

/**
 * Calcule la distance en kilomètres entre deux villes à partir de leurs IDs
 */
function calculerDistanceVilles($bdd, $id_ville_1, $id_ville_2) {
    if ($id_ville_1 == $id_ville_2) {
        return 0.0;
    }

    try {
        $stmt = $bdd->prepare("SELECT id_ville, latitude, longitude FROM jevend_villes WHERE id_ville IN (?, ?)");
        $stmt->execute([$id_ville_1, $id_ville_2]);
        $villes = $stmt->fetchAll(PDO::FETCH_UNIQUE);

        if (!isset($villes[$id_ville_1]) || !isset($villes[$id_ville_2])) {
            return null;
        }

        $lat1 = deg2rad((float)$villes[$id_ville_1]['latitude']);
        $lon1 = deg2rad((float)$villes[$id_ville_1]['longitude']);
        $lat2 = deg2rad((float)$villes[$id_ville_2]['latitude']);
        $lon2 = deg2rad((float)$villes[$id_ville_2]['longitude']);

        $vitesse_terre = 6371; // Rayon de la Terre en km
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $vitesse_terre * $c;

    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Génère le libellé de localisation intelligent selon l'acheteur connecté et l'auteur de l'annonce
 */
function obtenirTexteDistance($bdd, $id_ville_acheteur, $id_ville_vendeur, $nom_ville_vendeur, $id_auteur_annonce, $id_utilisateur_connecte) {
    
    // CAS 1 : C'est la propre annonce du membre connecté (ZOU regarde l'annonce de ZOU)
    if (!empty($id_utilisateur_connecte) && $id_utilisateur_connecte == $id_auteur_annonce) {
        return "📍 " . htmlspecialchars($nom_ville_vendeur);
    }

    // CAS 2 : L'acheteur n'est pas connecté (visiteur anonyme)
    if (empty($id_ville_acheteur)) {
        return "📍 " . htmlspecialchars($nom_ville_vendeur);
    }

    // CAS 3 : Un autre membre de la MÊME ville vend un objet (ZOU regarde JEAN à Matane)
    if ($id_ville_acheteur == $id_ville_vendeur) {
        return "📍 Proche de chez vous (" . htmlspecialchars($nom_ville_vendeur) . ")";
    }

    // CAS 4 : Le vendeur est dans une autre ville (ZOU regarde TARGO à Causapscal)
    $distance = calculerDistanceVilles($bdd, $id_ville_acheteur, $id_ville_vendeur);

    if ($distance === null) {
        return "📍 " . htmlspecialchars($nom_ville_vendeur);
    }

    if ($distance < 1) {
        return "📍 À moins de 1 km de chez vous";
    }
    
    return "📍 À " . round($distance) . " km (" . htmlspecialchars($nom_ville_vendeur) . ")";
}
