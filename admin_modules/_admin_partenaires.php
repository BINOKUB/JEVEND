<?php
// =============================================================================
// MODULE       : _admin_partenaires.php
// REVISION     : 1.0 - Gestion des widgets partenaires & Rapports municipaux
// NOM DU SCRIPT: admin_modules/_admin_partenaires.php
// =============================================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

$message_partenaire = "";
$type_message = "";

// Traitement de l'ajout d'un nouveau partenaire (Ex: Municipalité)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_partenaire'])) {
    $nom_partenaire = trim($_POST['nom_partenaire'] ?? '');
    $ville_filtre   = trim($_POST['ville_filtre'] ?? '');
    $site_web       = trim($_POST['site_web'] ?? '');

    if (!empty($nom_partenaire)) {
        try {
            $widget_token = bin2hex(random_bytes(16));
            $stmt_ins = $bdd->prepare("
                INSERT INTO jevend_annuaire_partenaire (nom_partenaire, ville_filtre, site_web, widget_token, date_creation) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt_ins->execute([$nom_partenaire, $ville_filtre, $site_web, $widget_token]);
            $message_partenaire = "Partenaire « " . htmlspecialchars($nom_partenaire) . " » ajouté avec succès.";
            $type_message = "succes";
        } catch (PDOException $e) {
            $message_partenaire = "Erreur SQL : " . $e->getMessage();
            $type_message = "erreur";
        }
    }
}

// Récupération de la liste des partenaires enregistrés
try {
    $stmt_part = $bdd->query("SELECT * FROM jevend_annuaire_partenaire ORDER BY id_partenaire DESC");
    $partenaires = $stmt_part->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $partenaires = [];
}

// Détection dynamique de l'URL de base
$protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$url_de_base = $protocole . $_SERVER['HTTP_HOST'];
?>

<div style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; box-sizing: border-box; margin-top: 20px;">

    <h3 style="color: #1e3a8a; margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
        <span>🤝</span> Partenariats Municipaux & Widgets d'Annonces
    </h3>

    <?php if (!empty($message_partenaire)): ?>
        <div style="padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 0.9rem; text-align: center; background: <?= ($type_message === 'succes') ? '#f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : '#fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
            <?= htmlspecialchars($message_partenaire) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULAIRE D'AJOUT -->
    <form method="POST" style="background: #f8fafc; padding: 15px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 25px;">
        <input type="hidden" name="action_partenaire" value="1">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="nom_partenaire" placeholder="Nom (ex: Mairie de Matane)" required style="flex: 2; min-width: 200px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" name="ville_filtre" placeholder="Ville filtrée (ex: Matane)" style="flex: 1; min-width: 150px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="url" name="site_web" placeholder="Site web (https://...)" style="flex: 2; min-width: 200px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                + Ajouter
            </button>
        </div>
    </form>

    <!-- TABLEAU DES PARTENAIRES -->
    <?php if (empty($partenaires)): ?>
        <div style="text-align: center; color: #94a3b8; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px;">
            Aucun partenaire enregistré pour le moment.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                <thead>
                    <tr style="background: #0f172a; color: #ffffff;">
                        <th style="padding: 10px;">ID & Partenaire</th>
                        <th style="padding: 10px;">Ville Filtrée</th>
                        <th style="padding: 10px;">Jeton du Widget</th>
                        <th style="padding: 10px; text-align: center;">Lien du Rapport (à envoyer à la Mairie)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partenaires as $p): 
                        $url_rapport = $url_de_base . '/rapport_partenaire.php?token=' . $p['widget_token'];
                    ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            
                            <!-- Partenaire -->
                            <td style="padding: 12px 10px; vertical-align: top;">
                                <strong>#<?= $p['id_partenaire'] ?> <?= htmlspecialchars($p['nom_partenaire']) ?></strong><br>
                                <?php if (!empty($p['site_web'])): ?>
                                    <a href="<?= htmlspecialchars($p['site_web']) ?>" target="_blank" style="font-size: 0.78rem; color: #2563eb; text-decoration: none;"><?= htmlspecialchars($p['site_web']) ?> ↗</a>
                                <?php endif; ?>
                            </td>

                            <!-- Ville -->
                            <td style="padding: 12px 10px; vertical-align: top;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem;">
                                    <?= !empty($p['ville_filtre']) ? htmlspecialchars($p['ville_filtre']) : 'Toutes les villes' ?>
                                </span>
                            </td>

                            <!-- Jeton -->
                            <td style="padding: 12px 10px; vertical-align: top;">
                                <code style="background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-size: 0.78rem; color: #334155;">
                                    <?= htmlspecialchars($p['widget_token']) ?>
                                </code>
                            </td>

                            <!-- Lien de Rapport à copier -->
                            <td style="padding: 12px 10px; vertical-align: top; text-align: center;">
                                <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                    <input type="text" readonly value="<?= $url_rapport ?>" onclick="this.select();" title="Cliquer pour tout copier" style="font-size: 0.75rem; padding: 6px; width: 100%; max-width: 280px; border: 1px dashed #2563eb; border-radius: 4px; background: #eff6ff; color: #1e40af; cursor: pointer; text-align: center; font-weight: bold;">
                                    <span style="font-size: 0.7rem; color: #64748b;">(Cliquer pour copier le lien)</span>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
