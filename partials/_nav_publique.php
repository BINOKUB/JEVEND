<?php
// =============================================================================
// NOM DU SCRIPT : partials/_nav_publique.php
// REVISION : 1.7 - Capturation de l'URL courante pour retour après connexion
// MODULE UNIQUE
// =============================================================================

// Détection de la page active et de l'URL complète
$page_active = basename($_SERVER['PHP_SELF']);
$url_courante = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
?>
<style>
    /* BARRE DE NAVIGATION SUPÉRIEURE SYSTÈME */
    .barre-navigation-globale {
        background-color: #0f172a;
        border-bottom: 1px solid #1e293b;
        padding: 10px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    /* Conteneur du logo pour un alignement vertical parfait */
    .logo-nav-container {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    .logo-nav-img {
        max-height: 45px;
        width: auto;
        display: block;
        border-radius: 4px;
    }
    .liens-session-zone {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    /* STYLE BOUTON LOUPE ORIGINAL JEVEND */
    .btn-nav-recherche {
        color: #94a3b8;
        text-decoration: none;
        font-size: 1.15rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px;
        border-radius: 4px;
        transition: all 0.15s ease;
    }
    .btn-nav-recherche:hover {
        color: #00f3ff; /* Ton cyan signature */
        background-color: #1e293b;
        transform: scale(1.05);
    }

    /* STYLE DU LIEN F.A.Q. */
    .btn-nav-faq {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 4px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-faq:hover {
        color: #ffffff;
        background-color: #1e293b;
    }
    .btn-nav-faq.active {
        color: #38bdf8;
        background-color: #1e293b;
    }

    .btn-nav-membre {
        background-color: #2563eb;
        color: #ffffff;
        text-decoration: none;
        padding: 6px 14px;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: bold;
        transition: background 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-membre:hover { background-color: #1d4ed8; }
    
    .btn-nav-deconnexion {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .btn-nav-deconnexion:hover { color: #f43f5e; }

    /* === CORRECTIF CHIRURGICAL POUR SERRAGE MOBILE === */
    @media (max-width: 768px) {
        .barre-navigation-globale {
            flex-direction: column;
            gap: 12px;
            padding: 15px;
            text-align: center;
        }
        .logo-nav-img {
            max-height: 38px;
        }
        .liens-session-zone {
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        .liens-session-zone span {
            margin-right: 0 !important;
        }
        .liens-session-zone a {
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            padding: 8px 14px;
        }
        .btn-nav-recherche {
            width: 100%;
            border: 1px solid #1e293b;
            padding: 8px;
        }
    }
</style>

<nav class="barre-navigation-globale">
    <a href="index.php" class="logo-nav-container">
        <img src="assets/LOGO_JEVEND-COM.jpeg" alt="jevend.com" class="logo-nav-img">
    </a>
    <div class="liens-session-zone">
        
        <!-- Injecté dynamiquement : affiché partout SAUF sur la page search.php elle-même -->
        <?php if ($page_active !== 'search.php'): ?>
            <a href="search.php" class="btn-nav-recherche" title="Rechercher une annonce">🔍</a>
        <?php endif; ?>

        <!-- LIEN F.A.Q. (Positionné entre la loupe et la zone de connexion/session) -->
        <a href="faq.php" class="btn-nav-faq <?= ($page_active === 'faq.php') ? 'active' : '' ?>" title="Foire Aux Questions">F.A.Q.</a>

        <?php if (isset($_SESSION['id_utilisateur'])): ?>
            <span style="color: #94a3b8; font-size: 0.85rem; display: inline-block; white-space: nowrap;">
                👤 <?= htmlspecialchars($_SESSION['nom_entreprise'] ?? $_SESSION['nom'] ?? 'Membre') ?>
            </span>

            <!-- DIRECTION TRIVALENTE SEGMENTÉE (ADMIN VS PRO VS PARTICULIER) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="panneau.php" class="btn-nav-membre" style="background-color: #0f172a; border: 1px solid #38bdf8; color: #38bdf8;">⚙️ Panneau Admin</a>
            <?php elseif (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'pro'): ?>
                <a href="espace_membre_pro.php" class="btn-nav-membre" style="background-color: #2563eb;">🏢 Espace Marchand</a>
            <?php else: ?>
                <a href="espace_membre.php" class="btn-nav-membre">📋 Mon Espace</a>
            <?php endif; ?>

            <a href="deconnexion.php" class="btn-nav-deconnexion">Déconnexion</a>
        <?php else: ?>
            <!-- Affiché uniquement si on n'est PAS sur la page de connexion -->
            <?php if ($page_active !== 'connexion.php'): ?>
                <a href="connexion.php?redirect=<?= $url_courante ?>" class="btn-nav-membre" style="background-color: #1e293b; border: 1px solid #475569;">Se connecter</a>
            <?php endif; ?>

            <!-- Affiché uniquement si on n'est PAS sur la page d'inscription NI de connexion -->
            <?php if ($page_active !== 'inscription.php' && $page_active !== 'connexion.php'): ?>
                <a href="inscription.php" class="btn-nav-membre">S'inscrire</a>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</nav>
