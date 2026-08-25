<?php
// =============================================================================
// SCRIPT      : admin_modules/_vitrine_admin.php
// REVISION    : 3.1 - Gestion complète avec formats élargis et rafraîchissement de cache ciblé
// =============================================================================

if (!defined('ROOT_DIR') && basename($_SERVER['SCRIPT_FILENAME']) === '_vitrine_admin.php') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

$message_admin = "";
$type_message = "";

// Traitement des actions (Ajout d'un partenaire, suppression ou vidage de cache ciblé)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_vitrine'] ?? '';

    // 1. Ajouter un nouveau partenaire
    if ($action === 'ajouter_partenaire') {
        $nom_partenaire = trim($_POST['nom_partenaire'] ?? '');
        $nom_contact    = trim($_POST['nom_contact'] ?? '');
        $courriel       = trim($_POST['courriel'] ?? '');
        $url_site       = trim($_POST['url_site'] ?? '');
        $format_widget  = trim($_POST['format_widget'] ?? 'sidebar');
        $ville_filtre   = trim($_POST['ville_filtre'] ?? '');

        if (!empty($nom_partenaire) && !empty($url_site)) {
            $widget_token = bin2hex(random_bytes(16));

            try {
                $stmt = $bdd->prepare("
                    INSERT INTO jevend_annuaire_partenaire 
                    (nom_partenaire, nom_contact, courriel, url_site, format_widget, ville_filtre, widget_token) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nom_partenaire, $nom_contact, $courriel, $url_site, $format_widget, $ville_filtre, $widget_token]);
                $message_admin = "Le partenaire '$nom_partenaire' a été ajouté avec succès !";
                $type_message = "succes";
            } catch (PDOException $e) {
                $message_admin = "Erreur lors de l'enregistrement : " . $e->getMessage();
                $type_message = "erreur";
            }
        } else {
            $message_admin = "Le nom du partenaire et l'URL du site sont obligatoires.";
            $type_message = "erreur";
        }
    }

    // 2. Supprimer un partenaire
    if ($action === 'supprimer_partenaire') {
        $id_partenaire = (int)($_POST['id_partenaire'] ?? 0);
        if ($id_partenaire > 0) {
            try {
                $stmt = $bdd->prepare("DELETE FROM jevend_annuaire_partenaire WHERE id_partenaire = ?");
                $stmt->execute([$id_partenaire]);
                $message_admin = "Partenaire supprimé avec succès.";
                $type_message = "succes";
            } catch (PDOException $e) {
                $message_admin = "Erreur lors de la suppression.";
                $type_message = "erreur";
            }
        }
    }

    // 3. Vider le cache spécifique d'un partenaire via son token
    if ($action === 'vider_cache_partenaire') {
        $token_cible = trim($_POST['widget_token'] ?? '');
        if (!empty($token_cible)) {
            $cache_dir = __DIR__ . '/../widgets_cache/';
            if (is_dir($cache_dir)) {
                $fichiers = glob($cache_dir . 'widget_*.json');
                $suppr_count = 0;
                foreach ($fichiers as $f) {
                    if (is_file($f)) {
                        $contenu_cache = @file_get_contents($f);
                        // Si le token de ce partenaire se trouve dans le fichier de cache, on le supprime
                        if (strpos($contenu_cache, $token_cible) !== false) {
                            @unlink($f);
                            $suppr_count++;
                        }
                    }
                }
                $message_admin = "Cache rafraîchi avec succès pour ce partenaire ($suppr_count fichier(s) effacé(s)).";
                $type_message = "succes";
            }
        }
    }
}

