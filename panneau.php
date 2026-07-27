<?php
/*
====================================================
Fichier       : panneau.php
Révision      : v2.3
Description   : Panneau de configuration - JeVend.com
Nouveautés    : 
  - Conservation automatique de l'onglet actif après soumission de formulaire (via URL hash #)
  - Mise à jour dynamique de l'URL lors du changement d'onglet
====================================================
*/

session_start();

// PROTECTION ABSOLUE : L'adresse courriel doit obligatoirement être celle de l'admin suprême
$courriel_admin_supreme = 'douimet61@gmail.com';

if (
    !isset($_SESSION['id_utilisateur']) || 
    !isset($_SESSION['role']) || 
    !isset($_SESSION['courriel']) || 
    $_SESSION['role'] !== 'admin' || 
    $_SESSION['courriel'] !== $courriel_admin_supreme
) {
    header('Location: connexion.php');
    exit();
}

// Appel de la connexion à la base de données
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Administration — jevend.com</title>
    <link rel="stylesheet" href="style_panneau.css">
</head>
<body class="admin-body">

    <!-- BANDEAU DE TÊTE FONCÉ -->
    <div class="admin-barre-haut">
        <h1>Panneau de Configuration Secrète</h1>
        <div class="statut-admin">
            Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong>
            <a href="deconnexion.php" style="margin-left: 15px; color: #f87171; font-weight: bold; text-decoration: none;">Déconnexion</a>
        </div>
    </div>

    <div class="admin-conteneur" style="max-width: 1200px; margin: 25px auto; padding: 0 15px; box-sizing: border-box;">
        
        <!-- INCLUSION DE LA BARRE DE NAVIGATION MODULAIRE DES ONGLETS -->
        <?php 
        if (file_exists('admin_modules/_admin_nav.php')) {
            include 'admin_modules/_admin_nav.php';
        } elseif (file_exists('_admin_nav.php')) {
            include '_admin_nav.php';
        }
        ?>

        <!-- MODULE 1 : TRAFFIC -->
        <div id="onglet-traffic" class="section-panneau active">
            <?php if (file_exists('admin_modules/_admin_traffic.php')) include 'admin_modules/_admin_traffic.php'; ?>
        </div>

        <!-- MODULE 2 : MEMBRES -->
        <div id="onglet-membres" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_membres.php')) include 'admin_modules/_admin_membres.php'; ?>
        </div>

        <!-- MODULE 3 : CATÉGORIES -->
        <div id="onglet-categories" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_categories.php')) include 'admin_modules/_admin_categories.php'; ?>
        </div>

        <!-- MODULE 4 : TARIFS -->
        <div id="onglet-tarifs" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_tarifs.php')) include 'admin_modules/_admin_tarifs.php'; ?>
        </div>

        <!-- MODULE 5 : COMPTABILITÉ -->
        <div id="onglet-compta" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_compta.php')) include 'admin_modules/_admin_compta.php'; ?>
        </div>

        <!-- MODULE 6 : INFO DIRECTION -->
        <div id="onglet-admin-ban" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_ban.php')) include 'admin_modules/_admin_ban.php'; ?>
        </div>

        <!-- MODULE 7 : F.A.Q. ADMIN -->
        <div id="onglet-faq" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_faq.php')) include 'admin_modules/_admin_faq.php'; ?>
        </div>

    </div>

    <script>
    function changerOnglet(idSection) {
        const sections = document.querySelectorAll('.section-panneau');
        sections.forEach(sec => sec.classList.remove('active'));

        const boutons = document.querySelectorAll('.onglet-btn');
        boutons.forEach(btn => btn.classList.remove('actif'));

        const sectionCible = document.getElementById(idSection);
        if (sectionCible) {
            sectionCible.classList.add('active');
        }

        const boutonActif = Array.from(boutons).find(btn => btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(idSection));
        if (boutonActif) {
            boutonActif.classList.add('actif');
        }

        // Met à jour l'ancre dans l'URL sans recharger la page
        if (history.pushState) {
            history.pushState(null, null, '#' + idSection);
        } else {
            location.hash = '#' + idSection;
        }
    }

    // Réouverture automatique de l'onglet actif si une ancre est présente dans l'URL (#onglet-faq, etc.)
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            changerOnglet(hash);
        }
    });
    </script>
</body>
</html>
