<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =============================================================================
// NOM DU SCRIPT : creer_annonce.php
// REVISION : 2.5 - Séparation de la logique de traitement dans creer_annonce_traitement.php
// =============================================================================

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

require_once 'config.php';

$id_utilisateur = $_SESSION['id_utilisateur'];
$erreur = "";
$succes = "";
$quota_global_atteint = false;
$quota_utilisateur_atteint = false;
$total_annonces_site = 0;
$nb_annonces_actuelles = 0;
$limite_globale_annonces = 2000;
$limite_personnelle = 6;

// 1. VÉRIFICATION PRÉVENTIVE (QUOTA GLOBAL + QUOTA PERSONNEL) DÈS L'ARRIVÉE
try {
    // Quota Global du site
    $stmt_tot = $bdd->query("SELECT COUNT(*) FROM jevend_annonces");
    $total_annonces_site = (int)$stmt_tot->fetchColumn();

    $stmt_param = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'limite_annonces'");
    $stmt_param->execute();
    $val_param = $stmt_param->fetchColumn();
    if ($val_param !== false) {
        $limite_globale_annonces = (int)$val_param;
    }

    if ($total_annonces_site >= $limite_globale_annonces) {
        $quota_global_atteint = true;
    }

    // Quota Personnel de l'utilisateur
    $stmt_quota = $bdd->prepare("SELECT COUNT(*) FROM jevend_annonces WHERE id_utilisateur = ?");
    $stmt_quota->execute([$id_utilisateur]);
    $nb_annonces_actuelles = (int)$stmt_quota->fetchColumn();

    if ($nb_annonces_actuelles >= $limite_personnelle) {
        $quota_utilisateur_atteint = true;
    }

} catch (PDOException $e) {
    $quota_global_atteint = false;
    $quota_utilisateur_atteint = false;
}

// 2. INCLUSION DU TRAITEMENT DE LA SOUMISSION DU FORMULAIRE
require_once 'creer_annonce_traitement.php';

