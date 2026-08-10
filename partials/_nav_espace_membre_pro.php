<?php
// =============================================================================
// NOM DU SCRIPT : partials/_nav_espace_membre_pro.php
// DESCRIPTION  : Barre de navigation spécifique aux comptes PRO (Sans "Je Cherche")
// =============================================================================
$page_active = basename($_SERVER['PHP_SELF']);
$url_courante = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
?>
<style>
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
    @media (max-width: 992px) {
        .slogan-nav-pub { display: none; }
    }
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
        .liens-session-zone {
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        .liens-session-zone a {
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            padding: 8px 14px;
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
        <?php if ($page_active !== 'search.php'): ?>
            <a href="search.php" class="btn-nav-recherche" title="Rechercher une annonce">🔍</a>
        <?php endif; ?>

        <a href="faq.php" class="btn-nav-faq <?= ($page_active === 'faq.php') ? 'active' : '' ?>" title="Foire Aux Questions">F.A.Q.</a>

        <?php if (isset($_SESSION['id_utilisateur'])): ?>
            <span style="color: #94a3b8; font-size: 0.85rem; display: inline-block; white-space: nowrap;">
                👤 <a href="edit_membre_pro.php" style="color: #ffffff; text-decoration: none; border-bottom: 1px dotted #ffffff;" title="Mon Espace Marchand"><strong><?= htmlspecialchars($_SESSION['nom_entreprise'] ?? $_SESSION['nom'] ?? 'Pro') ?></strong></a>
            </span>

            <a href="espace_membre_pro.php" class="btn-nav-membre" style="background-color: #2563eb;">🏢 Espace Marchand</a>

            <a href="deconnexion.php" class="btn-nav-deconnexion">Déconnexion</a>
        <?php endif; ?>
    </div>
</nav>
