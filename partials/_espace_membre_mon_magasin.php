<?php
// =============================================================================
// NOM DU SCRIPT : partials/_espace_membre_mon_magasin.php
// DESCRIPTION  : Contenu de l'onglet Mon Magasin (Description, Pubs et Reçus)
// =============================================================================
?>
<!-- ONGLET 3 : MON MAGASIN -->
<div id="onglet-magasin" class="contenu-onglet">
    
    <div class="zone-description-magasin">
        <label class="case-bascule-container">
            <input type="checkbox" id="chk-activer-description" onchange="basculerFormulaireDescription()" <?= !empty($description_magasin) ? 'checked' : '' ?>>
            ✍️ Personnaliser l'accueil de votre boutique (Mot de bienvenue / Promotions)
        </label>
        
        <div class="bloc-form-description" id="bloc-form-description" style="<?= !empty($description_magasin) ? 'display: block;' : '' ?>">
            <p style="color: #64748b; font-size: 0.85rem; margin-top: 0; margin-bottom: 12px;">
                Ce texte sera affiché tout en haut de votre boutique publique pour accueillir vos clients et mettre en valeur vos services.
            </p>
            <textarea id="txt-description-magasin" style="width: 100%; height: 100px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 0.9rem; box-sizing: border-box; resize: vertical;" placeholder="Ex: Bienvenue dans mon magasin d'instruments de musique d'occasion ! Retrouvez nos nouveautés chaque semaine..."><?= htmlspecialchars($description_magasin) ?></textarea>
            
            <div style="display: flex; justify-content: flex-end; margin-top: 10px; gap: 10px; align-items: center;">
                <span id="statut-sauvegarde-desc" style="font-size: 0.85rem; font-weight: bold; transition: color 0.2s;"></span>
                <button onclick="sauvegarderDescriptionBoutique()" class="btn-action" style="margin: 0; padding: 8px 16px; width: auto; font-size: 0.85rem;">💾 Enregistrer la présentation</button>
            </div>
        </div>

    </div>
