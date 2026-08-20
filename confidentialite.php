<?php
// =============================================================================
// NOM DU SCRIPT : confidentialite.php
// REVISION : 2.0 - Politique de confidentialité officielle et adaptée à Jevend.com
// =============================================================================
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de confidentialité — Jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f8fafc !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            float: none !important;
        }
        .container-global-legal {
            width: 100% !important;
            max-width: 900px !important;
            margin: 40px auto !important;
            float: none !important;
            clear: both !important;
            padding: 0 20px;
            box-sizing: border-box;
        }
        .wrapper-contenu-legal {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            padding: 40px;
            box-sizing: border-box;
            font-family: sans-serif;
            color: #334155;
            line-height: 1.6;
        }
        .wrapper-contenu-legal h1 {
            color: #0f172a;
            font-size: 1.8rem;
            margin-top: 0;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
        }
        .wrapper-contenu-legal h2 {
            color: #1e293b;
            font-size: 1.2rem;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        .wrapper-contenu-legal p {
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        .wrapper-contenu-legal ul {
            margin-bottom: 15px;
            padding-left: 20px;
            font-size: 0.95rem;
        }
        .wrapper-contenu-legal li {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

    <!-- 1. HEADER / NAVIGATION PUBLIQUE -->
    <div style="width: 100%; display: block;">
        <?php 
        if (file_exists('partials/_nav_publique.php')) {
            include 'partials/_nav_publique.php';
        }
        ?>
<!-- BANDEAU LIVE "JE CHERCHE" (Intercalé sous la navigation) -->
<?php include 'partials/_ticker_je_cherche.php'; ?>

    </div>

    <!-- 2. CONTENU DE LA POLITIQUE DE CONFIDENTIALITÉ -->
    <div class="container-global-legal">
        <div class="wrapper-contenu-legal">
            <h1>Politique de confidentialité</h1>
            <p style="color: #64748b; font-size: 0.85rem; margin-top: -5px;">Dernière mise à jour : 20 août 2026</p>

            <p>Bienvenue sur <strong>Jevend.com</strong>. Nous accordons une importance fondamentale à la protection de votre vie privée et de vos renseignements personnels. La présente politique décrit comment nous recueillons, utilisons, conservons et protégeons vos informations dans le cadre de l'utilisation de notre plateforme de petites annonces et de nos services publicitaires.</p>

            <h2>1. Consentement</h2>
            <p>En naviguant sur Jevend.com, en publiant une annonce ou en souscrivant à un espace publicitaire, vous consentez expressément à la collecte, à l'utilisation et à la communication de vos renseignements personnels conformément à la présente politique.</p>

            <h2>2. Renseignements recueillis</h2>
            <p>Pour assurer le bon fonctionnement de notre plateforme et de nos services, nous sommes susceptibles de recueillir les informations suivantes :</p>
            <ul>
                <li><strong>Coordonnées :</strong> Vos nom, prénom, adresse courriel, numéros de téléphone et de cellulaire.</li>
                <li><strong>Informations d'annonces :</strong> Les descriptions, localisations, photos et liens Web (ou pages de réseaux sociaux) associés à vos petites annonces.</li>
                <li><strong>Données de transaction :</strong> Les montants, périodes de diffusion et jetons de facturation de vos bannières publicitaires. (Note : Les paiements en ligne sont traités de manière sécurisée par notre partenaire <strong>Stripe</strong>. Jevend.com ne conserve aucune donnée de carte de crédit sur ses serveurs).</li>
                <li><strong>Données de navigation automatisées :</strong> Votre type d'appareil, votre navigateur et des données statistiques sur votre comportement de navigation à des fins d'amélioration de l'expérience utilisateur.</li>
            </ul>

            <h2>3. Utilisation de vos renseignements</h2>
            <p>Vos renseignements personnels sont utilisés exclusivement pour :</p>
            <ul>
                <li>Publier et gérer vos annonces et bannières publicitaires.</li>
                <li>Administrer notre relation d'affaires, valider vos paiements et assurer le suivi de vos contrats publicitaires.</li>
                <li>Communiquer avec vous par courriel ou par téléphone pour toute question relative à vos services.</li>
                <li>Protéger la plateforme contre la fraude et assurer la sécurité des utilisateurs.</li>
            </ul>

            <h2>4. Témoins (Cookies) et stockage local</h2>
            <p>Jevend.com utilise des témoins de connexion (cookies) essentiels au bon fonctionnement du site (gestion des sessions, authentification et mémorisation des préférences de navigation). La plupart des navigateurs acceptent automatiquement les cookies, mais vous pouvez modifier vos paramètres pour les refuser, bien que cela puisse altérer certaines fonctions de la plateforme.</p>

            <h2>5. Partage et divulgation des données</h2>
            <p>Jevend.com s'engage à ne vendre, louer ou échanger en aucun cas vos renseignements personnels à des tiers. Vos coordonnées affichées sur vos annonces le sont uniquement dans le but de faciliter la mise en relation avec de potentiels acheteurs.</p>

            <h2>6. Vos droits (Accès et suppression)</h2>
            <p>Conformément aux lois applicables en matière de protection des renseignements personnels, vous disposez d'un droit d'accès, de rectification et de suppression de vos données. Vous pouvez en tout temps demander à consulter les informations que nous détenons à votre sujet ou demander leur retrait en communiquant directement avec notre équipe via la plateforme.</p>

            <h2>7. Liens externes</h2>
            <p>Notre site peut contenir des hyperliens vers des sites Web externes (partenaires ou réseaux sociaux). Jevend.com n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leurs propres pratiques de confidentialité.</p>

            <h2>8. Modifications de la politique</h2>
            <p>Nous nous réservons le droit de modifier la présente politique de confidentialité à tout moment. Toute modification sera publiée directement sur cette page avec une date de mise à jour révisée.</p>
        </div>
    </div>

    <!-- 3. FOOTER CLASSIQUE -->
    <div style="width: 100%; display: block; margin-top: 50px;">
        <?php 
if (file_exists('partials/_barre_flottante.php')) {
    include 'partials/_barre_flottante.php';
}
?>
    </div>

</body>
</html>
