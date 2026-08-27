<?php
// =============================================================================
// SCRIPT      : api_vitrine.php
// PROJET      : JEVEND | BRANCHE : main
// REVISION    : 1.2 | AUTEUR : Dan | DATE : 2026-08-27
// DESC        : Filtrage ville par JOIN (annonces -> utilisateurs -> villes)
// =============================================================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

require_once 'config.php';

$format = $_GET['format'] ?? 'leaderboard';
$token  = trim($_GET['token'] ?? '');
$limit  = (int)($_GET['limit'] ?? 4);
if ($limit < 1 || $limit > 10) { $limit = 4; }

// --- 1. RÉCUPÉRATION DE LA VILLE DE FILTRAGE VIA LE TOKEN PARTENAIRE ---
$ville_filtre = '';

if (!empty($token)) {
    try {
        $stmt_partenaire = $bdd->prepare("
            SELECT ville_filtre 
            FROM jevend_annuaire_partenaire 
            WHERE widget_token = ? 
            LIMIT 1
        ");
        $stmt_partenaire->execute([$token]);
        $partenaire = $stmt_partenaire->fetch(PDO::FETCH_ASSOC);

        if ($partenaire && !empty($partenaire['ville_filtre'])) {
            $ville_filtre = trim($partenaire['ville_filtre']);
        }
    } catch (PDOException $e) {
        // En cas d'erreur SQL, on poursuit sans filtre
    }
}

// --- 2. GESTION DU CACHE UNIQUE ---
$cache_dir = __DIR__ . '/widgets_cache/';
if (!is_dir($cache_dir)) { 
    @mkdir($cache_dir, 0777, true); 
}

$cache_file = $cache_dir . 'widget_' . md5($token . $format . $limit . $ville_filtre) . '.json';

if (file_exists($cache_file) && (time() - filemtime($cache_file) < 1800)) {
    echo file_get_contents($cache_file);
    exit;
}

// --- 3. REQUÊTE SQL AVEC JOINTURE POUR EXTRAIRE LA VILLE ---
try {
    if (!empty($ville_filtre)) {
        // Requête filtrée via la ville du membre qui a publié l'annonce
        $stmt = $bdd->prepare("
            SELECT 
                a.id_annonces, 
                a.titre_objet_nettoye, 
                a.prix, 
                a.image_courante,
                v.nom_ville
            FROM jevend_annonces a
            INNER JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
            INNER JOIN jevend_villes v ON u.id_ville = v.id_ville
            WHERE a.statut = 'actif' 
              AND LOWER(v.nom_ville) = LOWER(?) 
            ORDER BY a.date_creation DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $ville_filtre, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback : Si 0 annonce trouvée pour cette ville, on charge les plus récentes globales
        if (empty($annonces)) {
            $stmt_fallback = $bdd->prepare("
                SELECT 
                    a.id_annonces, 
                    a.titre_objet_nettoye, 
                    a.prix, 
                    a.image_courante,
                    v.nom_ville
                FROM jevend_annonces a
                INNER JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
                INNER JOIN jevend_villes v ON u.id_ville = v.id_ville
                WHERE a.statut = 'actif' 
                ORDER BY a.date_creation DESC 
                LIMIT ?
            ");
            $stmt_fallback->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt_fallback->execute();
            $annonces = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // Requête globale sans filtre de ville
        $stmt = $bdd->prepare("
            SELECT 
                a.id_annonces, 
                a.titre_objet_nettoye, 
                a.prix, 
                a.image_courante,
                v.nom_ville
            FROM jevend_annonces a
            INNER JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
            INNER JOIN jevend_villes v ON u.id_ville = v.id_ville
            WHERE a.statut = 'actif' 
            ORDER BY a.date_creation DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $annonces = [];
}

$html_output = '';

if (empty($annonces)) {
    $html_output = '<p style="font-size: 0.85rem; color: #64748b; text-align: center; padding: 10px;">Aucune annonce active pour le moment.</p>';
} else {

    // --- 1. LEADERBOARD (728 x 90 px) ---
    if ($format === 'leaderboard') {
        $html_output .= '<div style="font-family: Arial, sans-serif; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; max-width: 100%; overflow-x: auto; display: flex; gap: 10px; align-items: center;">';
        $html_output .= '<div style="font-size: 0.75rem; font-weight: bold; color: #64748b; writing-mode: vertical-lr; text-align: center;">JEVEND</div>';
        foreach ($annonces as $a) {
            $lien = 'vitrine_clic.php?id=' . $a['id_annonces'] . '&token=' . urlencode($token);
            $img = !empty($a['image_courante']) ? 'uploads/' . htmlspecialchars($a['image_courante']) : 'uploads/default.jpg';
            $prix = !empty($a['prix']) ? number_format($a['prix'], 2, ',', ' ') . ' $' : '';
            
            $html_output .= '<a href="'.$lien.'" target="_blank" style="display: flex; align-items: center; gap: 8px; text-decoration: none; background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px; border-radius: 4px; flex: 1; min-width: 180px;">
                <img src="'.$img.'" style="width: 45px; height: 45px; object-fit: cover; border-radius: 3px;" onerror="this.style.display=\'none\'">
                <div style="overflow: hidden;">
                    <div style="font-size: 0.78rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.htmlspecialchars($a['titre_objet_nettoye']).'</div>
                    <div style="font-size: 0.72rem; color: #16a34a; font-weight: bold;">'.$prix.'</div>
                </div>
            </a>';
        }
        $html_output .= '</div>';
    }

    // --- 2. RECTANGLE (300 x 250) & GRAND RECTANGLE (336 x 280) ---
    elseif ($format === 'rectangle' || $format === 'grand_rectangle') {
        $max_w = ($format === 'grand_rectangle') ? '336px' : '300px';
        $html_output .= '<div style="font-family: Arial, sans-serif; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; width: 100%; max-width: '.$max_w.'; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
        
        $html_output .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">';
        $html_output .= '<span style="font-size: 0.82rem; font-weight: bold; color: #1e3a8a;">🛒 Annonces Locales</span>';
        $html_output .= '<a href="#" target="_blank" style="font-size: 0.68rem; color: #2563eb; text-decoration: none;">Jevend.com ↗</a>';
        $html_output .= '</div>';

        $html_output .= '<div style="display: flex; flex-direction: column; gap: 8px;">';
        foreach ($annonces as $a) {
            $lien = 'vitrine_clic.php?id=' . $a['id_annonces'] . '&token=' . urlencode($token);
            $img = !empty($a['image_courante']) ? 'uploads/' . htmlspecialchars($a['image_courante']) : 'uploads/default.jpg';
            $prix = !empty($a['prix']) ? number_format($a['prix'], 2, ',', ' ') . ' $' : 'Prix libre';

            $html_output .= '
            <a href="'.$lien.'" target="_blank" style="display: flex; gap: 8px; text-decoration: none; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px; border-radius: 6px; align-items: center;">
                <div style="width: 45px; height: 45px; flex-shrink: 0; background: #cbd5e1; border-radius: 4px; overflow: hidden;">
                    <img src="'.$img.'" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\'none\'">
                </div>
                <div style="flex: 1; overflow: hidden;">
                    <div style="font-size: 0.78rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;">'.htmlspecialchars($a['titre_objet_nettoye']).'</div>
                    <div style="font-size: 0.72rem; color: #16a34a; font-weight: bold;">'.$prix.'</div>
                </div>
            </a>';
        }
        $html_output .= '</div>';

        $html_output .= '<div style="text-align: right; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 4px;">';
        $html_output .= '<a href="#" target="_blank" style="font-size: 0.65rem; color: #94a3b8; text-decoration: none;">Propulsé par Jevend.com</a>';
        $html_output .= '</div>';

        $html_output .= '</div>';
    }

    // --- 3. SKYSCRAPER, DEMI-PAGE & SIDEBAR ---
    elseif ($format === 'skyscraper' || $format === 'demi_page' || $format === 'sidebar') {
        $largeur_max = ($format === 'skyscraper') ? '160px' : (($format === 'demi_page') ? '300px' : '260px');
        
        $html_output .= '<div style="font-family: Arial, sans-serif; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; width: 100%; max-width: '.$largeur_max.'; box-sizing: border-box; display: flex; flex-direction: column; gap: 10px;">';
        $html_output .= '<div style="font-size: 0.78rem; font-weight: bold; color: #1e3a8a; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px; text-align: center;">🛒 Annonces Locales</div>';
        
        foreach ($annonces as $a) {
            $lien = 'vitrine_clic.php?id=' . $a['id_annonces'] . '&token=' . urlencode($token);
            $img = !empty($a['image_courante']) ? 'uploads/' . htmlspecialchars($a['image_courante']) : 'uploads/default.jpg';
            $prix = !empty($a['prix']) ? number_format($a['prix'], 2, ',', ' ') . ' $' : 'Prix libre';

            $html_output .= '
            <a href="'.$lien.'" target="_blank" style="display: flex; flex-direction: column; text-decoration: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; padding: 8px;">
                <div style="width: 100%; height: 90px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-bottom: 6px;">
                    <img src="'.$img.'" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\'none\'">
                </div>
                <div style="font-size: 0.75rem; font-weight: bold; color: #1e3a8a; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">'.htmlspecialchars($a['titre_objet_nettoye']).'</div>
                <div style="font-size: 0.75rem; color: #16a34a; font-weight: bold; margin-top: auto;">'.$prix.'</div>
            </a>';
        }
        $html_output .= '<div style="text-align: center; margin-top: 4px;"><a href="http://jevend.com" target="_blank" style="font-size: 0.65rem; color: #94a3b8; text-decoration: none;">Propulsé par Jevend</a></div>';
        $html_output .= '</div>';
    }

    // --- 4. BANNIÈRE MOBILE (320 x 50) ---
    elseif ($format === 'banniere_mobile') {
        $a = $annonces[0];
        $lien = 'vitrine_clic.php?id=' . $a['id_annonces'] . '&token=' . urlencode($token);
        $img = !empty($a['image_courante']) ? 'uploads/' . htmlspecialchars($a['image_courante']) : 'uploads/default.jpg';
        $prix = !empty($a['prix']) ? number_format($a['prix'], 2, ',', ' ') . ' $' : '';

        $html_output .= '<a href="'.$lien.'" target="_blank" style="display: flex; align-items: center; gap: 10px; text-decoration: none; background: #0f172a; color: #ffffff; padding: 5px 10px; border-radius: 4px; width: 100%; max-width: 320px; box-sizing: border-box;">
            <img src="'.$img.'" style="width: 38px; height: 38px; object-fit: cover; border-radius: 3px;" onerror="this.style.display=\'none\'">
            <div style="flex: 1; overflow: hidden;">
                <div style="font-size: 0.75rem; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.htmlspecialchars($a['titre_objet_nettoye']).'</div>
                <div style="font-size: 0.7rem; color: #00f3ff; font-weight: bold;">'.$prix.'</div>
            </div>
        </a>';
    }
}

// Sauvegarde dans le cache
@file_put_contents($cache_file, $html_output);
@chmod($cache_file, 0777);

echo $html_output;
exit;