// Récupération de la liste des partenaires et du nombre d'annonces
try {
    $partenaires = $bdd->query("SELECT * FROM jevend_annuaire_partenaire ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC);
    $total_actives = $bdd->query("SELECT COUNT(*) FROM jevend_annonces WHERE statut = 'actif'")->fetchColumn();
} catch (PDOException $e) {
    $partenaires = [];
    $total_actives = 0;
}
?>

<div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
        <div>
            <h2 style="margin: 0 0 5px 0; color: #166534;">🌐 Gestion de la Vitrine du Village & Partenaires</h2>
            <p style="margin: 0; color: #64748b; font-size: 0.88rem;">Suivi des installations, formats multiples et filtres géographiques régionaux.</p>
        </div>
        <div style="background: #f0fdf4; color: #166534; padding: 8px 15px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; border: 1px solid #bbf7d0;">
            Annonces actives éligibles : <?= $total_actives ?>
        </div>
    </div>

    <?php if (!empty($message_admin)): ?>
        <div style="padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 0.88rem; text-align: center; background: <?= ($type_message === 'succes') ? '#f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : '#fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
            <?= htmlspecialchars($message_admin) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULAIRE D'AJOUT D'UN NOUVEAU PARTENAIRE -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 1.05rem; margin-bottom: 15px;">➕ Enregistrer un nouveau site partenaire / municipalité</h3>
        
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <input type="hidden" name="action_vitrine" value="ajouter_partenaire">

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #334155; margin-bottom: 4px;">Nom du Partenaire / Ville *</label>
                <input type="text" name="nom_partenaire" placeholder="ex: Municipalité de Matane" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #334155; margin-bottom: 4px;">URL du Site Partenaire *</label>
                <input type="url" name="url_site" placeholder="https://www.matane.ca" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #334155; margin-bottom: 4px;">Nom du contact (Optionnel)</label>
                <input type="text" name="nom_contact" placeholder="ex: Marc Bouchard" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #334155; margin-bottom: 4px;">Courriel de contact (Optionnel)</label>
                <input type="email" name="courriel" placeholder="contact@matane.ca" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #334155; margin-bottom: 4px;">Format de Widget</label>
               <select name="format_widget" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; background: #ffffff;">
                    <option value="leaderboard">Leaderboard (728 x 90 px)</option>
                    <option value="rectangle">Rectangle moyen (300 x 250 px)</option>
                    <option value="grand_rectangle">Grand rectangle (336 x 280 px)</option>
                    <option value="skyscraper">Skyscraper (160 x 600 px)</option>
                    <option value="demi_page">Demi-page / Grand-angle (300 x 600 px)</option>
                    <option value="banniere_mobile">Bannière mobile (320 x 50 px)</option>
                    <option value="sidebar">Sidebar verticale</option>
                    <option value="bouton">Bouton de marque Jevend</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #334155; margin-bottom: 4px;">Filtre Géographique (Optionnel)</label>
                <input type="text" name="ville_filtre" placeholder="ex: Matane (laisser vide pour tout)" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>

            <div style="grid-column: span 2; text-align: right; margin-top: 5px;">
                <button type="submit" style="background: #16a34a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem;">
                    Enregistrer et générer le Widget
                </button>
            </div>
        </form>
    </div>

    <!-- LISTE DES PARTENAIRES -->
    <h3 style="color: #0f172a; font-size: 1.05rem; margin-bottom: 10px;">📋 Annuaire des Partenaires & Codes à Transmettre</h3>
    
    <?php if (empty($partenaires)): ?>
        <div style="background: #f8fafc; padding: 20px; text-align: center; border-radius: 6px; border: 1px solid #e2e8f0; color: #64748b; font-size: 0.88rem;">
            Aucun partenaire enregistré pour l'instant. Remplissez le formulaire ci-dessus pour en créer un.
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($partenaires as $p): ?>
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 8px;">
                        <div>
                            <strong style="font-size: 1rem; color: #1e3a8a;"><?= htmlspecialchars($p['nom_partenaire']) ?></strong>
                            <a href="<?= htmlspecialchars($p['url_site']) ?>" target="_blank" style="font-size: 0.8rem; color: #2563eb; margin-left: 10px; text-decoration: none;"><?= htmlspecialchars($p['url_site']) ?> ↗</a>
                            <?php if(!empty($p['ville_filtre'])): ?>
                                <span style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">Zone : <?= htmlspecialchars($p['ville_filtre']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; font-size: 0.82rem;">
                            <span style="background: #eff6ff; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-weight: bold;">Format : <?= htmlspecialchars($p['format_widget']) ?></span>
                            <span style="background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-weight: bold;">🖱️ Clics : <?= $p['nb_clics'] ?></span>
                            
                            <!-- Bouton pour vider le cache de CE partenaire spécifiquement -->
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action_vitrine" value="vider_cache_partenaire">
                                <input type="hidden" name="widget_token" value="<?= htmlspecialchars($p['widget_token']) ?>">
                                <button type="submit" style="background: #f59e0b; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;" title="Efface le cache de ce partenaire uniquement">
                                    🧹 Rafraîchir
                                </button>
                            </form>

                            <!-- Bouton Supprimer -->
                            <form method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer ce partenaire ?');" style="margin: 0;">
                                <input type="hidden" name="action_vitrine" value="supprimer_partenaire">
                                <input type="hidden" name="id_partenaire" value="<?= $p['id_partenaire'] ?>">
                                <button type="submit" style="background: #dc2626; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Supprimer</button>
                            </form>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: bold; color: #64748b; margin-bottom: 3px;">Code Widget Personnalisé :</label>
                        <textarea readonly style="width: 100%; height: 75px; padding: 8px; font-family: monospace; font-size: 0.78rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; color: #0f172a;" onclick="this.select();">
<!-- Widget Jevend.com - <?= htmlspecialchars($p['nom_partenaire']) ?> -->
<div id="jevend-vitrine-<?= $p['id_partenaire'] ?>"></div>
<script>
    fetch('https://jevend.com/api_vitrine.php?format=<?= $p['format_widget'] ?>&limit=5&token=<?= $p['widget_token'] ?>')
        .then(res => res.text())
        .then(html => { document.getElementById('jevend-vitrine-<?= $p['id_partenaire'] ?>').innerHTML = html; })
        .catch(err => console.log('Erreur chargement vitrine'));
</script>
                        </textarea>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
