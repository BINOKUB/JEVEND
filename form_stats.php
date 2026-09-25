<?php
// =============================================================================
// NOM DU SCRIPT : form_stats.php
// DESCRIPTION : Tableau de bord des statistiques globales et gestion de la bannière 728x90
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// 1. Récupération des données de l'environnement client
$ip_client = $_SERVER['REMOTE_ADDR'];
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$accept_lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

// 2. Filtres de sécurité anti-robots et de langue
$est_un_robot = (empty($user_agent) || preg_match('/bot|crawler|spider|curl|python|wget|libwww|scanner|nikto|ltx71/i', $user_agent));
$langue_francaise = (strpos($accept_lang, 'fr') !== false);

// 3. Vérification de l'administrateur (Email + IP dans le champ 'neq')
$stmt = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE courriel = 'douimet61@gmail.com' AND role = 'admin' AND neq = ?");
$stmt->execute([$ip_client]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$autorise = ($admin !== false && !$est_un_robot && $langue_francaise);

$message_succes = "";
$message_erreur = "";

// 4. Traitement du formulaire d'upload de bannière si autorisé
if ($autorise && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $lien_url = trim($_POST['lien_url'] ?? '');
    $statut   = isset($_POST['statut']) ? 'actif' : 'inactif';

    if (isset($_FILES['image_banniere']) && $_FILES['image_banniere']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image_banniere']['tmp_name'];
        $file_name_orig = $_FILES['image_banniere']['name'];
        $extension = strtolower(pathinfo($file_name_orig, PATHINFO_EXTENSION));
        
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $file_name_orig);
        $destination = 'uploads/' . $file_name;

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (in_array($extension, $allowed_extensions)) {
            if (move_uploaded_file($file_tmp, $destination)) {
                try {
                    $bdd->exec("UPDATE jevend_banniere_728 SET statut = 'inactif'");
                    $stmt_ins = $bdd->prepare("INSERT INTO jevend_banniere_728 (image_banniere, lien_url, statut) VALUES (?, ?, ?)");
                    $stmt_ins->execute([$file_name, $lien_url, $statut]);
                    $message_succes = "La bannière 728x90 a été mise à jour avec succès !";
                } catch (PDOException $e) {
                    $message_erreur = "Erreur BDD : " . $e->getMessage();
                }
            } else {
                $message_erreur = "Erreur lors du déplacement du fichier image.";
            }
        } else {
            $message_erreur = "Format de fichier non valide (JPG, PNG, GIF, WEBP ou SVG acceptés).";
        }
    } else {
        $id_banniere_actuelle = $_POST['id_banniere_actuelle'] ?? 0;
        if ($id_banniere_actuelle > 0) {
            try {
                $stmt_up = $bdd->prepare("UPDATE jevend_banniere_728 SET lien_url = ?, statut = ? WHERE id = ?");
                $stmt_up->execute([$lien_url, $statut, $id_banniere_actuelle]);
                $message_succes = "Paramètres de la bannière mis à jour !";
            } catch (PDOException $e) {
                $message_erreur = "Erreur BDD : " . $e->getMessage();
            }
        }
    }
}

// 5. Récupération des statistiques globales si autorisé
$total_absolu = 0;
$total_mois = 0;
$stats_par_jour = [];
$banniere_actuelle = null;

