 <!-- 3. FLUX D'ANNONCES -->
    <div class="admin-conteneur">
        <div class="flux-grille" id="fil-actualite">
            <?php 
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
                if ($compteur_position % 4 === 0 && $index_banniere < $total_bannieres_flux): 
                    $banniere = $bannieres_flux[$index_banniere]; 
                    $index_banniere++;
                    
                    $_SESSION['bannieres_affichees_session'][] = (int)$banniere['id_banniere'];
                    incrementerVueBanniere($bdd, $banniere['id_banniere']);
                    ?>
                    <div class="bloc-banniere-pub">
                        <span class="banniere-badge">📣 VITRINE SPONSORISÉE</span>
                        <div class="banniere-slogan">"<?= htmlspecialchars($banniere['texte_banniere'] ?? '') ?>"</div>
                        <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 12px;">Artisan local : <strong><?= htmlspecialchars($banniere['vendeur_nom'] ?? '') ?></strong></div>
                        
                        <?php if (!empty($banniere['id_annonce'])): ?>
                            <a href="details.php?id=<?= $banniere['id_annonce'] ?>" class="btn-action lien-banniere-pub" data-id="<?= $banniere['id_banniere'] ?>" style="max-width: 250px; margin: 0 auto; text-decoration: none;">👁️ Découvrir la vitrine</a>
                        <?php endif; ?>

                        <?php if (!empty($banniere['image_courante']) && file_exists("uploads/" . $banniere['image_courante'])): ?>
                            <div class="flux-pub-image-zone">
                                <img src="uploads/<?= htmlspecialchars($banniere['image_courante']) ?>" alt="Vitrine promotionnelle">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php 
                endif; 
            endforeach; 
            ?>
        </div>
        <div id="declencheur-scroll" style="height: 50px; margin-top: 20px; text-align: center; color: #64748b; font-style: italic; font-size: 0.9rem;">🦊 Défiler pour en voir plus...</div>
    </div>

