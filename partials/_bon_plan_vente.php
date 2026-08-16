<?php
/*
====================================================
Fichier       : partials/_bon_plan_vente.php
Révision      : v1.2 - Correction du débordement visuel et de la grille
Description   : Sous-module Espace Membre - Stratégie Plan de Vente & Prix Spécial Flash
====================================================
*/
?>
<div style="background: #ffffff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-sizing: border-box;">
    <h2 style="margin-top: 0; color: #1e3a8a; font-size: 1.4rem; display: flex; align-items: center; gap: 8px;">
        🚀 Stratégie "Bon Plan de Vente" (Vente Flash & Relance Prospects)
    </h2>
    <p style="color: #475569; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
        Forcez la vente de vos objets en proposant un <strong>Prix Spécial temporaire</strong> ! 
        Toutes les personnes ayant ajouté votre bien à leur <em>Liste d'Envie</em> verront votre offre spéciale mise en évidence avec un compte à rebours d'urgence. 
        Même si aucun prospect n'est encore enregistré, lancer un Plan de Vente rendra votre annonce ultra-attractive dès le premier coup d'œil !
    </p>

    <?php if (!empty($liste_annonces)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; box-sizing: border-box;">
            <?php foreach ($liste_annonces as $item): ?>
                <?php
                    $id_item       = (int)$item['id_annonces'];
                    $prix_reg      = (float)($item['prix'] ?? 0);
                    $prix_promo    = !empty($item['prix_promo']) ? (float)$item['prix_promo'] : null;
                    $date_fin      = !empty($item['date_fin_promo']) ? $item['date_fin_promo'] : null;
                    $nb_prospects  = (int)($item['nb_prospects'] ?? 0);
                    $date_exp      = !empty($item['date_expiration']) ? date('Y-m-d à H:i', strtotime($item['date_expiration'])) : 'Non définie';

                    // Vérifier si une promo est actuellement active et non expirée
                    $promo_active = false;
                    $heures_restantes = 0;
                    if ($prix_promo !== null && $date_fin !== null) {
                        $dt_fin = new DateTime($date_fin);
                        $dt_now = new DateTime();
                        if ($dt_now < $dt_fin) {
                            $promo_active = true;
                            $diff = $dt_now->diff($dt_fin);
                            $heures_restantes = ($diff->days * 24) + $diff->h;
                        }
                    }
                ?>
                <!-- Retrait de la classe 'form-bloc' et ajout de box-sizing pour éviter le débordement -->
                <div style="background: #f8fafc; border: 1px solid <?= $promo_active ? '#f97316' : '#cbd5e1' ?>; border-radius: 8px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box; width: 100%;">
                    
                    <div>
                        <!-- En-tête : Catégorie & Badge ID -->
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #64748b; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                            <span>📦 <?= htmlspecialchars($item['categorie_nom'] ?? 'Général') ?></span>
                            <span style="font-family: monospace; font-weight: bold; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">#<?= $id_item ?></span>
                        </div>

                        <!-- Titre -->
                        <h3 style="margin: 0 0 4px 0; color: #0f172a; font-size: 1.1rem; font-weight: bold; line-height: 1.3;">
                            <?= htmlspecialchars(stripslashes(html_entity_decode($item['titre_objet_nettoye'] ?? '', ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <!-- DATE D'ÉCHÉANCE SOUS LE TITRE -->
                        <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 4px;">
                            ⏳ Échéance : <strong style="color: #334155;"><?= $date_exp ?></strong>
                        </div>

                        <!-- INDICATEUR DE PROSPECTS (ACHETEURS EN LISTE D'ENVIE) -->
                        <div style="margin-bottom: 15px; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; display: flex; align-items: center; gap: 8px; box-sizing: border-box; <?= $nb_prospects > 0 ? 'background-color: #fff7ed; color: #c2410c; border: 1px dashed #ea580c;' : 'background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;' ?>">
                            <?php if ($nb_prospects > 0): ?>
                                🔥 <span><strong><?= $nb_prospects ?> prospect(s)</strong> a/ont cet article dans leur Liste d'Envie !</span>
                            <?php else: ?>
                                👤 <span>0 prospect actuellement (Prêt pour votre offre !)</span>
                            <?php endif; ?>
                        </div>

                        <!-- PRIX RÉGULIER -->
                        <div style="font-size: 0.9rem; color: #475569; margin-bottom: 12px;">
                            Prix régulier en ligne : <strong style="color: #1e293b; font-size: 1.1rem;"><?= number_format($prix_reg, 2, ',', ' ') ?> $</strong>
                        </div>

                        <!-- ÉTAT ACTIF DU PLAN DE VENTE (SI EN COURS) -->
                        <?php if ($promo_active): ?>
                            <div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 12px; margin-bottom: 15px; box-sizing: border-box;">
                                <div style="color: #991b1b; font-weight: bold; font-size: 0.85rem; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    🔥 PRIX SPÉCIAL EN COURS
                                </div>
                                <div style="font-size: 1.3rem; font-weight: bold; color: #dc2626; margin-bottom: 4px;">
                                    <?= number_format($prix_promo, 2, ',', ' ') ?> $
                                </div>
                                <div style="font-size: 0.8rem; color: #b91c1c; font-weight: bold;">
                                    ⏳ Se termine le <?= date('d/m/Y à H:i', strtotime($date_fin)) ?> (Reste env. <?= $heures_restantes ?>h)
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- BLOC FORMULAIRE D'ACTIVATION / ANNULATION -->
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 10px;">
                        <?php if ($promo_active): ?>
                            <button onclick="executerPlanDeVente(<?= $id_item ?>, 'annuler')" style="width: 100%; background-color: #64748b; color: #ffffff; border: none; padding: 9px 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; box-sizing: border-box;">
                                🛑 Annuler la promotion & remettre au prix normal
                            </button>
                        <?php else: ?>
                            <div style="font-size: 0.82rem; font-weight: bold; color: #334155; margin-bottom: 8px;">
                                Configurer un Prix Spécial Flash :
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <input type="number" step="0.01" id="prix-promo-<?= $id_item ?>" placeholder="Prix promo ex: <?= number_format($prix_reg * 0.85, 2, '.', '') ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                                </div>
                                <div style="width: 110px;">
                                    <select id="duree-promo-<?= $id_item ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                                        <option value="24">24 heures</option>
                                        <option value="48" selected>48 heures</option>
                                        <option value="72">72 heures</option>
                                    </select>
                                </div>
                            </div>

                            <button onclick="executerPlanDeVente(<?= $id_item ?>, 'activer')" style="width: 100%; background-color: #2563eb; color: #ffffff; border: none; padding: 9px 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; transition: background 0.2s; box-sizing: border-box;">
                                🚀 Activer le Prix Spécial
                            </button>
                        <?php endif; ?>

                        <div id="msg-plan-<?= $id_item ?>" style="font-size: 0.8rem; font-weight: bold; margin-top: 6px; text-align: center;"></div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="admin-bloc-vide">
            Vous n'avez aucune annonce active pour le moment. Déposez votre première vitrine pour utiliser le Plan de Vente !
        </div>
    <?php endif; ?>
</div>
