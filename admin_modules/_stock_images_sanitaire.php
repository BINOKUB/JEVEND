<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_stock_images_sanitaire.php
// REVISION     : 1.1 - Prise en compte du sous-dossier uploads/bannieres/
// =============================================================================

$message_sanitaire = '';

// -----------------------------------------------------------------------------
// ACTION : SUPPRESSION SÉLECTIONNÉE DES IMAGES ORPHELINES
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_purger_orphelines'])) {
    $fichiers_a_effacer = $_POST['fichiers_orphelins'] ?? [];
    $compteur_effaces = 0;

    foreach ($fichiers_a_effacer as $item) {
        $fichier_clean = basename($item);
        
        $chemin_uploads = __DIR__ . '/../uploads/' . $fichier_clean;
        $chemin_bannieres = __DIR__ . '/../uploads/bannieres/' . $fichier_clean;

        if (file_exists($chemin_uploads) && is_file($chemin_uploads)) {
            if (@unlink($chemin_uploads)) $compteur_effaces++;
        } elseif (file_exists($chemin_bannieres) && is_file($chemin_bannieres)) {
            if (@unlink($chemin_bannieres)) $compteur_effaces++;
        }
    }

    if ($compteur_effaces > 0) {
        $message_sanitaire = '<div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">🧹 Nettoyage réussi : ' . $compteur_effaces . ' fichier(s) orphelin(s) supprimé(s) du disque !</div>';
    } else {
        $message_sanitaire = '<div style="background:#fef3c7; color:#b45309; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">⚠️ Aucun fichier n’a été sélectionné ou supprimé.</div>';
    }
}

// -----------------------------------------------------------------------------
// 1. VÉRIFICATION DES DOSSIERS ET PERMISSIONS
// -----------------------------------------------------------------------------
$dossier_uploads = __DIR__ . '/../uploads/';
$dossier_bannieres = __DIR__ . '/../uploads/bannieres/';

function inspecterDossier($chemin, $est_dossier_racine = true) {
    if (!file_exists($chemin)) {
        return ['existe' => false, 'script_ecriture' => false, 'chmod' => 'Inexistant', 'taille' => 0, 'nb_fichiers' => 0, 'fichiers' => []];
    }
    $perms = substr(sprintf('%o', fileperms($chemin)), -4);
    $est_script_scriptable = is_writable($chemin);
    
    // Ignorer les répertoires, .htaccess, .gitkeep et readme
    $fichiers_bruts = array_diff(scandir($chemin), ['.', '..', '.htaccess', 'index.php', 'readme', '.gitkeep']);
    $taille_totale = 0;
    $liste_fichiers = [];

    foreach ($fichiers_bruts as $f) {
        $full_path = $chemin . '/' . $f;
        if (is_file($full_path)) {
            $taille_totale += filesize($full_path);
            $liste_fichiers[] = $f;
        }
    }

    return [
        'existe' => true,
        'script_ecriture' => $est_script_scriptable,
        'chmod' => $perms,
        'taille' => $taille_totale,
        'nb_fichiers' => count($liste_fichiers),
        'fichiers' => $liste_fichiers
    ];
}

$info_uploads = inspecterDossier($dossier_uploads, true);
$info_bannieres = inspecterDossier($dossier_bannieres, false);

// -----------------------------------------------------------------------------
// 2. EXTRACTION DES IMAGES RÉFÉRENCÉES DANS LA BDD
// -----------------------------------------------------------------------------
$bdd_images_uploads = [];
$bdd_images_bannieres = [];

if (isset($bdd)) {
    try {
        // A. Images Principales des Annonces
        $stmt = $bdd->query("SELECT image_courante FROM jevend_annonces WHERE image_courante IS NOT NULL AND image_courante != ''");
        while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $bdd_images_uploads[] = basename($row);
        }

        // B. Galerie Secondaire des Annonces
        $stmt = $bdd->query("SELECT nom_fichier FROM jevend_annonces_images WHERE nom_fichier IS NOT NULL AND nom_fichier != ''");
        while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $bdd_images_uploads[] = basename($row);
        }

        // C. Images des Recherches
        $stmt = $bdd->query("SELECT image_reference FROM jevend_recherches WHERE image_reference IS NOT NULL AND image_reference != ''");
        while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $bdd_images_uploads[] = basename($row);
        }

        // D. Bannières Actives Pro (table jevend_bannieres_actives_pro)
        $stmt = $bdd->query("SELECT image_url FROM jevend_bannieres_actives_pro WHERE image_url IS NOT NULL AND image_url != ''");
        while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $bdd_images_bannieres[] = basename($row);
        }

    } catch (PDOException $e) { }
}

$bdd_images_uploads = array_unique($bdd_images_uploads);
$bdd_images_bannieres = array_unique($bdd_images_bannieres);

// -----------------------------------------------------------------------------
// 3. DÉTECTION DES IMAGES ORPHELINES
// -----------------------------------------------------------------------------
$orphelines_uploads = array_diff($info_uploads['fichiers'], $bdd_images_uploads);
$orphelines_bannieres = array_diff($info_bannieres['fichiers'], $bdd_images_bannieres);

function formaterTaille($octets) {
    if ($octets >= 1073741824) return number_format($octets / 1073741824, 2) . ' Go';
    if ($octets >= 1048576) return number_format($octets / 1048576, 2) . ' Mo';
    if ($octets >= 1024) return number_format($octets / 1024, 2) . ' Ko';
    return $octets . ' octets';
}
?>