// 3. RÉCUPÉRATION DES CATÉGORIES (Uniquement si aucun quota n'est atteint)
$parents = [];
if (!$quota_global_atteint && !$quota_utilisateur_atteint) {
    try {
        $stmt = $bdd->query("SELECT id_categorie, parent_id, nom_fr FROM jevend_categories ORDER BY parent_id ASC, nom_fr ASC");
        $liste_brute = $stmt->fetchAll();

        $enfants = [];
        foreach ($liste_brute as $cat) {
            if ($cat['parent_id'] === null) {
                $parents[$cat['id_categorie']] = $cat;
                $parents[$cat['id_categorie']]['sous_cat'] = [];
            } else {
                $enfants[] = $cat;
            }
        }
        foreach ($enfants as $enfant) {
            if (isset($parents[$enfant['parent_id']])) {
                $parents[$enfant['parent_id']]['sous_cat'][] = $enfant;
            }
        }
    } catch (PDOException $e) {
        $erreur = "Erreur de chargement des catégories : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publier une annonce - jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_create_annonce.css?v=1.0">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="form-bloc">
        <h2>📢 Créer une Annonce (5 photos max)</h2>

        <?php if ($quota_global_atteint): ?>
            <!-- ENCART DE BLOCAGE GLOBAL -->
            <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 30px; border-radius: 8px; text-align: center; margin-top: 20px;">
                <div style="font-size: 1.3rem; font-weight: 900; margin-bottom: 12px;">
                    ⚠️ Capacité maximale du réseau atteinte (<?= $total_annonces_site ?> / <?= $limite_globale_annonces ?> annonces)
                </div>
                <p style="font-size: 1rem; line-height: 1.5; color: #7f1d1d; margin-bottom: 25px;">
                    Le quota global des annonces fixé par l'administration est actuellement atteint. La création de nouvelles vitrines est temporairement suspendue.
                </p>
                <a href="espace_membre.php" class="btn-action" style="display: inline-block; background-color: #2563eb; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold;">
                    ← Retour à mon espace client
                </a>
            </div>

        <?php elseif ($quota_utilisateur_atteint): ?>
            <!-- ENCART DE BLOCAGE PERSONNEL (6 ANNONCES ATTEINTES) -->
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 30px; border-radius: 8px; text-align: center; margin-top: 20px;">
                <div style="font-size: 1.3rem; font-weight: 900; margin-bottom: 12px;">
                    🔒 Limite de publication atteinte (<?= $nb_annonces_actuelles ?> / <?= $limite_personnelle ?> annonces)
                </div>
                <p style="font-size: 1rem; line-height: 1.5; color: #78350f; margin-bottom: 25px;">
                    Vous avez atteint le nombre maximal de <?= $limite_personnelle ?> annonces actives autorisées sur votre compte. Pour publier une nouvelle annonce, veuillez d'abord supprimer ou modifier une de vos annonces existantes depuis votre espace membre.
                </p>
                <a href="espace_membre.php" class="btn-action" style="display: inline-block; background-color: #2563eb; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold;">
                    ← Gérer mes annonces
                </a>
            </div>

        <?php else: ?>
            <!-- FORMULAIRE ACTIF -->
            <?php if (!empty($erreur)): ?>
                <div class="erreur-msg"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if (!empty($succes)): ?>
                <div class="succes-msg"><?php echo htmlspecialchars($succes); ?></div>
            <?php endif; ?>

            <form id="form-annonce" action="creer_annonce.php" method="POST">
                
                <div class="champ-groupe">
                    <label for="id_categorie">Catégorie ou Type de Service</label>
                    <select id="id_categorie" name="id_categorie" required>
                        <option value="">-- Sélectionnez une catégorie --</option>
                        <?php foreach ($parents as $parent): ?>
                            <option value="<?php echo $parent['id_categorie']; ?>" style="font-weight: bold; background-color: #e2e8f0;">
                                <?php echo htmlspecialchars($parent['nom_fr']); ?>
                            </option>
                            <?php foreach ($parent['sous_cat'] as $sous_cat): ?>
                                <option value="<?php echo $sous_cat['id_categorie']; ?>">
                                    &nbsp;&nbsp;&nbsp;&nbsp;↳ <?php echo htmlspecialchars($sous_cat['nom_fr']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ-groupe">
                    <label for="titre_objet_nettoye">Titre court de l'annonce (Max 60 caractères)</label>
                    <input type="text" id="titre_objet_nettoye" name="titre_objet_nettoye" maxlength="60" value="<?php echo isset($titre_objet_nettoye) ? htmlspecialchars($titre_objet_nettoye) : ''; ?>" required placeholder="Ex: Tonte de pelouse, Divan en cuir...">
                </div>

                <div class="champ-groupe">
                    <label for="description_service">Description complète du bien ou du service</label>
                    <textarea id="description_service" name="description_service" rows="5" required placeholder="Donnez un maximum de détails ici..."><?php echo isset($description_service) ? htmlspecialchars($description_service) : ''; ?></textarea>
                </div>

                <div class="champ-groupe">
                    <label for="prix">Prix recherché ($) <span style="font-size:0.8rem; color:#64748b;">(Optionnel pour un service)</span></label>
                    <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?php echo isset($prix_brut) ? htmlspecialchars($prix_brut) : ''; ?>" placeholder="Ex: 45.50 (laisser vide si sur demande)">
                </div>

                <div class="champ-groupe">
                    <label style="font-weight: bold; color: #1e3a8a;">📷 Photos de votre vitrine (Maximum 5 photos)</label>
                    <p style="font-size: 0.8rem; color:#64748b; margin-top: 2px; margin-bottom: 10px;">La première photo valide sera l'image principale de votre vitrine.</p>
                    
                    <div class="zone-photos-inputs">
                        <input type="file" class="input-image-comp" data-index="0" accept="image/jpeg, image/png, image/webp">
                        <input type="file" class="input-image-comp" data-index="1" accept="image/jpeg, image/png, image/webp">
                        <input type="file" class="input-image-comp" data-index="2" accept="image/jpeg, image/png, image/webp">
                        <input type="file" class="input-image-comp" data-index="3" accept="image/jpeg, image/png, image/webp">
                        <input type="file" class="input-image-comp" data-index="4" accept="image/jpeg, image/png, image/webp">
                    </div>

                    <div id="conteneur-base64"></div>
                </div>

                <div class="champ-groupe">
                    <label>Durée de présence sur la vitrine (Timeout obligatoire)</label>
                    <div class="radio-duree-group">
                        <label class="radio-option">
                            <input type="radio" name="duree_affichage" value="10" checked> 10 Jours
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="duree_affichage" value="20"> 20 Jours
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="duree_affichage" value="30"> 30 Jours (Max)
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-action-submit">✨ Mettre en vitrine instantanément</button>
                
                <div class="liens-navigation">
                    <a href="espace_membre.php">← Retour à mon tableau de bord</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll(".input-image-comp");
        if (!inputs.length) return;
        const conteneurBase64 = document.getElementById("conteneur-base64");

        inputs.forEach(input => {
            input.addEventListener("change", function(e) {
                const index = this.getAttribute("data-index");
                const fichier = e.target.files[0];

                const ancienInput = document.getElementById("hidden-img-" + index);
                if (ancienInput) ancienInput.remove();

                if (!fichier) return;

                const reader = new FileReader();
                reader.readAsDataURL(fichier);
                reader.onload = function(event) {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = function() {
                        const max_largeur = 1200;
                        let largeur = img.width;
                        let hauteur = img.height;

                        if (largeur > max_largeur) {
                            const ratio = max_largeur / largeur;
                            largeur = max_largeur;
                            hauteur = Math.round(hauteur * ratio);
                        }

                        const canvas = document.createElement("canvas");
                        canvas.width = largeur;
                        canvas.height = hauteur;

                        const ctx = canvas.getContext("2d");
                        ctx.fillStyle = "#FFFFFF";
                        ctx.fillRect(0, 0, largeur, hauteur);
                        ctx.drawImage(img, 0, 0, largeur, hauteur);

                        const dataUrl = canvas.toDataURL("image/jpeg", 0.75);

                        const hiddenInput = document.createElement("input");
                        hiddenInput.type = "hidden";
                        hiddenInput.name = "images_base64[" + index + "]";
                        hiddenInput.id = "hidden-img-" + index;
                        hiddenInput.value = dataUrl;
                        conteneurBase64.appendChild(hiddenInput);
                    };
                };
            });
        });
    });
    </script>
</body>
</html>
