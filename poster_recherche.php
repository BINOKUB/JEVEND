<?php
// =============================================================================
// NOM DU SCRIPT : poster_recherche.php
// REVISION : 1.3 - Nettoyage CSS complet, 100% modulaire (poster_recherche.css)
// =============================================================================
session_start();
require_once 'config.php';
include 'partials/_quota_jecherche.php';

// Protection : L'utilisateur doit être connecté pour poster une demande
$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if (!$id_utilisateur) {
    $url_courante = urlencode($_SERVER['REQUEST_URI']);
    header("Location: connexion.php?redirect=" . $url_courante);
    exit();
}

$erreur = "";
$succes = "";

// EXTRACTION DES CATÉGORIES HIÉRARCHISÉES
$categories_parentes = [];
try {
    $stmt_p = $bdd->query("SELECT id_categorie, nom_fr FROM jevend_categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY nom_fr ASC");
    $parents = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    foreach ($parents as $parent) {
        $id_p = $parent['id_categorie'];
        $stmt_s = $bdd->prepare("SELECT id_categorie, nom_fr FROM jevend_categories WHERE parent_id = ? ORDER BY nom_fr ASC");
        $stmt_s->execute([$id_p]);
        $sous_cats = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

        $categories_parentes[] = [
            'parent' => $parent,
            'enfants' => $sous_cats
        ];
    }
} catch (PDOException $e) { }

