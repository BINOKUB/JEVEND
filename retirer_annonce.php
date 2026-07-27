<?php
session_start();

// NOM DU SCRIPT : retirer_annonce.php
// REVISION : 1.0 - Suppression sécurisée de l'annonce et de son image physique
// SCRIPT COMPLET ET SUIVI

if (!isset($_SESSION['id_utilisateur']) || !isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: espace_membre.php');
    exit();
}

require_once 'config.php';

$id_annonce = intval($_GET['id']);
$id_utilisateur = $_SESSION['id_utilisateur'];

try {
    // 1. Récupérer le nom de l'image pour nettoyage du serveur
    $stmt_img = $bdd->prepare("SELECT image_courante FROM jevend_annonces WHERE id_annonces = ? AND id_utilisateur = ?");
    $stmt_img->execute([$id_annonce, $id_utilisateur]);
    $image = $stmt_img->fetchColumn();

    // 2. Supprimer l'annonce de la base de données
    $stmt_del = $bdd->prepare("DELETE FROM jevend_annonces WHERE id_annonces = ? AND id_utilisateur = ?");
    
    if ($stmt_del->execute([$id_annonce, $id_utilisateur])) {
        // 3. Si un fichier image physique existe, on le supprime pour ne pas gaspiller d'espace disque
        if (!empty($image) && file_exists('uploads/' . $image)) {
            @unlink('uploads/' . $image);
        }
    }
} catch (PDOException $e) {
    // En cas d'erreur SQL, on intercepte sans faire planter la redirection
}

header('Location: espace_membre.php');
exit();
