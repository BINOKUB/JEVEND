<?php
// NOM DU SCRIPT : partials/_nav_membre.php
// REVISION : 1.4 - Harmonisation esthétique (Thème Bleu Nuit Sombre comme l'Index public)
// MODULE UNIQUE
?>
<style>
    /* BARRE DE NAVIGATION SUPÉRIEURE MEMBRE HARMONISÉE */
    .nav-membre-responsive {
        margin-bottom: 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px 25px; 
        background-color: #0f172a; /* Même bleu nuit que l'index public */
        border-bottom: 1px solid #1e293b; /* Séparation discrète et pro */
    }

    /* Bloc contenant le logo, le label et le bouton de publication */
    .nav-membre-gauche {
        display: flex; 
        align-items: center; 
        gap: 15px;
    }

    /* Conteneur et taille du logo membre */
    .logo-membre-container {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    .logo-membre-img {
        max-height: 45px; /* Même dimension que sur le flux public */
        width: auto;
        display: block;
        border-radius: 4px;
    }

    /* Bloc contenant le message de bienvenue et la déconnexion */
    .nav-membre-droite {
        display: flex; 
        align-items: center; 
        margin: 0;
        color: #94a3b8; /* Texte gris clair pour le contraste */
        font-size: 0.9rem;
        white-space: nowrap;
    }

    /* Bouton d'action Publier adapté pour ressortir sur le fond sombre */
    .btn-nav-publier {
        margin: 0; 
        padding: 6px 14px; 
        font-size: 0.85rem; 
        text-decoration: none; 
        display: inline-block; 
        width: auto; 
        background-color: #2563eb; 
        color: #ffffff; 
        border-radius: 4px; 
        font-weight: bold;
        transition: background 0.15s ease;
    }
    .btn-nav-publier:hover { background-color: #1d4ed8; }

    /* === AJUSTEMENTS CHIRURGICAUX POUR MOBILE (CELLULAIRE) === */
    @media (max-width: 768px) {
        .nav-membre-responsive {
            flex-direction: column;
            gap: 15px;
            text-align: center;
            padding: 15px;
        }
        .nav-membre-gauche {
            flex-direction: column;
            gap: 12px;
            width: 100%;
        }
        .logo-membre-img {
            max-height: 38px;
        }
        .nav-membre-gauche a.btn-nav-publier {
            width: 100% !important;
            box-sizing: border-box;
            text-align: center;
            padding: 8px 14px;
        }
        .nav-membre-droite {
            width: 100%;
            justify-content: center;
            border-top: 1px dashed #1e293b;
            padding-top: 12px;
        }
    }
</style>

<div class="nav-membre-responsive">
    <div class="nav-membre-gauche">
        <a href="index.php" class="logo-membre-container">
            <img src="assets/LOGO_JEVEND-COM.jpeg" alt="jevend.com" class="logo-membre-img">
        </a>
        <span style="font-size: 0.8rem; background-color: #1e293b; color: #3b82f6; padding: 4px 10px; border-radius: 4px; font-weight: bold; border: 1px solid #2563eb; white-space: nowrap;">
            👤 Espace Client
        </span>
        <a href="creer_annonce.php" class="btn-nav-publier">
            + Publier une annonce
        </a>
    </div>
    
    <div class="nav-membre-droite">
        <span style="color: #94a3b8;">Bonjour, <strong style="color: #ffffff;"><?php echo htmlspecialchars($_SESSION['nom']); ?></strong></span>
        <a href="deconnexion.php" style="margin-left: 20px; color: #94a3b8; font-weight: bold; text-decoration: none; font-size: 0.85rem; transition: color 0.15s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">Déconnexion</a>
    </div>
</div>
