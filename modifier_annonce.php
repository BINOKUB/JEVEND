<?php
session_start();

// Forcer l'affichage des erreurs PHP pour le débogage local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =============================================================================
// NOM DU SCRIPT : modifier_annonce.php
// REVISION : 2.1 - Externalisation CSS vers style_modif_ann.css, suppression des styles en ligne et intégration de la barre membre
// =============================================================================

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

require_once 'config.php';

$id_utilisateur = $_SESSION['id_utilisateur'];
$erreur = "";
$succes = "";

// 1. VÉRIFICATION DE L'EXISTENCE ET PROPRIÉTÉ DE L'ANNONCE
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: espace_membre.php');
    exit();
}

$id_annonce = intval($_GET['id']);

try {
    // Récupération de l'annonce maîtresse
    $stmt = $bdd->prepare("SELECT * FROM jevend_annonces WHERE id_annonces = ? AND id_utilisateur = ?");
    $stmt->execute([$id_annonce, $id_utilisateur]);
    $annonce = $stmt->fetch();

    if (!$annonce) {
        header('Location: espace_membre.php');
        exit();
    }

    // --- TRAITEMENT DU RETRAIT CHIRURGICAL D'UNE PHOTO ---
    if (isset($_GET['action']) && $_GET['action'] === 'supprimer_photo' && isset($_GET['id_photo'])) {
        $id_photo_del = intval($_GET['id_photo']);
        
        $stmt_find = $bdd->prepare("SELECT nom_fichier, est_principale FROM jevend_annonces_images WHERE id_image = ? AND id_annonces = ?");
        $stmt_find->execute([$id_photo_del, $id_annonce]);
        $photo_info = $stmt_find->fetch();

        if ($photo_info) {
            if (file_exists('uploads/' . $photo_info['nom_fichier'])) {
                @unlink('uploads/' . $photo_info['nom_fichier']);
            }
            
            $stmt_del_pic = $bdd->prepare("DELETE FROM jevend_annonces_images WHERE id_image = ?");
            $stmt_del_pic->execute([$id_photo_del]);

            if ($photo_info['est_principale'] == 1) {
                $stmt_next = $bdd->prepare("SELECT id_image, nom_fichier FROM jevend_annonces_images WHERE id_annonces = ? LIMIT 1");
                $stmt_next->execute([$id_annonce]);
                $next_pic = $stmt_next->fetch();

                if ($next_pic) {
                    $bdd->prepare("UPDATE jevend_annonces_images SET est_principale = 1 WHERE id_image = ?")->execute([$next_pic['id_image']]);
                    $bdd->prepare("UPDATE jevend_annonces SET image_courante = ? WHERE id_annonces = ?")->execute([$next_pic['nom_fichier'], $id_annonce]);
                } else {
                    $bdd->prepare("UPDATE jevend_annonces SET image_courante = NULL WHERE id_annonces = ?")->execute([$id_annonce]);
                }
            }
        }
        header("Location: modifier_annonce.php?id=" . $id_annonce);
        exit();
    }

} catch (PDOException $e) {
    $erreur = "Erreur SQL : " . $e->getMessage();
}

