<?php
// =============================================================================
// NOM DU SCRIPT : partials/_ticker_je_cherche.php
// REVISION : 1.0 - Ruban d'actualité "Je Cherche" sous la navigation principale
// DESCRIPTION : Bandeau défilant horizontal d'opportunités d'achats locaux.
// =============================================================================

$recherches_ticker = [];
if (isset($bdd)) {
    try {
        $stmt_t = $bdd->query("
            SELECT r.*, v.nom_ville 
            FROM jevend_recherches r
            JOIN jevend_villes v ON r.id_ville = v.id_ville
            WHERE r.statut = 'actif'
            ORDER BY r.date_creation DESC 
            LIMIT 10
        ");
        $recherches_ticker = $stmt_t->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // En cas d'absence de la table ou erreur, le ruban bascule en mode découverte
        $recherches_ticker = [];
    }
}
?>

<style>
    .ticker-zone-wrapper {
        background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
        border-bottom: 2px solid #f59e0b;
        color: #ffffff;
        height: 44px;
        display: flex;
        align-items: center;
        padding: 0 15px;
        overflow: hidden;
        font-family: Arial, sans-serif;
        font-size: 0.85rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .ticker-badge-titre {
        background-color: #d97706;
        color: #ffffff;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .ticker-conteneur-defilant {
        flex-grow: 1;
        overflow: hidden;
        position: relative;
        white-space: nowrap;
        margin: 0 15px;
        mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
    }

    .ticker-piste-texte {
        display: inline-block;
        white-space: nowrap;
        animation: defilementTicker 30s linear infinite;
    }

    .ticker-conteneur-defilant:hover .ticker-piste-texte {
        animation-play-state: paused;
    }

    @keyframes defilementTicker {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    .ticker-item-demande {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 35px;
        color: #e2e8f0;
        text-decoration: none;
        transition: color 0.15s ease;
    }

    .ticker-item-demande:hover {
        color: #fbbf24;
        text-decoration: underline;
    }

    .ticker-item-ville {
        color: #38bdf8;
        font-weight: bold;
    }

    .ticker-item-budget {
        color: #4ade80;
        font-weight: bold;
        background: rgba(74, 222, 128, 0.1);
        padding: 1px 6px;
        border-radius: 3px;
    }

    .ticker-actions-droite {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        z-index: 2;
    }

    .btn-ticker-voir {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        text-decoration: none;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.15s ease;
        white-space: nowrap;
    }

    .btn-ticker-voir:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }

    .btn-ticker-poster {
        background: #f59e0b;
        color: #0f172a;
        text-decoration: none;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 800;
        transition: background 0.15s ease;
        white-space: nowrap;
    }

    .btn-ticker-poster:hover {
        background: #d97706;
        color: #ffffff;
    }

    @media (max-width: 768px) {
        .ticker-zone-wrapper {
            height: auto;
            padding: 8px 10px;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: space-between;
        }
        .ticker-conteneur-defilant {
            width: 100%;
            order: 3;
            margin: 4px 0 0 0;
        }
    }
</style>

<div class="ticker-zone-wrapper">
    
    <div class="ticker-badge-titre">
        🎯 JE CHERCHE
    </div>

    <div class="ticker-conteneur-defilant">
        <div class="ticker-piste-texte">
            <?php if (!empty($recherches_ticker)): ?>
                <?php 
                // Doublon de la boucle pour assurer un défilement infini sans saut
                $items_a_afficher = array_merge($recherches_ticker, $recherches_ticker);
                foreach ($items_a_afficher as $r): 
                ?>
                    <a href="details_recherche.php?id=<?= $r['id_recherche'] ?>" class="ticker-item-demande">
                        <span class="ticker-item-ville">📍 <?= htmlspecialchars($r['nom_ville']) ?></span> : 
                        <strong><?= htmlspecialchars($r['titre_recherche']) ?></strong>
                        <?php if (!empty($r['budget_max']) && $r['budget_max'] > 0): ?>
                            <span class="ticker-item-budget">(Budget : <?= number_format((float)$r['budget_max'], 2, ',', ' ') ?> $)</span>
                        <?php endif; ?>
                        <span style="color: #64748b; margin-left: 10px;">•</span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Message de démarrage par défaut quand aucune recherche n'est en BDD -->
                <span class="ticker-item-demande" style="cursor: default;">
                    📍 <strong style="color: #38bdf8;">Matane & Régions</strong> : Vous cherchez un objet spécifique ? Publiez votre demande gratuitement en 1 clic !
                </span>
                <span class="ticker-item-demande" style="cursor: default;">
                    🎯 Les vendeurs de votre municipalité vous contacteront directement.
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="ticker-actions-droite">
        <a href="zone_cherche.php" class="btn-ticker-voir">👁️ Tout voir</a>
        <a href="poster_recherche.php" class="btn-ticker-poster">🎯 + Poster</a>
    </div>

</div>