<div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <h2 style="margin-top:0; color:#0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        💾 Santé du Serveur & Gestion du Stockage d'Images
    </h2>

    <?= $message_sanitaire ?>

    <!-- CARTES D'INSPECTION DOSSIERS -->
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px;">
        
        <!-- CARTE UPLOADS -->
        <div style="flex: 1; min-width: 300px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top:0; color:#1e293b;">📁 Dossier <code>/uploads/</code></h3>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Permissions (CHMOD) :</strong> <span style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-family:monospace;"><?= $info_uploads['chmod'] ?></span></p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Droits d'écriture PHP :</strong> <?= $info_uploads['script_ecriture'] ? '<span style="color:#166534; font-weight:bold;">✅ Écriture autorisée</span>' : '<span style="color:#991b1b; font-weight:bold;">❌ Écriture Bloquée</span>' ?></p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Total Fichiers :</strong> <?= $info_uploads['nb_fichiers'] ?> image(s)</p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Espace Occupé :</strong> <?= formaterTaille($info_uploads['taille']) ?></p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Images Orphelines :</strong> <strong style="color:<?= count($orphelines_uploads) > 0 ? '#dc2626' : '#166534' ?>;"><?= count($orphelines_uploads) ?></strong></p>
        </div>

        <!-- CARTE BANNIERES -->
        <div style="flex: 1; min-width: 300px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top:0; color:#1e293b;">🖼️ Dossier <code>/uploads/bannieres/</code></h3>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Permissions (CHMOD) :</strong> <span style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-family:monospace;"><?= $info_bannieres['chmod'] ?></span></p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Droits d'écriture PHP :</strong> <?= $info_bannieres['script_ecriture'] ? '<span style="color:#166534; font-weight:bold;">✅ Écriture autorisée</span>' : '<span style="color:#991b1b; font-weight:bold;">❌ Écriture Bloquée</span>' ?></p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Total Fichiers :</strong> <?= $info_bannieres['nb_fichiers'] ?> image(s)</p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Espace Occupé :</strong> <?= formaterTaille($info_bannieres['taille']) ?></p>
            <p style="margin:5px 0; font-size:0.9rem;"><strong>Bannières Orphelines :</strong> <strong style="color:<?= count($orphelines_bannieres) > 0 ? '#dc2626' : '#166534' ?>;"><?= count($orphelines_bannieres) ?></strong></p>
        </div>

    </div>

    <!-- TABLEAU DE SCAN ET PURGE DES IMAGES ORPHELINES -->
    <h3 style="color:#0f172a; border-bottom:1px solid #cbd5e1; padding-bottom:8px;">
        🔍 Fichiers Orphelins Détectés (Absents de la base de données)
    </h3>

    <?php if (count($orphelines_uploads) === 0 && count($orphelines_bannieres) === 0): ?>
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:15px; border-radius:6px; text-align:center; font-weight:bold;">
            🎉 Parfait ! Aucun fichier orphelin n'a été trouvé. Le serveur est parfaitement propre.
        </div>
    <?php else: ?>
        <form method="POST" action="#onglet-stock-images">
            <input type="hidden" name="action_purger_orphelines" value="1">
            
            <div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                <p style="margin:0; font-size:0.9rem; color:#475569;">
                    Ces fichiers existent sur le disque mais ne sont rattachés à aucune entrée en base de données.
                </p>
                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ces fichiers orphelins du disque ?');" style="background:#ef4444; color:#fff; border:none; padding:10px 20px; border-radius:6px; font-weight:bold; cursor:pointer;">
                    🗑️ Purger la sélection
                </button>
            </div>

            <table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #e2e8f0; font-size:0.85rem;">
                <thead>
                    <tr style="background:#0f172a; color:#fff; text-align:left;">
                        <th style="padding:10px; width:40px; text-align:center;"><input type="checkbox" onclick="toggleTout(this)"></th>
                        <th style="padding:10px;">Dossier</th>
                        <th style="padding:10px;">Nom du Fichier</th>
                        <th style="padding:10px;">Taille</th>
                        <th style="padding:10px;">Aperçu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orphelines_uploads as $f): 
                        $chemin = $dossier_uploads . $f;
                        $taille = file_exists($chemin) ? filesize($chemin) : 0;
                    ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:10px; text-align:center;"><input type="checkbox" name="fichiers_orphelins[]" value="<?= htmlspecialchars($f) ?>" class="chk-orphelin" checked></td>
                        <td style="padding:10px;"><span style="background:#dbeafe; color:#1e40af; padding:2px 6px; border-radius:4px; font-weight:bold;">/uploads/</span></td>
                        <td style="padding:10px; font-family:monospace;"><?= htmlspecialchars($f) ?></td>
                        <td style="padding:10px;"><?= formaterTaille($taille) ?></td>
                        <td style="padding:5px;"><img src="uploads/<?= htmlspecialchars($f) ?>" style="height:35px; border-radius:4px; border:1px solid #ccc;"></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php foreach ($orphelines_bannieres as $f): 
                        $chemin = $dossier_bannieres . $f;
                        $taille = file_exists($chemin) ? filesize($chemin) : 0;
                    ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:10px; text-align:center;"><input type="checkbox" name="fichiers_orphelins[]" value="<?= htmlspecialchars($f) ?>" class="chk-orphelin" checked></td>
                        <td style="padding:10px;"><span style="background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:4px; font-weight:bold;">/uploads/bannieres/</span></td>
                        <td style="padding:10px; font-family:monospace;"><?= htmlspecialchars($f) ?></td>
                        <td style="padding:10px;"><?= formaterTaille($taille) ?></td>
                        <td style="padding:5px;"><img src="uploads/bannieres/<?= htmlspecialchars($f) ?>" style="height:35px; border-radius:4px; border:1px solid #ccc;"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <script>
        function toggleTout(source) {
            checkboxes = document.getElementsByClassName('chk-orphelin');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        </script>
    <?php endif; ?>
</div>
