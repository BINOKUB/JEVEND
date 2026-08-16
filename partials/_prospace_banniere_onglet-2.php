<?php
// =============================================================================
// NOM DU SCRIPT : partials/_prospace_banniere_onglet-2.php
// DESCRIPTION  : Onglet 2 - Gestion des bannières Pro en circulation et reçus d'achat
// =============================================================================
?>
<!-- ======================================================================= -->
<!-- ONGLET 2 : GESTION DES BANNIÈRES PRO EN CIRCULATION -->
<!-- ======================================================================= -->
<div id="tab-mes-bannieres" class="pro-tab-content">
    <div class="pro-box">
        <h3 style="margin-top: 0; color: #0f172a;">🖼️ Vos Emplacements Publicitaires</h3>
        
        <?php if (empty($bannieres_par_periode)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">📢</div>
                <p style="font-size: 1rem; margin: 0;">Vous n'avez aucun bloc publicitaire pour le moment.</p>
                <small>Rendez-vous dans l'onglet "Réserver une bannière" pour lancer votre première campagne.</small>
            </div>
        <?php else: ?>
            <?php foreach ($bannieres_par_periode as $periode => $liste_bannieres): ?>
                <details class="accordeon-periode" open>
                    <summary class="accordeon-header">
                        <span>📅 Période : <strong><?= htmlspecialchars($periode) ?></strong></span>
                        <span class="badge-count"><?= count($liste_bannieres) ?> bannière(s)</span>
                    </summary>
                    <div class="accordeon-content">
                        <?php foreach ($liste_bannieres as $bann): ?>
                            <?php 
                                $date_debut  = isset($bann['date_debut']) ? new DateTime($bann['date_debut']) : new DateTime();
                                $date_fin    = isset($bann['date_fin']) ? new DateTime($bann['date_fin']) : new DateTime('+30 days');
                                
                                $date_butoir = (clone $date_fin)->modify('-10 days');
                                
                                $est_expire      = ($maintenant > $date_fin);
                                $peut_renouveler = ($maintenant >= $date_butoir && !$est_expire);
                                
                                $diff_interval  = $maintenant->diff($date_fin);
                                $jours_restants = $diff_interval->invert ? 0 : $diff_interval->days;

                                $fichier_image_existe = !empty($bann['image_url']) && file_exists($bann['image_url']);
                                $nb_clics = (int)($bann['nb_clics'] ?? 0);
                                $url_destination = $bann['url_redirection'] ?? '#';
                            ?>
                            <div class="card-banniere-active">
                                <div>
                                    <?php if ($fichier_image_existe): ?>
                                        <img src="<?= htmlspecialchars($bann['image_url']) ?>" alt="Bannière" class="preview-banniere-img">
                                    <?php else: ?>
                                        <div class="preview-banniere-img" style="display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.75rem; text-align: center; padding: 5px;">
                                            📦 Visuel Archivé<br><small>(Fichier purgé)</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div style="flex: 1; min-width: 240px;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <span style="background: <?= $bann['type_banniere'] === 'supreme' ? '#7c3aed' : '#2563eb' ?>; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                            BLOC <?= htmlspecialchars($bann['type_banniere'] ?? 'PRO') ?>
                                        </span>
                                        <strong style="color: #0f172a; font-size: 1rem;">
                                            Tarif : <?= number_format((float)($bann['prix_paye'] ?? 0), 2, ',', ' ') ?> $ CAD
                                        </strong>
                                    </div>

                                    <div style="font-size: 0.85rem; color: #475569; line-height: 1.6;">
                                        🔗 <strong>Destination du clic :</strong> <a href="<?= htmlspecialchars($url_destination) ?>" target="_blank" style="color: #2563eb; font-weight: bold; word-break: break-all;"><?= htmlspecialchars($url_destination) ?></a><br>
                                        📅 <strong>Date de début :</strong> <?= $date_debut->format('d/m/Y') ?><br>
                                        ⌛ <strong>Date d'échéance :</strong> <strong style="color: <?= $est_expire ? '#dc2626' : '#16a34a' ?>;"><?= $date_fin->format('d/m/Y') ?></strong> (<?= $est_expire ? 'Expiré' : $jours_restants . ' jours restants' ?>)<br>
                                        🖱️ <strong>Performance :</strong> <span style="color: #2563eb; font-weight: bold; background: #eff6ff; padding: 2px 8px; border-radius: 4px; border: 1px solid #bfdbfe; font-size: 0.8rem;"><?= $nb_clics ?> clic(s) enregistré(s)</span>
                                    </div>
                                </div>

                                <div>
                                    <?php if ($est_expire): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; display: inline-block;">
                                            🔴 Expiré (Slot libéré)
                                        </span>
                                    <?php elseif ($peut_renouveler): ?>
                                        <a href="renouveler_pro.php?id=<?= $bann['id_banniere_pro'] ?>" style="background: #16a34a; color: #ffffff; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.85rem; display: inline-block;">
                                            🔄 Renouveler en Priorité
                                        </a>
                                    <?php else: ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; display: inline-block;">
                                            🟢 Actif (Renouvellement dès le <?= $date_butoir->format('d/m/Y') ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ACCORDÉON SEPARÉ : HISTORIQUE DES REÇUS ET PREUVES D'ACHAT (À VIE) -->
        <details class="accordeon-recus-pro" open>
            <summary>
                <span>🧾 Historique des reçus & preuves d'achat (<?= count($mes_recus_pro) ?>)</span>
                <span class="accordeon-icon">−</span>
            </summary>
            <div class="accordeon-contenu">
                <?php if (empty($mes_recus_pro)): ?>
                    <div style="padding: 15px; color: #64748b; font-style: italic; text-align: center;">
                        Aucun reçu ou preuve d'achat disponible dans votre historique pour le moment.
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto; width: 100%;">
                        <table class="table-recus-pro">
                            <thead>
                                <tr>
                                    <th>N° Transaction</th>
                                    <th>Date d'achat</th>
                                    <th>Emplacement / Description</th>
                                    <th>Durée</th>
                                    <th>Montant payé</th>
                                    <th>Statut de la transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mes_recus_pro as $recu): ?>
                                    <?php 
                                        $date_recu = !empty($recu['date_achat']) ? date('Y-m-d H:i', strtotime($recu['date_achat'])) : date('Y-m-d H:i');
                                        $ref_recu = !empty($recu['no_transaction']) ? htmlspecialchars($recu['no_transaction']) : ("#PRO-" . str_pad((string)$recu['id_preuve'], 5, '0', STR_PAD_LEFT));
                                        $type_txt = strtoupper($recu['type_banniere'] ?? 'PRO');
                                        $duree_txt = ((int)($recu['duree_mois'] ?? 1)) . " mois";
                                        $prix_txt = number_format((float)($recu['prix_paye'] ?? 0), 2, ',', ' ') . " $ CAD";
                                        $desc_txt = !empty($recu['description_achat']) ? htmlspecialchars($recu['description_achat']) : '';
                                        $statut_txt = htmlspecialchars($recu['statut_paiement'] ?? 'Payé');
                                    ?>
                                    <tr>
                                        <td style="font-weight: bold; color: #0f172a; font-family: monospace;">
                                            <?= $ref_recu ?>
                                        </td>
                                        <td><?= $date_recu ?></td>
                                        <td>
                                            <strong>Forfait <?= $type_txt ?></strong>
                                            <?php if (!empty($desc_txt)): ?>
                                                <br><small style="color: #64748b; font-style: italic;">"<?= $desc_txt ?>"</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $duree_txt ?></td>
                                        <td style="font-weight: bold; color: #16a34a; font-size: 0.95rem;">
                                            <?= $prix_txt ?>
                                        </td>
                                        <td>
                                            <span style="background-color: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 0.78rem; border: 1px solid #bbf7d0; display: inline-block;">
                                                <?= $statut_txt ?> & Archivé (À vie)
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </details>

    </div>
</div>
