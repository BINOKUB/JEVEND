<?php
// =============================================================================
// NOM DU SCRIPT : partials/_affiche_sponsorise.php
// REVISION : 1.4 - Correction de la couleur des liens cliquables dans le ticker
// =============================================================================
try {
    $stmt_pubs = $bdd->query("
        SELECT * FROM jevend_bandeau_sponsorise 
        WHERE statut = 'actif' 
          AND NOW() BETWEEN date_debut AND date_fin 
        ORDER BY id_bandeau DESC
    ");
    $pubs_actives = $stmt_pubs->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pubs_actives = [];
}

if (!empty($pubs_actives)):
    $bg_colors = [
        'rouge' => 'linear-gradient(135deg, #dc2626, #b91c1c)',
        'bleu nuit' => 'linear-gradient(135deg, #1e3a8a, #172554)',
        'noir' => '#0f172a',
        'blanc' => '#ffffff'
    ];
    $text_colors = [
        'blanc' => '#ffffff',
        'noir' => '#0f172a',
        'vert fluo' => '#4ade80'
    ];
?>
    <style>
        .bandeau-defilant-container {
            background: #0f172a;
            padding: 8px 0;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 100%;
            box-sizing: border-box;
            z-index: 9999;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        .bandeau-defilant-track {
            display: inline-block;
            white-space: nowrap;
            animation: defilementBandeau 30s linear infinite;
        }
        .bandeau-defilant-track:hover {
            animation-play-state: paused;
        }
        .bandeau-item {
            display: inline-block;
            padding: 4px 15px;
            margin-right: 50px;
            border-radius: 4px;
        }
        /* Style universel pour forcer le lien à prendre la couleur du texte et enlever le souligné par défaut */
        .bandeau-item a {
            color: inherit !important;
            text-decoration: none;
        }
        .bandeau-item a:hover {
            text-decoration: underline;
        }
        @keyframes defilementBandeau {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-50%, 0, 0); }
        }
        @media (max-width: 768px) {
            .bandeau-defilant-container {
                font-size: 0.8rem;
                padding: 6px 0;
            }
        }
    </style>

    <!-- BANDEAU PUBLICITAIRE DÉFILANT MULTI-CLIENTS -->
    <div class="bandeau-defilant-container">
        <div class="bandeau-defilant-track">
            <!-- Première boucle -->
            <?php foreach ($pubs_actives as $pub): 
                $item_bg = $bg_colors[$pub['fond_couleur'] ?? 'rouge'] ?? '#dc2626';
                $item_color = $text_colors[$pub['couleur_police'] ?? 'blanc'] ?? '#ffffff';
                $item_border = (($pub['fond_couleur'] ?? '') === 'blanc') ? 'border: 1px solid #cbd5e1;' : '';
            ?>
                <span class="bandeau-item" style="background: <?= $item_bg ?>; color: <?= $item_color ?>; <?= $item_border ?>">
                    📢 
                    <?php if (!empty($pub['url_redirection'])): ?>
                        <a href="<?= htmlspecialchars($pub['url_redirection']) ?>" target="_blank">
                            <?= htmlspecialchars($pub['message']) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($pub['message']) ?>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>

            <!-- Deuxième boucle identique pour la fluidité du défilement infini -->
            <?php foreach ($pubs_actives as $pub): 
                $item_bg = $bg_colors[$pub['fond_couleur'] ?? 'rouge'] ?? '#dc2626';
                $item_color = $text_colors[$pub['couleur_police'] ?? 'blanc'] ?? '#ffffff';
                $item_border = (($pub['fond_couleur'] ?? '') === 'blanc') ? 'border: 1px solid #cbd5e1;' : '';
            ?>
                <span class="bandeau-item" style="background: <?= $item_bg ?>; color: <?= $item_color ?>; <?= $item_border ?>">
                    📢 
                    <?php if (!empty($pub['url_redirection'])): ?>
                        <a href="<?= htmlspecialchars($pub['url_redirection']) ?>" target="_blank">
                            <?= htmlspecialchars($pub['message']) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($pub['message']) ?>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
