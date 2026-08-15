<?php
// =============================================================================
// NOM DU SCRIPT : inscription_execute.php
// REVISION     : 1.0 - Traitement sécurisé de l'inscription (Séparation Vue/Logique)
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

if (isset($_SESSION['id_utilisateur'])) {
    header('Location: espace_membre.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inscription.php');
    exit();
}

// Nettoyage et sanitisation des entrées
$nom            = trim($_POST['nom'] ?? '');
$courriel       = strtolower(trim($_POST['courriel'] ?? ''));
$cellulaire     = trim($_POST['cellulaire'] ?? '');
$id_ville       = (int)($_POST['id_ville'] ?? 0);
$type_compte    = (isset($_POST['type_compte']) && $_POST['type_compte'] === 'pro') ? 'pro' : 'particulier';

// Champs spécifiques PRO
$nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
$telephone_pro  = trim($_POST['telephone_pro'] ?? '');
$adresse_pro    = trim($_POST['adresse_pro'] ?? '');
$site_web       = trim($_POST['site_web'] ?? '');

// Mémorisation des saisies en session en cas d'erreur
$_SESSION['form_inscription'] = [
    'nom' => $nom,
    'courriel' => $courriel,
    'cellulaire' => $cellulaire,
    'id_ville' => $id_ville,
    'type_compte' => $type_compte,
    'nom_entreprise' => $nom_entreprise,
    'telephone_pro' => $telephone_pro,
    'adresse_pro' => $adresse_pro,
    'site_web' => $site_web
];

// Formater l'URL du site web avec https:// si nécessaire
if (!empty($site_web) && !preg_match("~^(?:f|ht)tps?://~i", $site_web)) {
    $site_web = "https://" . $site_web;
}

// Validations côté serveur
if (empty($nom) || empty($courriel) || empty($cellulaire) || $id_ville <= 0) {
    $_SESSION['erreur_inscription'] = "Tous les champs de base sont obligatoires.";
    header('Location: inscription.php');
    exit();
}

if (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erreur_inscription'] = "L'adresse courriel n'est pas valide.";
    header('Location: inscription.php');
    exit();
}

if ($type_compte === 'pro') {
    if (empty($nom_entreprise) || empty($telephone_pro) || empty($adresse_pro)) {
        $_SESSION['erreur_inscription'] = "Le nom, le téléphone direct et l'adresse commerciale sont obligatoires pour un profil commercial.";
        header('Location: inscription.php');
        exit();
    }
}

try {
    // Vérifier si le courriel est déjà enregistré
    $check = $bdd->prepare("SELECT id_utilisateur FROM jevend_utilisateurs WHERE courriel = ?");
    $check->execute([$courriel]);
    
    if ($check->fetch()) {
        $_SESSION['erreur_inscription'] = "Cette adresse courriel est déjà associée à un compte.";
        header('Location: inscription.php');
        exit();
    }

    // Génération du premier code secret à 6 chiffres
    $premier_code = rand(100000, 999999);
    $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $mot_de_passe_factice = password_hash(uniqid(rand(), true), PASSWORD_DEFAULT);

    // Insertion BDD sans le champ NEQ
    $insert = $bdd->prepare("
        INSERT INTO jevend_utilisateurs 
        (nom, courriel, cellulaire, id_ville, mot_de_passe, statut, type_compte, nom_entreprise, telephone_pro, adresse_pro, site_web, neq, jeton_connexion, jeton_expiration) 
        VALUES (?, ?, ?, ?, ?, 'actif', ?, ?, ?, ?, ?, NULL, ?, ?)
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
        $premier_code, 
        $expiration
    ]);

    // Envoi du courriel
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: jevend.com <no-reply@jevend.com>\r\n";
    $headers .= "Reply-To: no-reply@jevend.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $sujet = "Bienvenue sur jevend.com - Confirmez votre compte !";
$nom_propre = htmlspecialchars($type_compte === 'pro' ? $nom_entreprise : $nom);

$message = "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, sans-serif;'>
    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f1f5f9; padding: 40px 10px;'>
        <tr>
            <td align='center'>
                <table width='100%' style='max-width: 550px; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;' border='0' cellspacing='0' cellpadding='0'>
                    <!-- EN-TÊTE SANS IMAGE BRISÉE -->
                    <tr>
                        <td align='center' style='background-color: #0f172a; padding: 25px;'>
                            <h1 style='margin: 0; color: #ffffff; font-size: 1.8rem; font-style: italic; letter-spacing: -1px;'>jevend.com</h1>
                        </td>
                    </tr>
                    <!-- CORPS DU MESSAGE -->
                    <tr>
                        <td style='padding: 40px 30px; color: #334155; text-align: center;'>
                            <h2 style='margin: 0 0 15px 0; font-size: 1.4rem; color: #0f172a;'>
                                Bienvenue chez nous, " . $nom_propre . " !
                            </h2>
                            <p style='margin: 0 0 25px 0; font-size: 0.95rem; color: #475569;'>
                                Entrez le code unique ci-dessous pour valider votre compte :
                            </p>
                            <!-- PAVE CODE SECRET MODERNE -->
                            <div style='text-align: center; margin-bottom: 25px;'>
                                <div style='background-color: #2563eb; color: #ffffff; font-size: 2.2rem; font-weight: bold; letter-spacing: 8px; padding: 15px 25px; display: inline-block; border-radius: 8px; font-family: Arial, sans-serif; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);'>
                                    " . $premier_code . "
                                </div>
                            </div>
                            <p style='margin: 0; font-size: 0.8rem; color: #94a3b8;'>
                                Ce code secret est valide pendant 15 minutes.
                            </p>
                        </td>
                    </tr>
                    <!-- PIED DE PAGE -->
                    <tr>
                        <td align='center' style='background-color: #f8fafc; padding: 15px; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #94a3b8;'>
                            « Premier arrivé, premier vendu » — jevend.com
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
    $_SESSION['succes_connexion'] = "Votre compte a été créé avec succès ! Entrez le code secret envoyé à votre adresse courriel.";
    unset($_SESSION['form_inscription']);

    header('Location: connexion.php');
    exit();

} catch (PDOException $e) {
    $_SESSION['erreur_inscription'] = "Une erreur technique s'est produite lors de l'enregistrement.";
    header('Location: inscription.php');
    exit();
}