if ($autorise) {
    try {
        $total_absolu = $bdd->query("SELECT COUNT(*) FROM jevend_stats WHERE page = 'actualite'")->fetchColumn();
        $total_mois = $bdd->query("SELECT COUNT(*) FROM jevend_stats WHERE page = 'actualite' AND MONTH(date_visite) = MONTH(CURRENT_DATE()) AND YEAR(date_visite) = YEAR(CURRENT_DATE())")->fetchColumn();

        $stmt_jours = $bdd->query("
            SELECT DATE(date_visite) as jour, 
                   COUNT(*) as total 
            FROM jevend_stats 
            WHERE page = 'actualite' 
            GROUP BY DATE(date_visite) 
            ORDER BY jour DESC 
            LIMIT 30
        ");
        $stats_par_jour = $stmt_jours->fetchAll(PDO::FETCH_ASSOC);

        $stmt_b = $bdd->query("SELECT * FROM jevend_banniere_728 ORDER BY id DESC LIMIT 1");
        $banniere_actuelle = $stmt_b->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques & Publicité — Jevend</title>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .admin-container {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        }
        .ip-badge {
            background: #0f172a;
            border: 1px solid #38bdf8;
            color: #38bdf8;
            padding: 10px 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9rem;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: #0f172a;
            border: 1px solid #334155;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 8px 0;
            font-size: 0.85rem;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .stat-card .nombre {
            font-size: 1.8rem;
            font-weight: bold;
            color: #38bdf8;
            margin: 0;
        }
        .mini-stats-ligne {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .mini-stat-pill {
            background: #0f172a;
            border: 1px solid #475569;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .form-group input[type="text"],
        .form-group input[type="file"] {
            width: 100%;
            background: #0f172a;
            border: 1px solid #475569;
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
            font-family: inherit;
        }
        .btn-action {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-action:hover { background: #1d4ed8; }
        .alerte-refus {
            text-align: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 40px;
            max-width: 450px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            margin: auto;
        }
        .succes-msg {
            background: rgba(22, 163, 74, 0.2);
            border: 1px solid #16a34a;
            color: #4ade80;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .erreur-msg {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #f87171;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <?php if (!$autorise): ?>
        <div class="alerte-refus">
            <h2 style="color: #f8fafc; margin-top:0;">Page introuvable</h2>
            <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.5;">
                La page que vous essayez d'atteindre n'existe pas ou a été déplacée.
            </p>
        </div>
    <?php else: ?>
        <div class="admin-container">
            <div class="ip-badge">
                <span>📊 Statistiques & Régie Publicitaire (728x90)</span>
                <a href="form.php" style="color: #38bdf8; text-decoration: none;">← Retour Admin Journal</a>
            </div>

            <?php if (!empty($message_succes)): ?>
                <div class="succes-msg"><?= $message_succes ?></div>
            <?php endif; ?>

            <?php if (!empty($message_erreur)): ?>
                <div class="erreur-msg"><?= $message_erreur ?></div>
            <?php endif; ?>

            <!-- BLOCS DE COMPTEURS PRINCIPAUX -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total du mois en cours</h3>
                    <p class="nombre"><?= number_format($total_mois, 0, ',', ' ') ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Absolu (Vues)</h3>
                    <p class="nombre"><?= number_format($total_absolu, 0, ',', ' ') ?></p>
                </div>
            </div>

            <!-- VUES PAR DATE (30 DERNIERS JOURS) -->
            <h3 style="font-size: 1rem; color: #cbd5e1; margin-bottom: 10px;">📅 Vues par date (30 derniers jours)</h3>
            <div class="mini-stats-ligne">
                <?php if (!empty($stats_par_jour)): ?>
                    <?php foreach ($stats_par_jour as $st): ?>
                        <div class="mini-stat-pill">
                            <strong style="color: #38bdf8;"><?= $st['jour'] ?></strong><br>
                            <?= $st['total'] ?> vue(s)
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: #94a3b8;">Aucune statistique enregistrée pour le moment.</p>
                <?php endif; ?>
            </div>

            <hr style="border: 0; border-top: 1px solid #334155; margin: 25px 0;">

            <!-- GESTION DE LA BANNIÈRE 728x90 -->
            <h3 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 15px;">🖼️ Gestion de l'Espace Publicitaire (728x90)</h3>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id_banniere_actuelle" value="<?= $banniere_actuelle['id'] ?? 0 ?>">

                <?php if ($banniere_actuelle && !empty($banniere_actuelle['image_banniere'])): ?>
                    <div style="margin-bottom: 15px; background: #0f172a; padding: 15px; border-radius: 6px; text-align: center;">
                        <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 0;">Bannière actuellement enregistrée :</p>
                        <img src="uploads/<?= htmlspecialchars($banniere_actuelle['image_banniere']) ?>" style="max-width: 100%; width: 500px; height: auto; border-radius: 4px; border: 1px solid #475569;">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="image_banniere">Nouvelle image (Format 728x90 recommandé)</label>
                    <input type="file" id="image_banniere" name="image_banniere" accept="image/jpeg, image/png, image/gif, image/webp, image/svg+xml">
                </div>

                <div class="form-group">
                    <label for="lien_url">Lien de redirection au clic (URL)</label>
                    <input type="text" id="lien_url" name="lien_url" value="<?= htmlspecialchars($banniere_actuelle['lien_url'] ?? '') ?>" placeholder="https://exemple.com">
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="statut" name="statut" value="actif" <?= (($banniere_actuelle['statut'] ?? 'actif') === 'actif') ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                    <label for="statut" style="margin: 0; cursor: pointer; color: #4ade80;">Activer l'affichage de la bannière sur la page d'actualités</label>
                </div>

                <button type="submit" class="btn-action">💾 Enregistrer la bannière</button>
            </form>

        </div>
    <?php endif; ?>

</body>
</html>
