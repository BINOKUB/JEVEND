<?php
// =============================================================================
// NOM DU SCRIPT : partials/_nav_publique.php
// REVISION : 1.10 - Ajout du slogan "« Premier arrivé, premier vendu »" à gauche
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
    .nav-gauche-publique {
        display: flex;
        align-items: center;
        gap: 15px;
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
    .slogan-nav-pub {
        color: #94a3b8;
        font-size: 0.85rem;
        font-style: italic;
        font-weight: 500;
        white-space: nowrap;
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
        color: #00f3ff;
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

    /* STYLE DU LIEN LA ZONE JE CHERCHE */
    .btn-nav-zone-cherche {
        color: #f59e0b;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 4px;
        border: 1px solid rgba(245, 158, 11, 0.4);
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-zone-cherche:hover {
        color: #ffffff;
        background-color: #f59e0b;
    }
    .btn-nav-zone-cherche.active {
        color: #ffffff;
        background-color: #d97706;
        border-color: #d97706;
    }

    /* STYLE DU LIEN MES RECHERCHES */
    .btn-nav-mes-recherches {
        color: #38bdf8;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 4px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-mes-recherches:hover {
        color: #ffffff;
        background-color: #1e293b;
    }
    .btn-nav-mes-recherches.active {
        color: #ffffff;
        background-color: #1e293b;
        border: 1px solid #38bdf8;
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

    @media (max-width: 992px) {
        .slogan-nav-pub {
            display: none;
        }
    }

    /* === CORRECTIF CHIRURGICAL POUR SERRAGE MOBILE === */
    @media (max-width: 768px) {
        .barre-navigation-globale {
            flex-direction: column;
            gap: 12px;
            padding: 15px;
            text-align: center;
        }
        .nav-gauche-publique {
            flex-direction: column;
            gap: 8px;
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
    <div class="nav-gauche-publique">
        <a href="index.php" class="logo-nav-container">
            <img src="assets/LOGO_JEVEND-COM.jpeg" alt="jevend.com" class="logo-nav-img">
        </a>
        <span class="slogan-nav-pub">« Premier arrivé, premier vendu »</span>
    </div>

    <div class="liens-session-zone">
        
        <!-- Injecté dynamiquement : affiché partout SAUF sur la page search.php elle-même -->
        <?php if ($page_active !== 'search.php'): ?>
            <a href="search.php" class="btn-nav-recherche" title="Rechercher une annonce">🔍</a>
        <?php endif; ?>

        <!-- LIEN F.A.Q. -->
        <a href="faq.php" class="btn-nav-faq <?= ($page_active === 'faq.php') ? 'active' : '' ?>" title="Foire Aux Questions">F.A.Q.</a>

        <!-- LIEN PERMANENT : LA ZONE JE CHERCHE -->
        <a href="zone_cherche.php" class="btn-nav-zone-cherche <?= ($page_active === 'zone_cherche.php') ? 'active' : '' ?>" title="La Zone Je Cherche">🎯 Je Cherche</a>

        <?php if (isset($_SESSION['id_utilisateur'])): ?>
            <span style="color: #94a3b8; font-size: 0.85rem; display: inline-block; white-space: nowrap;">
    👤 <a href="edit_membre_pro.php" style="color: #ffffff; text-decoration: none; border-bottom: 1px dotted #ffffff;" title="Modifier mes informations marchandes"><strong><?= htmlspecialchars($_SESSION['nom_entreprise'] ?? $_SESSION['nom'] ?? 'Membre') ?></strong></a>
</span>

            <!-- LIEN DIRECT : MES RECHERCHES (HISTORIQUE PERSONNEL) -->
            <a href="mes_recherches.php" class="btn-nav-mes-recherches <?= ($page_active === 'mes_recherches.php') ? 'active' : '' ?>" title="Mes recherches Je Cherche">📋 Mes recherches</a>

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