<div class="zone-campagnes-pub">ICI LE MESSAGE SI LES BANN SONT >15% ... </div>


    <div class="zone-campagnes-pub">
        <h3 style="margin-top:0; display:flex; align-items:center; gap:8px; color:#1e3a8a;"><span style="color:#10b981;">🟢</span> Vos campagnes publicitaires en direct</h3>
        
        <?php if (count($bannieres_du_membre) > 0): ?>
            <div style="overflow-x: auto; width: 100%;">
                <table class="table-pub">
                    <thead>
                        <tr>
                            <th>Annonce liée</th>
                            <th>Slogan de la campagne</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Échéance & Restant</th>
                            <th style="text-align: center; width: 130px;">Performances</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bannieres_du_membre as $campagne): ?>
                            <?php
                                $duree_jours = (int)($campagne['duree_jours'] ?? 0);
                                $date_ref = !empty($campagne['date_debut_activation']) ? $campagne['date_debut_activation'] : ($campagne['date_enregistrement'] ?? null);
                                $affichage_temps = "<span style='color:#94a3b8;'>Non spécifié</span>";
                                
                                if ($date_ref && $duree_jours > 0) {
                                    $dt_debut = new DateTime($date_ref);
                                    $dt_fin = (clone $dt_debut)->modify("+" . $duree_jours . " days");
                                    $dt_actuel = new DateTime();
                                    
                                    if ($dt_actuel > $dt_fin) {
                                        $affichage_temps = "<span style='color:#dc2626; font-weight:bold;'>Expiré</span>";
                                    } else {
                                        $diff_jours = $dt_actuel->diff($dt_fin)->days;
                                        
                                        if ($campagne['statut_affichage'] === 'en_attente' && empty($campagne['date_debut_activation'])) {
                                            $affichage_temps = "<strong>" . $duree_jours . " jours</strong><br><small style='color:#b45309; font-weight:bold;'>⏳ En attente d'activation</small>";
                                        } else {
                                            $affichage_temps = "<strong>" . $dt_fin->format('Y-m-d') . "</strong><br><small style='color:#2563eb; font-weight:bold;'>⏳ " . $diff_jours . " jour(s) restant(s)</small>";
                                        }
                                    }
                                }
                            ?>
                            <tr>
                                <td style="font-weight: 500;">
                                    <?= !empty($campagne['titre_objet_nettoye']) ? htmlspecialchars($campagne['titre_objet_nettoye']) : '<span style="color:#94a3b8; font-style:italic;">Aucune (Lien Boutique)</span>' ?>
                                </td>
                                <td style="font-style: italic; color:#475569;">"<?= htmlspecialchars($campagne['texte_banniere']) ?>"</td>
                                <td style="font-weight: bold; color:#1e3a8a; text-transform:uppercase; font-size:0.8rem;"><?= htmlspecialchars($campagne['type_banniere'] ?? 'Régulière') ?></td>
                                <td>
                                    <?php if ($campagne['statut_affichage'] === 'active'): ?>
                                        <span style="background-color: #e6f4ea; color: #15803d; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.8rem; border: 1px solid #bbf7d0; white-space: nowrap;">🟢 En circulation</span>
                                    <?php else: ?>
                                        <span style="background-color: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.8rem; border: 1px solid #fde68a; white-space: nowrap;">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $affichage_temps ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <span class="badge-perf badge-vue" title="Nombre d'affichages sur l'index">
                                        👁️ <?= (int)($campagne['nb_vues'] ?? 0) ?>
                                    </span>
                                    <span class="badge-perf badge-clic" title="Nombre de clics enregistrés">
                                        🎯 <?= (int)($campagne['nb_clics'] ?? 0) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="background-color:#f8fafc; border:1px dashed #cbd5e1; padding:20px; border-radius:6px; text-align:center; color:#64748b; font-style:italic;">
                Vous n'avez aucune campagne publicitaire active ou en attente pour le moment.
            </div>
        <?php endif; ?>
    </div>

    <!-- INCLUSION DU MODULE D'ACHAT DE BANNIÈRE RÉGULIÈRE -->
    <div style="margin-top: 30px; width: 100%;">
        <?php include '_membre-banniere.php'; ?>
    </div>

    <!-- ACCORDÉON COMPTABILITÉ UNIQUE -->
    <details class="accordeon-recus">
        <summary class="accordeon-header">
            <span>🧾 Historique des reçus & factures (<?= count($historique_achats) ?>)</span>
            <span class="accordeon-icon">+</span>
        </summary>
        <div class="accordeon-contenu">
            <?php if (count($historique_achats) > 0): ?>
                <div style="overflow-x: auto; width: 100%;">
                    <table class="table-pub">
                        <thead>
                            <tr>
                                <th>N° Transaction</th>
                                <th>Date d'achat</th>
                                <th>Description du service</th>
                                <th>Montant payé</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique_achats as $achat): ?>
                                <?php 
                                    $date_recu = !empty($achat['date_achat']) ? date('Y-m-d H:i', strtotime($achat['date_achat'])) : date('Y-m-d H:i');
                                    $ref_recu = !empty($achat['no_transaction']) ? htmlspecialchars($achat['no_transaction']) : ("#REG-" . str_pad((string)$achat['id_preuve'], 5, '0', STR_PAD_LEFT));
                                    $desc_txt = !empty($achat['description_achat']) ? htmlspecialchars($achat['description_achat']) : 'Campagne publicitaire';
                                    $prix_txt = number_format((float)($achat['prix_paye'] ?? 0), 2, ',', ' ') . " $ CAD";
                                    $statut_txt = htmlspecialchars($achat['statut_paiement'] ?? 'Payé');
                                ?>
                                <tr>
                                    <td style="font-weight: bold; color: #1e3a8a; font-family: monospace;">
                                        <?= $ref_recu ?>
                                    </td>
                                    <td><?= $date_recu ?></td>
                                    <td><?= $desc_txt ?></td>
                                    <td style="font-weight: bold; color: #16a34a;">
                                        <?= $prix_txt ?>
                                    </td>
                                    <td>
                                        <span style="background-color: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; border: 1px solid #bbf7d0;">
                                            <?= $statut_txt ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 10px; color: #64748b; font-style: italic; text-align: center;">
                    Aucun reçu ou facture disponible dans votre historique pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </details>

</div>