// 2. TRAITEMENT DU FORMULAIRE DE MODIFICATION (TEXTES ET NOUVELLES IMAGES)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre_objet_nettoye = htmlspecialchars(trim($_POST['titre_objet_nettoye']), ENT_QUOTES, 'UTF-8');
    $description_service = htmlspecialchars(trim($_POST['description_service']), ENT_QUOTES, 'UTF-8');
    $prix_brut = trim($_POST['prix']);

    $prix = (!empty($prix_brut) && is_numeric($prix_brut)) ? floatval($prix_brut) : null;

    if (empty($titre_objet_nettoye) || empty($description_service)) {
        $erreur = "Le titre et la description ne peuvent pas être vides.";
    } elseif (strlen($titre_objet_nettoye) > 60) {
        $erreur = "Le titre ne doit pas dépasser 60 caractères.";
    } else {
        try {
            $bdd->beginTransaction();

            $stmt_update = $bdd->prepare("UPDATE jevend_annonces SET titre_objet_nettoye = ?, description_service = ?, prix = ? WHERE id_annonces = ? AND id_utilisateur = ?");
            $stmt_update->execute([$titre_objet_nettoye, $description_service, $prix, $id_annonce, $id_utilisateur]);

            if (isset($_POST['images_base64']) && is_array($_POST['images_base64'])) {
                $stmt_count = $bdd->prepare("SELECT COUNT(*) FROM jevend_annonces_images WHERE id_annonces = ?");
                $stmt_count->execute([$id_annonce]);
                $nb_photos_existantes = $stmt_count->fetchColumn();

                foreach ($_POST['images_base64'] as $index => $data_base64) {
                    if (!empty($data_base64) && ($nb_photos_existantes < 5)) {
                        $data_base64 = str_replace('data:image/jpeg;base64,', '', $data_base64);
                        $data_base64 = str_replace(' ', '+', $data_base64);
                        $donnees_decodees = base64_decode($data_base64);

                        if ($donnees_decodees !== false) {
                            $nom_fichier_jpg = uniqid('img_', true) . '.jpg';
                            $chemin_final = 'uploads/' . $nom_fichier_jpg;

                            if (file_put_contents($chemin_final, $donnees_decodees)) {
                                $est_principale = ($nb_photos_existantes === 0) ? 1 : 0;

                                $stmt_img = $bdd->prepare("INSERT INTO jevend_annonces_images (id_annonces, nom_fichier, est_principale) VALUES (?, ?, ?)");
                                $stmt_img->execute([$id_annonce, $nom_fichier_jpg, $est_principale]);

                                if ($est_principale === 1) {
                                    $bdd->prepare("UPDATE jevend_annonces SET image_courante = ? WHERE id_annonces = ?")->execute([$nom_fichier_jpg, $id_annonce]);
                                }

                                $nb_photos_existantes++;
                            }
                        }
                    }
                }
            }

            $bdd->commit();
            $succes = "L'annonce a été mise à jour avec succès !";
            
            $stmt->execute([$id_annonce, $id_utilisateur]);
            $annonce = $stmt->fetch();

        } catch (PDOException $e) {
            $bdd->rollBack();
            $erreur = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// Récupération des photos actuelles pour la galerie
$photos_actuelles = [];
try {
    $stmt_pics = $bdd->prepare("SELECT * FROM jevend_annonces_images WHERE id_annonces = ? ORDER BY est_principale DESC, date_envoi ASC");
    $stmt_pics->execute([$id_annonce]);
    $photos_actuelles = $stmt_pics->fetchAll();
} catch (PDOException $e) {
    $erreur = "Erreur de chargement de la galerie : " . $e->getMessage();
}

$nb_photos_actuelles = count($photos_actuelles);
$emplacements_libres = 5 - $nb_photos_actuelles;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier votre annonce - jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_modif_ann.css?v=1.0">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="form-bloc">
        <h2>✏️ Modifier votre annonce</h2>

        <?php if (!empty($erreur)): ?>
            <div class="erreur-msg"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="succes-msg"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <form id="form-modifier" action="modifier_annonce.php?id=<?php echo $id_annonce; ?>" method="POST">
            
            <div class="champ-groupe">
                <label for="titre_objet_nettoye">Titre de l'annonce (Max 60 caractères)</label>
                <input type="text" id="titre_objet_nettoye" name="titre_objet_nettoye" maxlength="60" value="<?php echo htmlspecialchars(stripslashes(html_entity_decode($annonce['titre_objet_nettoye'], ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="champ-groupe">
                <label for="description_service">Description du bien ou du service</label>
                <textarea id="description_service" name="description_service" rows="5" required><?php echo htmlspecialchars(stripslashes(html_entity_decode($annonce['description_service'], ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="champ-groupe">
                <label for="prix">Prix ($) <span style="font-size:0.8rem; color:#64748b;">(Laisser vide si inchangé ou service)</span></label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?php echo ($annonce['prix'] !== null) ? htmlspecialchars($annonce['prix']) : ''; ?>">
            </div>

            <div class="champ-groupe section-galerie">
                <label class="titre-galerie">📷 Gestion de la galerie (<?php echo $nb_photos_actuelles; ?>/5 photo(s))</label>
                
                <?php if ($nb_photos_actuelles > 0): ?>
                    <div class="galerie-grille">
                        <?php foreach ($photos_actuelles as $pic): ?>
                            <div class="vignette-carte">
                                <div class="vignette-img-wrapper">
                                    <img src="uploads/<?php echo htmlspecialchars($pic['nom_fichier']); ?>" alt="Photo de l'annonce">
                                    <?php if ($pic['est_principale'] == 1): ?>
                                        <span class="badge-principale">Principale</span>
                                    <?php endif; ?>
                                </div>
                                <a href="modifier_annonce.php?id=<?php echo $id_annonce; ?>&action=supprimer_photo&id_photo=<?php echo $pic['id_image']; ?>" onclick="return confirm('Retirer définitivement cette photo du serveur ?');" class="btn-retirer-photo">
                                    🗑️ Retirer
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($emplacements_libres > 0): ?>
                    <p style="font-size: 0.85rem; color: #475569; margin-bottom: 8px;">Ajouter de nouvelles photos (Format JPG, PNG, WEBP) :</p>
                    <div class="zone-photos-inputs">
                        <?php for ($i = 0; $i < $emplacements_libres; $i++): ?>
                            <input type="file" class="input-image-comp" data-index="<?php echo $i; ?>" accept="image/jpeg, image/png, image/webp">
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <div class="alerte-galerie-limite">
                        🚀 Limite maximale atteinte (5 photos). Retirez une photo pour pouvoir en ajouter une nouvelle.
                    </div>
                <?php endif; ?>

                <div id="conteneur-base64"></div>
            </div>

            <button type="submit" class="btn-action-submit">💾 Sauvegarder les modifications</button>
            
            <div class="liens-navigation">
                <a href="espace_membre.php">← Retour à mon tableau de bord</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll(".input-image-comp");
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
