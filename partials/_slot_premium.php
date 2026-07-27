<!-- partials/_slot_premium.php -->
<?php
// =============================================================================
// NOM DU SCRIPT : partials/_slot_premium.php
// REVISION     : 1.3 - Restriction d'affichage du bouton Réserver aux membres PRO uniquement
// DESCRIPTION  : Détection automatique du statut du membre connecté. 
//                Le bouton "🚀 Réserver" sur les emplacements libres n'apparaît 
//                désormais QUE si l'utilisateur est un membre PRO authentifié.
// =============================================================================

if (!isset($bdd)) {
    exit("Accès direct non autorisé.");
}

// Démarrage de session de sécurité si non déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détection infaillible du statut PRO du membre connecté
$est_membre_pro = false;

if (isset($_SESSION['id_utilisateur'])) {
    if (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'pro') {
        $est_membre_pro = true;
    } else {
        // Double vérification directe en BDD pour garantir l'étanchéité
        try {
            $stmt_pro_check = $bdd->prepare("SELECT type_compte FROM jevend_utilisateurs WHERE id_utilisateur = ?");
            $stmt_pro_check->execute([$_SESSION['id_utilisateur']]);
            $user_info = $stmt_pro_check->fetch(PDO::FETCH_ASSOC);
            
            if ($user_info && ($user_info['type_compte'] ?? '') === 'pro') {
                $_SESSION['type_compte'] = 'pro'; // Synchronisation session
                $est_membre_pro = true;
            }
        } catch (PDOException $e_pro) {
            $est_membre_pro = false;
        }
    }
}

try {
    // 1. EXTRACTION DES BANNIÈRES PREMIUM PRO ACTIVES (MAX 20)
    $sql_premium_pro = "
        SELECT b.*, u.nom_entreprise, u.nom AS vendeur_nom
        FROM jevend_bannieres_actives_pro b
        JOIN jevend_utilisateurs u ON b.id_utilisateur = u.id_utilisateur
        WHERE b.type_banniere = 'premium' 
          AND b.statut_affichage = 'active'
          AND b.date_fin >= NOW()
        ORDER BY b.id_banniere_pro DESC
        LIMIT 20
    ";
    $toutes_bannieres_premium = $bdd->query($sql_premium_pro)->fetchAll(PDO::FETCH_ASSOC);

    // 2. DISTRIBUTION SYSTÉMATIQUE DE GAUCHE À DROITE (ROUND-ROBIN SUR LES 4 SLOTS)
    $paves_premium_final = [[], [], [], []];
    foreach ($toutes_bannieres_premium as $index => $bann) {
        $slot_index = $index % 4; // Répartit de 0 à 3 en boucle
        $paves_premium_final[$slot_index][] = $bann;
    }

} catch (PDOException $e) {
    $paves_premium_final = [[], [], [], []];
}
?>

