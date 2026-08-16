<?php
// =============================================================================
// SCRIPT : inscription.php
// REVISION : 3.0 - Ajout des champs Adresse Pro & Site Web pour le profil Commercial
// NOM DU SCRIPT : inscription.php
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Si l'utilisateur est déjà connecté, on le redirige selon son type de compte
if (isset($_SESSION['id_utilisateur'])) {
    if (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'pro') {
        header('Location: espace_membre_pro.php');
    } else {
        header('Location: espace_membre.php');
    }
    exit();
}

$erreur = "";
$succes = "";

// 1. EXTRACTION DES VILLES POUR LE SÉLECTEUR DYNAMIQUE
try {
    $stmt_villes = $bdd->query("SELECT id_ville, nom_ville FROM jevend_villes ORDER BY nom_ville ASC");
    $villes = $stmt_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $villes = [];
    $erreur = "Impossible de charger la liste des villes.";
}

// 2. TRAITEMENT DE L'INSCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = trim($_POST['nom'] ?? '');
    $courriel       = trim($_POST['courriel'] ?? '');
    $cellulaire     = trim($_POST['cellulaire'] ?? '');
    $id_ville       = (int)($_POST['id_ville'] ?? 0);
    $type_compte    = (isset($_POST['type_compte']) && $_POST['type_compte'] === 'pro') ? 'pro' : 'particulier';
    
    // Champs spécifiques PRO
    $nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
    $telephone_pro  = trim($_POST['telephone_pro'] ?? '');
    $adresse_pro    = trim($_POST['adresse_pro'] ?? '');
    $site_web       = trim($_POST['site_web'] ?? '');
    $neq            = trim($_POST['neq'] ?? '');

    // Formater l'URL du site web avec https:// si nécessaire
    if (!empty($site_web) && !preg_match("~^(?:f|ht)tps?://~i", $site_web)) {
        $site_web = "https://" . $site_web;
    }

    // Validations de base
    if (empty($nom) || empty($courriel) || empty($cellulaire) || $id_ville <= 0) {
        $erreur = "Tous les champs de base sont obligatoires.";
    } elseif ($type_compte === 'pro' && empty($nom_entreprise)) {
        $erreur = "Le nom officiel de votre entreprise est obligatoire pour un compte marchand.";
    } elseif (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse courriel n'est pas valide.";
    } else {
        try {
            // Vérifier si le courriel est déjà utilisé
            $check = $bdd->prepare("SELECT id_utilisateur FROM jevend_utilisateurs WHERE courriel = ?");
            $check->execute([$courriel]);
            
            if ($check->fetch()) {
                $erreur = "Cette adresse courriel est déjà associée à un compte.";
            } else {
                // Génération du tout premier jeton de validation d'entrée
                $premier_code = rand(100000, 999999);
                $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // Mot de passe système factice chiffré
                $mot_de_passe_factice = password_hash(uniqid(rand(), true), PASSWORD_DEFAULT);

                // Insertion propre en base de données avec prise en compte complète du profil PRO
                $insert = $bdd->prepare("
                    INSERT INTO jevend_utilisateurs 
                    (nom, courriel, cellulaire, id_ville, mot_de_passe, statut, type_compte, nom_entreprise, telephone_pro, adresse_pro, site_web, neq, jeton_connexion, jeton_expiration) 
                    VALUES (?, ?, ?, ?, ?, 'actif', ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([
                    $nom, 
                    $courriel, 
                    $cellulaire, 
                    $id_ville, 
                    $mot_de_passe_factice, 
                    $type_compte, 
                    ($type_compte === 'pro' ? $nom_entreprise : NULL), 
                    ($type_compte === 'pro' ? $telephone_pro : NULL), 
                    ($type_compte === 'pro' ? $adresse_pro : NULL), 
                    ($type_compte === 'pro' ? $site_web : NULL), 
                    ($type_compte === 'pro' ? $neq : NULL), 
                    $premier_code, 
                    $expiration
                ]);

                // Configuration des entêtes pour l'envoi en HTML
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: jevend.com <no-reply@jevend.com>\r\n";
                $headers .= "Reply-To: no-reply@jevend.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                $sujet = "Bienvenue sur jevend.com - Confirmez votre compte !";
                $nom_propre = htmlspecialchars($type_compte === 'pro' ? $nom_entreprise : $nom);
                $annee_courante = date('Y');

                $message = "
                <!DOCTYPE html>
                <html lang='fr'>
                <head><meta charset='UTF-8'></head>
                <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, sans-serif;'>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f1f5f9; padding: 40px 10px;'>
                        <tr>
                            <td align='center'>
                                <table width='100%' max-width='550' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;'>
                                    <tr>
                                        <td align='center' style='background-color: #0f172a; padding: 25px;'>
                                            <img src='http://192.168.40.2/assets/LOGO_JEVEND-COM.jpeg' alt='jevend.com' style='max-height: 50px; border-radius: 4px;'>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 40px 30px; color: #334155;'>
                                            <h1 style='margin: 0 0 15px 0; font-size: 1.4rem; color: #0f172a; text-align: center;'>
                                                Bienvenue chez nous, " . $nom_propre . " !
                                            </h1>
                                            <p style='margin: 0 0 25px 0; font-size: 0.95rem; color: #475569; text-align: center;'>
                                                Entrez le code unique ci-dessous pour valider votre compte :
                                            </p>
                                            <div style='text-align: center; margin-bottom: 25px;'>
                                                <div style='background-color: #f8fafc; border: 2px dashed #2563eb; color: #2563eb; font-size: 2.2rem; font-weight: bold; letter-spacing: 6px; padding: 15px 30px; display: inline-block; font-family: Courier New, monospace;'>
                                                    " . $premier_code . "
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>";

                @mail($courriel, $sujet, $message, $headers);

                $_SESSION['temp_email_connexion'] = $courriel;
                header('Location: connexion.php');
                exit();
            }
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Créer un compte — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body { max-width: 100% !important; overflow-x: hidden !important; width: 100% !important; margin: 0; padding: 0; box-sizing: border-box; }
        @media (max-width: 768px) {
            .admin-conteneur { max-width: 100% !important; width: 100% !important; padding: 0 15px !important; margin-top: 20px !important; box-sizing: border-box !important; }
            .form-bloc { width: 100% !important; box-sizing: border-box !important; padding: 20px !important; }
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="admin-conteneur" style="max-width: 500px; margin-top: 40px; margin-left: auto; margin-right: auto; box-sizing: border-box;">
        <div class="form-bloc" style="background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); box-sizing: border-box;">
            
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="margin: 0 0 5px 0; color: #1e3a8a;">Rejoindre la communauté</h2>
                <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Inscrivez-vous en quelques secondes sans vous soucier d'un mot de passe.</p>
            </div>

            <?php if (!empty($erreur)): ?>
                <div class="erreur-msg" style="background-color: #fef2f2; color: #991b1b; padding: 10px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 15px; border: 1px solid #fecaca; font-weight: bold; text-align: center;">
                    ⚠️ <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form action="inscription.php" method="POST">
                
                <!-- SÉLECTEUR DU TYPE DE COMPTE -->
                <div class="champ-groupe" style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 8px; color: #0f172a;">Type de compte :</label>
                    <div style="display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <label style="cursor: pointer; font-weight: bold; margin: 0; display: flex; align-items: center; gap: 6px; color: #334155;">
                            <input type="radio" name="type_compte" value="particulier" <?= (!isset($_POST['type_compte']) || $_POST['type_compte'] === 'particulier') ? 'checked' : '' ?> onclick="basculerChampsPro(false)"> 
                            👤 Particulier
                        </label>
                        <label style="cursor: pointer; font-weight: bold; margin: 0; display: flex; align-items: center; gap: 6px; color: #2563eb;">
                            <input type="radio" name="type_compte" value="pro" <?= (isset($_POST['type_compte']) && $_POST['type_compte'] === 'pro') ? 'checked' : '' ?> onclick="basculerChampsPro(true)"> 
                            🏢 Commerce / Entreprise
                        </label>
                    </div>
                </div>

                <!-- BLOC DES CHAMPS PRO (DYNAMIQUE) -->
                <div id="bloc-champs-pro" style="display: <?= (isset($_POST['type_compte']) && $_POST['type_compte'] === 'pro') ? 'block' : 'none' ?>; background: #eff6ff; padding: 15px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 20px; box-sizing: border-box;">
                    <h4 style="margin-top: 0; color: #1e40af; margin-bottom: 12px; font-size: 0.95rem;">🏢 Profil Commercial</h4>
                    
                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="nom_entreprise" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Nom officiel de l'entreprise * :</label>
                        <input type="text" name="nom_entreprise" id="nom_entreprise" placeholder="Ex: Garage Auto Matane Inc." value="<?= htmlspecialchars($_POST['nom_entreprise'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                    </div>

                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="telephone_pro" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Téléphone commercial direct :</label>
                        <input type="text" name="telephone_pro" id="telephone_pro" placeholder="Ex: 418-555-0199" value="<?= htmlspecialchars($_POST['telephone_pro'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                    </div>

                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="adresse_pro" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Adresse commerciale :</label>
                        <input type="text" name="adresse_pro" id="adresse_pro" placeholder="Ex: 123 Avenue de la Phare, Matane" value="<?= htmlspecialchars($_POST['adresse_pro'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                    </div>

                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="site_web" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Site Web de l'entreprise (Facultatif) :</label>
                        <input type="text" name="site_web" id="site_web" placeholder="Ex: https://mon-garage.com" value="<?= htmlspecialchars($_POST['site_web'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                    </div>

                    <div class="champ-groupe">
                        <label for="neq" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">NEQ ou No. TPS/TVQ (Facultatif) :</label>
                        <input type="text" name="neq" id="neq" placeholder="Ex: 1234567890" value="<?= htmlspecialchars($_POST['neq'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                    </div>
                </div>

                <div class="champ-groupe" style="margin-bottom: 15px;">
                    <label for="nom">Nom du responsable du compte :</label>
                    <input type="text" name="nom" id="nom" placeholder="Ex: Jean Tremblay..." value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>

                <div class="champ-groupe" style="margin-bottom: 15px;">
                    <label for="courriel">Votre adresse courriel :</label>
                    <input type="email" name="courriel" id="courriel" placeholder="Ex: jean.tremblay@gmail.com" value="<?= htmlspecialchars($_POST['courriel'] ?? '') ?>" required style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>

                <div class="champ-groupe" style="margin-bottom: 15px;">
                    <label for="cellulaire">Numéro de cellulaire (Pour sécurité / validation) :</label>
                    <input type="tel" name="cellulaire" id="cellulaire" placeholder="Ex: 418-555-1234" value="<?= htmlspecialchars($_POST['cellulaire'] ?? '') ?>" required style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>

                <div class="champ-groupe" style="margin-bottom: 20px;">
                    <label for="id_ville">Votre ville de résidence / commerce :</label>
                    <select name="id_ville" id="id_ville" required style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <option value="">-- Sélectionnez votre ville --</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= $v['id_ville'] ?>" <?= (isset($_POST['id_ville']) && $_POST['id_ville'] == $v['id_ville']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-action" style="width: 100%; font-weight: bold; padding: 12px; margin-top: 10px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 4px; cursor: pointer;">
                    🎯 Créer mon compte
                </button>
            </form>

            <div style="text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 0.85rem; color: #64748b;">
                Déjà inscrit sur jevend ? <a href="connexion.php" style="color: #2563eb; font-weight: bold; text-decoration: none;">Me connecter</a>
            </div>

        </div>
    </div>

    <script>
    function basculerChampsPro(estPro) {
        const blocPro = document.getElementById('bloc-champs-pro');
        const inputNomEntreprise = document.getElementById('nom_entreprise');
        
        if (estPro) {
            blocPro.style.display = 'block';
            inputNomEntreprise.required = true;
        } else {
            blocPro.style.display = 'none';
            inputNomEntreprise.required = false;
        }
    }
    </script>
</body>
</html>
