<?php
// =============================================================================
// NOM DU SCRIPT : charger_flux_infini.php
// REVISION : 1.6 - Prise en charge des Prix Spéciaux (Vente Flash) au défilement
// =============================================================================
session_start();
require_once 'config.php';
require_once 'fonctions_geoloc.php';
require_once 'partials/_jevend_stat.php';

$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;
$id_ville_acheteur = null;

if ($id_utilisateur_connecte) {
    try {
        $stmt_acheteur = $bdd->prepare("SELECT id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_acheteur->execute([$id_utilisateur_connecte]);
        $id_ville_acheteur = $stmt_acheteur->fetchColumn();
    } catch (PDOException $e) { }
}

$limite = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = $page * $limite;

try {
    $sql_annonces = "
        SELECT a.*, u.nom AS vendeur_nom, u.id_ville AS vendeur_ville_id, v.nom_ville AS vendeur_ville_nom,
               IF(le.id_envie IS NOT NULL, 1, 0) AS est_favoris,
               (SELECT COUNT(*) FROM jevend_listes_envie WHERE id_annonce = a.id_annonces) AS nb_envies
        FROM jevend_annonces a
        JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        JOIN jevend_villes v ON u.id_ville = v.id_ville
        LEFT JOIN jevend_listes_envie le ON a.id_annonces = le.id_annonce AND le.id_utilisateur = :id_user
        WHERE a.statut = 'actif'
        ORDER BY a.date_creation DESC
        LIMIT :offset, :limite
    ";
    
    $stmt_annonces = $bdd->prepare($sql_annonces);
    $stmt_annonces->bindValue(':id_user', $id_utilisateur_connecte, PDO::PARAM_INT);
    $stmt_annonces->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_annonces->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt_annonces->execute();
    $flux_annonces = $stmt_annonces->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit();
}

if (empty($flux_annonces)) {
    exit();
}

$compteur_position = 0;

foreach ($flux_annonces as $annonce): 
    $compteur_position++;
    $fichier_image = !empty($annonce['image_courante']) ? $annonce['image_courante'] : '';
    $chemin_complet_image = "uploads/" . $fichier_image; 

    $date_cree = new DateTime($annonce['date_creation']);
    $date_expire = new DateTime($annonce['date_expiration']);
    $maintenant = new DateTime();
    $intervalle_total = $date_cree->diff($date_expire)->days;
    $jours_restants = $maintenant->diff($date_expire)->days;
    $badge_temps = "";
    
    if ($maintenant <= $date_expire && $intervalle_total > 0) {
        $pourcentage = ($jours_restants / $intervalle_total) * 100;
        if ($pourcentage <= 25) { $badge_temps = "<div class='badge-urgence-carte'>⏳ Plus que quelques jours !</div>"; } 
        elseif ($pourcentage <= 50) { $badge_temps = "<div class='badge-urgence-carte'>⏳ Le temps s'écoule !</div>"; }
    }

    // DÉTECTION EN DIRECT DU PRIX SPÉCIAL (VENTE FLASH)
    $a_promo = false;
    $prix_promo_affiche = 0;
    $temps_promo_texte = "";

    if (!empty($annonce['prix_promo']) && !empty($annonce['date_fin_promo'])) {
        try {
            $dt_fin_p = new DateTime($annonce['date_fin_promo']);
            if ($maintenant < $dt_fin_p) {
                $a_promo = true;
                $prix_promo_affiche = (float)$annonce['prix_promo'];
                $diff_p = $maintenant->diff($dt_fin_p);
                $h_rest = ($diff_p->days * 24) + $diff_p->h;
                $temps_promo_texte = "Reste " . $h_rest . "h " . $diff_p->i . "m !";
            }
        } catch (Exception $e) { $a_promo = false; }
    }

    $date_exp_formatee = date('Y-m-d', strtotime($annonce['date_expiration']));
    ?>
    
    <!-- CARTE ANNONCE RÉGULIÈRE OU VENTE FLASH -->
    <div class="carte-annonce" style="<?= $a_promo ? 'border: 2px solid #dc2626;' : '' ?>">
        <div class="carte-image-zone">
            <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
                <div class="mini-badge-vendu">🔴 VENDU</div>
            <?php endif; ?>

            <?php if(!empty($annonce['image_courante']) && file_exists($chemin_complet_image)): ?>
                <img src="<?= htmlspecialchars($chemin_complet_image) ?>" alt="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>">
            <?php else: ?>
                <div style="color: #94a3b8; font-size: 0.8rem; text-align: center; padding: 10px;">📸 Pas de photo</div>
            <?php endif; ?>
        </div>
        
        <div class="carte-corps">
            <div class="carte-vendeur-ligne">
                <a href="store.php?id=<?= $annonce['id_utilisateur'] ?>" class="vendeur-nom" title="Visiter la boutique de <?= htmlspecialchars($annonce['vendeur_nom']) ?>" style="text-decoration: none;">
                    👤 <?= htmlspecialchars($annonce['vendeur_nom']) ?>
                </a>
                <span style="font-size: 0.75rem; color: #475569; font-weight: bold; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1;">
                    ⌛ Exp : <?= $date_exp_formatee ?>
                </span>
            </div>
            
            <div class="carte-meta-ligne">
                <?= obtenirTexteDistance($bdd, $id_ville_acheteur, $annonce['vendeur_ville_id'], $annonce['vendeur_ville_nom'], $annonce['id_utilisateur'], $id_utilisateur_connecte) ?> • 🕒 <?= date('d M', strtotime($annonce['date_creation'])) ?>
            </div>

            <?php if ($a_promo): ?>
                <div style="font-size: 0.72rem; font-weight: bold; color: #ffffff; background-color: #dc2626; padding: 3px 6px; border-radius: 4px; margin-bottom: 6px; display: inline-block;">
                    🔥 VENTE FLASH (<?= $temps_promo_texte ?>)
                </div>
            <?php else: ?>
                <?= $badge_temps ?>
            <?php endif; ?>

            <?php if ($annonce['nb_envies'] > 0): ?>
                <div style="font-size:0.65rem; color:#b45309; margin-bottom:6px;">🔥 Envie : <strong><?= $annonce['nb_envies'] ?> acheteur(s)</strong></div>
            <?php endif; ?>
            
            <h3 class="carte-titre" title="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>"><?= htmlspecialchars($annonce['titre_objet_nettoye']) ?></h3>
            
            <!-- PRIX VENTE FLASH OU PRIX RÉGULIER -->
            <div class="carte-prix">
                <?php if ($a_promo): ?>
                    <del style="color: #94a3b8; font-size: 0.85rem; margin-right: 6px; font-weight: normal;">
                        <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                    </del>
                    <span style="color: #dc2626; font-weight: bold;"><?= number_format($prix_promo_affiche, 2, ',', ' ') ?> $</span>
                <?php else: ?>
                    <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                <?php endif; ?>
            </div>
        </div>
        <div class="carte-actions">
            <button class="btn-favoris" data-id="<?= $annonce['id_annonces'] ?>" title="Favoris">
                <?= ($annonce['est_favoris'] == 1) ? '❤️' : '🤍' ?>
            </button>
            
            <button style="background:none; border:none; cursor:pointer; font-size:0.9rem; color:#64748b; padding:0;" onclick="partagerAnnonce(<?= $annonce['id_annonces'] ?>, '<?= htmlspecialchars(addslashes($annonce['titre_objet_nettoye']), ENT_QUOTES) ?>')">
                🔗 Partager
            </button>
            
            <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
                <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:4px 8px; font-size:0.75rem; text-decoration:none; width:auto; background-color:#64748b;">📂 Archives</a>
            <?php else: ?>
                <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:4px 8px; font-size:0.75rem; text-decoration:none; width:auto; <?= $a_promo ? 'background-color:#dc2626;' : '' ?>">👁️ Vitrine</a>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    if ($compteur_position % 8 === 0): 
        $bannieres_deja_vues = $_SESSION['bannieres_affichees_session'] ?? [];

        $sql_pub_scroll = "
            SELECT b.id_banniere, b.id_annonce, b.id_utilisateur, b.texte_banniere, b.type_banniere, u.nom AS vendeur_nom, a.image_courante 
            FROM jevend_bannieres_actives b
            JOIN jevend_utilisateurs u ON b.id_utilisateur = u.id_utilisateur
            LEFT JOIN jevend_annonces a ON b.id_annonce = a.id_annonces
            WHERE b.statut_affichage = 'active' AND b.type_banniere = 'reguliere'
        ";

        if (!empty($bannieres_deja_vues)) {
            $ids_exclus = implode(',', array_map('intval', $bannieres_deja_vues));
            $sql_pub_scroll .= " AND b.id_banniere NOT IN ($ids_exclus)";
        }

        $sql_pub_scroll .= " ORDER BY RAND() LIMIT 1";

        try {
            $banniere_scroll = $bdd->query($sql_pub_scroll)->fetch(PDO::FETCH_ASSOC);

            if ($banniere_scroll) {
                $_SESSION['bannieres_affichees_session'][] = (int)$banniere_scroll['id_banniere'];
                incrementerVueBanniere($bdd, $banniere_scroll['id_banniere']);
                ?>
                <div class="bloc-banniere-pub">
                    <span class="banniere-badge">📣 VITRINE SPONSORISÉE</span>
                    <div class="banniere-slogan">"<?= htmlspecialchars($banniere_scroll['texte_banniere'] ?? '') ?>"</div>
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 12px;">Artisan local : <strong><?= htmlspecialchars($banniere_scroll['vendeur_nom'] ?? '') ?></strong></div>
                    
                    <?php if (!empty($banniere_scroll['id_annonce'])): ?>
                        <a href="details.php?id=<?= $banniere_scroll['id_annonce'] ?>" class="btn-action lien-banniere-pub" data-id="<?= $banniere_scroll['id_banniere'] ?>" style="max-width: 250px; margin: 0 auto; text-decoration: none;">👁️ Découvrir la vitrine</a>
                    <?php endif; ?>

                    <?php if (!empty($banniere_scroll['image_courante']) && file_exists("uploads/" . $banniere_scroll['image_courante'])): ?>
                        <div class="flux-pub-image-zone">
                            <img src="uploads/<?= htmlspecialchars($banniere_scroll['image_courante']) ?>" alt="Vitrine promotionnelle">
                        </div>
                    <?php endif; ?>
                </div>
                <?php 
            }
        } catch (PDOException $e) { }
    endif; 
endforeach; 
?>