<!-- GRILLE DES 4 PAVÉS PREMIUM -->
<div class="container-premium-fige">
    <div class="grille-premium">
        <?php for ($i = 0; $i < 4; $i++): 
            $bannieres_du_pave = $paves_premium_final[$i] ?? [];
        ?>
            <div class="carte-premium-figee">
                <?php if (!empty($bannieres_du_pave)): ?>
                    <?php 
                        $id_carrousel_mini = "mini-carrousel-" . $i;
                        $total_mini_slides = count($bannieres_du_pave);
                    ?>
                    <div class="mini-carrousel-wrapper" id="<?= $id_carrousel_mini ?>">
                        <?php if ($total_mini_slides > 1): ?>
                            <button class="mini-nav-btn prev" onclick="deplacerMiniCarrousel('<?= $id_carrousel_mini ?>', -1)">❮</button>
                            <button class="mini-nav-btn next" onclick="deplacerMiniCarrousel('<?= $id_carrousel_mini ?>', 1)">❯</button>
                        <?php endif; ?>

                        <div class="mini-carrousel-container">
                            <?php foreach ($bannieres_du_pave as $bann_p): ?>
                                <?php 
                                    if (function_exists('incrementerVueBanniere')) {
                                        incrementerVueBanniere($bdd, $bann_p['id_banniere_pro']);
                                    }
                                    
                                    // Correction HTTPS automatique si l'URL est saisie sans protocole
                                    $url_brute_p = trim($bann_p['url_redirection'] ?? '');
                                    if (!empty($url_brute_p) && strpos($url_brute_p, 'http') !== 0) {
                                        $url_brute_p = 'https://' . $url_brute_p;
                                    }
                                    $lien_cible_p = htmlspecialchars($url_brute_p);
                                    $id_pro_p     = (int)$bann_p['id_banniere_pro'];
                                ?>
                                <div class="mini-slide" 
                                     style="background-image: url('<?= htmlspecialchars($bann_p['image_url']) ?>');" 
                                     onclick="enregistrerClicEtOuvrirPro(<?= $id_pro_p ?>, '<?= $lien_cible_p ?>')">
                                    <div class="mini-slide-overlay">
                                        <span style="font-size: 0.6rem; color: #ffffff; background: #2563eb; padding: 2px 6px; border-radius: 4px; font-weight: bold; width: fit-content; text-transform: uppercase;">⚡ PREMIUM</span>
                                        <?php if (!empty($bann_p['texte_banniere'])): ?>
                                            <div style="font-size: 0.75rem; font-weight: bold; font-style: italic; background: rgba(15,23,42,0.75); padding: 4px 6px; border-radius: 4px; text-align: center;">
                                                "<?= htmlspecialchars($bann_p['texte_banniere']) ?>"
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="padding: 8px; text-align: center; background: #ffffff; margin-top: auto;">
                        <?php 
                            $first_url = trim($bannieres_du_pave[0]['url_redirection'] ?? '');
                            if (!empty($first_url) && strpos($first_url, 'http') !== 0) {
                                $first_url = 'https://' . $first_url;
                            }
                        ?>
                        <a href="javascript:void(0)" 
                           onclick="enregistrerClicEtOuvrirPro(<?= (int)$bannieres_du_pave[0]['id_banniere_pro'] ?>, '<?= htmlspecialchars($first_url) ?>')" 
                           style="font-size: 0.75rem; color: #2563eb; font-weight: bold; text-decoration: none;">
                            🏢 <?= htmlspecialchars($bannieres_du_pave[0]['nom_entreprise'] ?: $bannieres_du_pave[0]['vendeur_nom'] ?: 'Visiter l\'annonceur') ?> 🌐 →
                        </a>
                    </div>
                <?php else: ?>
                    <!-- EMPLACEMENT LIBRE -->
                    <div style="padding: 25px 15px; text-align: center; background: #f8fafc; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 8px; aspect-ratio: 3 / 2;">
                        <span style="font-size: 0.65rem; color: #64748b; font-weight: bold; text-transform: uppercase;">⚡ Emplacement Libre</span>
                        <div style="font-size: 0.8rem; color: #475569; font-weight: bold;">Pavé Premium Disponible</div>
                        
                        <?php if ($est_membre_pro): ?>
                            <a href="espace_membre_pro.php" style="font-size: 0.75rem; color: #2563eb; font-weight: bold; text-decoration: none; background: #eff6ff; padding: 4px 10px; border-radius: 4px; border: 1px solid #bfdbfe;">🚀 Réserver</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<script>
// ENREGISTREMENT DU CLIC ET REDIRECTION DIRECTE DANS UN NOUVEL ONGLET
if (typeof enregistrerClicEtOuvrirPro === 'undefined') {
    function enregistrerClicEtOuvrirPro(idBannierePro, urlCible) {
        if (!urlCible || urlCible === '#' || urlCible === '') return;
        
        fetch(`clic_stat.php?id_banniere_pro=${idBannierePro}`)
            .finally(() => {
                window.open(urlCible, '_blank');
            });
    }
}

// GESTION DES MINI-CARROUSELS
if (typeof etatsMiniCarrousels === 'undefined') {
    var etatsMiniCarrousels = {};
}

function deplacerMiniCarrousel(idWrapper, direction) {
    const wrapper = document.getElementById(idWrapper);
    if (!wrapper) return;
    const conteneurMini = wrapper.querySelector('.mini-carrousel-container');
    const slides = wrapper.querySelectorAll('.mini-slide');
    const total = slides.length;

    if (!etatsMiniCarrousels[idWrapper]) {
        etatsMiniCarrousels[idWrapper] = 0;
    }

    etatsMiniCarrousels[idWrapper] += direction;
    if (etatsMiniCarrousels[idWrapper] >= total) {
        etatsMiniCarrousels[idWrapper] = 0;
    } else if (etatsMiniCarrousels[idWrapper] < 0) {
        etatsMiniCarrousels[idWrapper] = total - 1;
    }

    conteneurMini.style.transform = `translateX(-${etatsMiniCarrousels[idWrapper] * 100}%)`;
}

// Auto-rotation des pavés multi-bannières
if (typeof intervalleMiniCarrousel === 'undefined') {
    var intervalleMiniCarrousel = setInterval(() => {
        for (let i = 0; i < 4; i++) {
            const idW = "mini-carrousel-" + i;
            const wrapper = document.getElementById(idW);
            if (wrapper) {
                const slides = wrapper.querySelectorAll('.mini-slide');
                if (slides.length > 1) {
                    deplacerMiniCarrousel(idW, 1);
                }
            }
        }
    }, 4000);
}
</script>
