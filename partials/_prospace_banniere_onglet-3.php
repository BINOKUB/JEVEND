<?php
// =============================================================================
// NOM DU SCRIPT : partials/_prospace_banniere_onglet-3.php
// DESCRIPTION  : Onglet 3 - Coordonnées du compte, modification des URL et support admin
// =============================================================================
?>
<!-- ======================================================================= -->
<!-- ONGLET 3 : COORDONNÉES DU COMPTE, MODIFICATION DES URL ET SUPPORT ADMIN -->
<!-- ======================================================================= -->
<div id="tab-coordonnees" class="pro-tab-content">
    <div class="pro-box">
        <h3 style="margin-top: 0; color: #0f172a;">📇 Coordonnées du Compte & Configuration</h3>

        <?php if (!empty($msg_succes_url)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                <?= $msg_succes_url ?>
            </div>
        <?php endif; ?>

        <div class="grid-coordonnees">
            
            <!-- 1. INFORMATIONS DU COMPTE MARCHAND -->
            <div class="card-info-box">
                <h4>🏢 Informations du Compte</h4>
                <div style="font-size: 0.9rem; color: #334155; line-height: 1.8;">
                    <strong>Nom d'entreprise / Commerçant :</strong><br>
                    <span style="color: #0f172a; font-weight: bold; font-size: 1rem;"><?= htmlspecialchars($compte['nom_entreprise'] ?? $compte['nom'] ?? 'Non renseigné') ?></span>
                    
                    <div style="margin-top: 10px;">
                        <strong>Courriel associé au compte :</strong><br>
                        <span style="color: #2563eb; font-weight: bold;"><?= htmlspecialchars($compte['courriel'] ?? 'Non renseigné') ?></span>
                    </div>

                    <div style="margin-top: 10px;">
                        <strong>Téléphone / Cellulaire enregistré :</strong><br>
                        <span style="color: #475569; font-weight: bold;"><?= htmlspecialchars($compte['cellulaire'] ?? $compte['telephone_pro'] ?? 'Non renseigné') ?></span>
                    </div>
                </div>
                <div style="margin-top: 15px; font-size: 0.75rem; color: #64748b; background: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                    ℹ️ Ces informations servent uniquement aux fins de facturation et de communication avec l'administration du site. Elles ne sont pas exposées publiquement.
                </div>
            </div>

            <!-- 2. MODIFICATION DE L'URL DE REDIRECTION DES BANNIÈRES -->
            <div class="card-info-box">
                <h4>🔗 Modifier la Redirection de vos Bannières</h4>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: -5px;">
                    Mettez à jour l'adresse web (site internet ou page Facebook) vers laquelle rediriger les clients lors d'un clic sur vos publicités.
                </p>

                <?php if (empty($mes_bannieres_pro)): ?>
                    <p style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">
                        Vous n'aucune bannière active à modifier pour le moment.
                    </p>
                <?php else: ?>
                    <?php foreach ($mes_bannieres_pro as $b_mod): ?>
                        <form action="" method="POST" style="background: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 12px;">
                            <input type="hidden" name="action_update_url_banniere" value="1">
                            <input type="hidden" name="id_banniere_pro" value="<?= (int)$b_mod['id_banniere_pro'] ?>">

                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <span style="font-weight: bold; font-size: 0.8rem; text-transform: uppercase; color: <?= $b_mod['type_banniere'] === 'supreme' ? '#7c3aed' : '#2563eb' ?>;">
                                    Bloc <?= htmlspecialchars($b_mod['type_banniere']) ?> #<?= $b_mod['id_banniere_pro'] ?>
                                </span>
                                <small style="color: #64748b; font-size: 0.75rem;">Échéance : <?= date('d/m/Y', strtotime($b_mod['date_fin'])) ?></small>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <input type="url" name="nouvelle_url_redirection" value="<?= htmlspecialchars($b_mod['url_redirection'] ?? '') ?>" placeholder="https://..." required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; font-weight: bold; color: #1e40af;">
                                <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; cursor: pointer;">
                                    💾 Sauvegarder
                                </button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 3. ASSISTANCE ET CONTACTS ADMINISTRATEUR -->
            <div class="card-info-box" style="background: #0f172a; color: #ffffff;">
                <h4 style="color: #ffffff; border-bottom-color: #334155;">📞 Support & Administration</h4>
                <p style="font-size: 0.85rem; color: #cbd5e1;">
                    Besoin d'aide pour adapter votre visuel publicitaire ou pour toute question concernant votre abonnement marchand ?
                </p>

                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); margin-top: 15px;">
                    <div style="font-weight: bold; color: #38bdf8; font-size: 0.95rem; margin-bottom: 10px;">
                        👨‍💼 Communication
                    </div>
                    
                    <div style="font-size: 0.9rem; margin-bottom: 8px;">
                        <strong></strong><br>
                        <a href="tel:4184299029" style="color: #ffffff; font-weight: bold; font-size: 1.1rem; text-decoration: none;"></a>
                    </div>

                    <div style="font-size: 0.9rem;">
                        ✉️ <strong>Nous Joindre</strong><br>
                        <a href="mailto:douimet61@gmail.com" style="color: #38bdf8; font-weight: bold; text-decoration: none;"><a href="nous_joindre.php" target="_new"><div style="font:arial; color:#38bdf8;"> Assistance Clientèle</div></a></a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