// Extraction des villes
$villes = [];
try {
    $stmt_villes = $bdd->query("SELECT id_ville, nom_ville FROM jevend_villes ORDER BY nom_ville ASC");
    $villes = $stmt_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// Extraction de la ville par défaut du membre
$id_ville_membre = 0;
try {
    $stmt_user_ville = $bdd->prepare("SELECT id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
    $stmt_user_ville->execute([$id_utilisateur]);
    $id_ville_membre = (int)$stmt_user_ville->fetchColumn();
} catch (PDOException $e) { }

// Variables pour la persistance du formulaire (Sticky values)
$titre_val  = $_POST['titre_recherche'] ?? ($_GET['q'] ?? '');
$cat_val    = (int)($_POST['id_categorie'] ?? 0);
$ville_val  = (int)($_POST['id_ville'] ?? $id_ville_membre);
$budget_val = $_POST['budget_max'] ?? '';
$desc_val   = $_POST['description'] ?? '';

// TRAITEMENT DU FORMULAIRE EN POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_poster_recherche'])) {
    $titre_recherche = trim($_POST['titre_recherche'] ?? '');
    $id_categorie   = (int)($_POST['id_categorie'] ?? 0);
    $id_ville       = (int)($_POST['id_ville'] ?? 0);
    $budget_max     = !empty($_POST['budget_max']) ? (float)str_replace(',', '.', $_POST['budget_max']) : NULL;
    $description    = trim($_POST['description'] ?? '');

    // Mise à jour des valeurs pour l'affichage en cas d'erreur
    $titre_val  = $titre_recherche;
    $cat_val    = $id_categorie;
    $ville_val  = $id_ville;
    $budget_val = $_POST['budget_max'] ?? '';
    $desc_val   = $description;

    if (empty($titre_recherche) || mb_strlen($titre_recherche) < 3) {
        $erreur = "Veuillez préciser l'objet que vous cherchez (minimum 3 caractères).";
    } elseif ($id_categorie <= 0) {
        $erreur = "Veuillez sélectionner une catégorie.";
    } elseif ($id_ville <= 0) {
        $erreur = "Veuillez sélectionner une municipalité.";
    } else {
        $nom_image_bd = NULL;

        // Gestion et traitement de l'image de référence (Compression 80% & Format JPEG)
        if (isset($_FILES['image_ref']) && $_FILES['image_ref']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['image_ref']['tmp_name']);
            finfo_close($finfo);

            $types_autorises = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($mime_type, $types_autorises)) {
                $nom_image_bd = "cherche_" . uniqid() . ".jpg";
                $dossier_destination = "uploads/" . $nom_image_bd;

                $image_source = null;
                if ($mime_type === 'image/jpeg') {
                    $image_source = imagecreatefromjpeg($_FILES['image_ref']['tmp_name']);
                } elseif ($mime_type === 'image/png') {
                    $image_source = imagecreatefrompng($_FILES['image_ref']['tmp_name']);
                } elseif ($mime_type === 'image/webp') {
                    $image_source = imagecreatefromwebp($_FILES['image_ref']['tmp_name']);
                }

                if ($image_source) {
                    $largeur_origine = imagesx($image_source);
                    $hauteur_origine = imagesy($image_source);

                    $largeur_max = 800;
                    $hauteur_max = 800;

                    $largeur_finale = $largeur_origine;
                    $hauteur_finale = $hauteur_origine;

                    if ($largeur_origine > $largeur_max || $hauteur_origine > $hauteur_max) {
                        $ratio = $largeur_origine / $hauteur_origine;
                        if ($largeur_max / $hauteur_max > $ratio) {
                            $largeur_finale = $hauteur_max * $ratio;
                            $hauteur_finale = $hauteur_max;
                        } else {
                            $largeur_finale = $largeur_max;
                            $hauteur_finale = $largeur_max / $ratio;
                        }
                    }

                    $image_finale = imagecreatetruecolor($largeur_finale, $hauteur_finale);
                    $blanc = imagecolorallocate($image_finale, 255, 255, 255);
                    imagefilledrectangle($image_finale, 0, 0, $largeur_finale, $hauteur_finale, $blanc);

                    imagecopyresampled(
                        $image_finale, $image_source,
                        0, 0, 0, 0,
                        $largeur_finale, $hauteur_finale,
                        $largeur_origine, $hauteur_origine
                    );

                    if (!imagejpeg($image_finale, $dossier_destination, 80)) {
                        $nom_image_bd = NULL;
                    }

                    imagedestroy($image_source);
                    imagedestroy($image_finale);
                } else {
                    $nom_image_bd = NULL;
                }
            }
        }

        $date_expiration = date('Y-m-d H:i:s', strtotime('+30 days'));

        try {
            $stmt_ins = $bdd->prepare("
                INSERT INTO jevend_recherches 
                (id_utilisateur, id_categorie, id_ville, titre_recherche, description, budget_max, image_reference, date_expiration) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ins->execute([
                $id_utilisateur,
                $id_categorie,
                $id_ville,
                $titre_recherche,
                $description,
                $budget_max,
                $nom_image_bd,
                $date_expiration
            ]);

            header("Location: zone_cherche.php?succes=1");
            exit();

        } catch (PDOException $e) {
            $erreur = "Une erreur technique est survenue lors de la création de votre demande.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster une demande — jevend.com</title>
    <!-- FEUILLE DE STYLE UNIQUE ET MODULAIRE -->
    <link rel="stylesheet" href="poster_recherche.css">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="conteneur-form-cherche">
        <div class="entete-form-cherche">
            <h2>🎯 Poster une demande d'achat</h2>
            <p>Dites aux vendeurs de votre secteur ce que vous recherchez !</p>
        </div>

        <?php if (!empty($erreur)): ?>
            <div style="background-color: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #fecaca; font-weight: bold; text-align: center;">
                ⚠️ <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form action="poster_recherche.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action_poster_recherche" value="1">

            <div class="champ-groupe-cherche">
                <label for="titre_recherche">Que cherchez-vous exactement ? *</label>
                <input type="text" name="titre_recherche" id="titre_recherche" placeholder="Ex: Tondeuse à gazon fonctionnelle, Banc de scie..." value="<?= htmlspecialchars($titre_val) ?>" required autofocus>
            </div>

            <div class="grille-champs-cherche">
                <div class="champ-groupe-cherche">
                    <label for="id_categorie">Catégorie *</label>

                   <select name="id_categorie" id="id_categorie" required>
                        <option value="0">-- Choisir une catégorie --</option>
                        <?php foreach ($categories_parentes as $groupe): ?>
                            <optgroup label="📁 <?= htmlspecialchars($groupe['parent']['nom_fr']) ?>">
                                <option value="<?= $groupe['parent']['id_categorie'] ?>" <?= ($groupe['parent']['id_categorie'] == $cat_val) ? 'selected' : '' ?>>
                                    &nbsp;&nbsp;📂 <?= htmlspecialchars($groupe['parent']['nom_fr']) ?> (Général)
                                </option>
                                <?php foreach ($groupe['enfants'] as $enfant): ?>
                                    <option value="<?= $enfant['id_categorie'] ?>" <?= ($enfant['id_categorie'] == $cat_val) ? 'selected' : '' ?>>
                                        &nbsp;&nbsp;&nbsp;&nbsp;↳ 📄 <?= htmlspecialchars($enfant['nom_fr']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>


                </div>

                <div class="champ-groupe-cherche">
                    <label for="id_ville">Municipalité / Secteur *</label>
                    <select name="id_ville" id="id_ville" required>
                        <option value="0">-- Choisir une ville --</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= $v['id_ville'] ?>" <?= ($v['id_ville'] == $ville_val) ? 'selected' : '' ?>>
                                📍 <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grille-champs-cherche">
                <div class="champ-groupe-cherche">
                    <label for="budget_max">Budget maximal ($ CAD) <span style="font-weight:normal; color:#64748b;">(Optionnel)</span></label>
                    <input type="text" name="budget_max" id="budget_max" placeholder="Ex: 150" value="<?= htmlspecialchars($budget_val) ?>">
                </div>

                <div class="champ-groupe-cherche">
                    <label for="image_ref">Photo d'exemple / de référence <span style="font-weight:normal; color:#64748b;">(Optionnel)</span></label>
                    <input type="file" name="image_ref" id="image_ref" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>

            <div class="champ-groupe-cherche">
                <label for="description">Détails ou exigences particulières <span style="font-weight:normal; color:#64748b;">(Optionnel)</span></label>
                <textarea name="description" id="description" rows="4" placeholder="Précisez la marque souhaitée, l'état minimal accepté ou si vous pouvez vous déplacer..."><?= htmlspecialchars($desc_val) ?></textarea>
            </div>

            <button type="submit" class="btn-soumettre-cherche">
                🚀 Publier ma demande gratuitement
            </button>
        </form>
    </div>

</body>
</html>
