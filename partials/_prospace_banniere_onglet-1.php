<?php
// =============================================================================
// NOM DU SCRIPT : partials/_prospace_banniere_onglet-1.php
// DESCRIPTION  : Onglet 1 - Formulaire d'achat et téléversement des bannières Pro
// =============================================================================
?>
<!-- ======================================================================= -->
<!-- ONGLET 1 : FORMULAIRE D'ACHAT ET TÉLÉVERSEMENT -->
<!-- ======================================================================= -->
<div id="tab-achat" class="pro-tab-content actif">
    <div class="pro-box">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 1.3rem;">
            🚀 Réserver un Emplacement Publicitaire Exclusif
        </h2>
        <p style="color: #64748b; font-size: 0.9rem; margin-top: -5px;">
            Téléversez votre visuel et renseignez le lien web vers lequel diriger les acheteurs lors d'un clic.
        </p>

        <?php if (!empty($msg_succes_url)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                <?= $msg_succes_url ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg_alerte_pro)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #fecaca;">
                <?= $msg_alerte_pro ?>
            </div>
        <?php endif; ?>

        <form action="traitement_paiement_pro.php" method="POST" enctype="multipart/form-data" id="form-pro-banniere">
            
            <div style="background: #f1f5f9; padding: 20px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 25px;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1rem;">1. Visuel Publicitaire & Lien Cible</h3>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #0f172a;">
                        🔗 URL de destination lors du clic (Site Web ou Page Facebook) * :
                    </label>
                    <input type="url" name="url_redirection" placeholder="Ex: https://www.mon-commerce.ca ou https://facebook.com/ma-page" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-weight: bold; color: #1e40af;">
                    <small style="color: #64748b; font-size: 0.75rem;">Les visiteurs qui cliquent sur votre bannière seront redirigés directement sur cette adresse.</small>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #0f172a;">
                        📸 Image de votre bannière (JPG, PNG, WEBP) * :
                    </label>
                    <input type="file" name="image_banniere" id="input-image-banniere" accept="image/jpeg,image/png,image/webp" required style="width: 100%; padding: 8px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    
                    <div style="margin-top: 8px; padding: 10px; background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 4px; font-size: 0.8rem; color: #1e3a8a;">
                        💡 <strong>Optimisation automatique :</strong> Téléversez simplement votre visuel. Le serveur effectue le recadrage centré et l'optimisation HD automatiquement.<br />
                          💡  <strong>Horizontal</strong> (Ratio 4:1 ou 6:1), 1200 x 300 pixels.<br />
                         💡   <strong>Carré</strong> Ratio 1:1 ou 4:3), 600 x 600 pixels
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #0f172a;">
                        📝 Slogan ou court texte d'accroche (Facultatif) :
                    </label>
                    <input type="text" name="texte_banniere" placeholder="Ex: Rabais de 15% sur présentation de cette pub !" maxlength="120" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
            </div>

            <h3 style="margin-top: 0; color: #1e293b; font-size: 1rem;">2. Choisir l'Emplacement et le Calculateur de Durée</h3>

            <div class="pro-grid-forfaits">
                
                <!-- FORFAIT SUPRÊME -->
                <div class="card-forfait-pro" id="card-supreme" style="border-color: #7c3aed; transition: all 0.3s ease;">
                    <div>
                        <!-- CASE RADIO D'ACTIVATION DÉDIÉE -->
                        <div style="background: #f3e8ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; border: 1px solid #d8b4fe; display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="document.getElementById('radio-supreme').click()">
                            <input type="radio" name="choix_forfait" value="supreme" id="radio-supreme" onchange="gererSelectionForfait()" style="transform: scale(1.3); cursor: pointer;" required>
                            <label for="radio-supreme" style="font-weight: bold; color: #581c87; cursor: pointer; font-size: 0.95rem;">
                                👑 Activer le Forfait Suprême
                            </label>
                        </div>

                        <p style="font-size: 0.85rem; color: #64748b;">   Carrousel haut de page principal (3 slots max en circulation).  </p>

                <p style="font-size: 0.85rem; color: #64748b;"> 

                            🪧 Pour le Grand Bandeau d'en-tête (Format Panoramique)<BR />
💡 Guide de l'image (Bandeau d'en-tête) :<BR /><BR />

Format idéal : Horizontal large (Ratio d'environ 4:1 ou 6:1).<BR />

Dimension conseillée : 1200 x 300 pixels (minimum 800 x 200 px).<BR />

Formats acceptés : JPG, PNG ou WEBP (Max 5 Mo).<BR /><BR />

Astuce : Privilégiez un visuel où l'information importante ou votre logo est bien centré. Notre système ajuste et optimise automatiquement le cadrage pour s'adapter à tous les écrans.

                            </p>
                        
                        <?php if ($nb_supreme_global >= 3): ?>
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 0.85rem; margin: 20px 0; border: 1px solid #fecaca;">
                                ❌ ESPACE PUBLICITAIRE COMPLET<br>
                                <span style="font-size: 0.75rem; font-weight: normal;">Tous les emplacements Suprêmes (3/3) sont actuellement occupés. Premier arrivé, premier vendu !</span>
                            </div>
                        <?php else: ?>
                            <div style="background: #f3e8ff; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center;">
                                <div style="font-size: 0.8rem; color: #6b21a8; font-weight: bold; text-transform: uppercase;">Tarif Total du Bloc</div>
                                <div id="affichage-prix-supreme" style="font-size: 1.6rem; font-weight: 800; color: #581c87;">
                                    <?= number_format($prix_mensuel_supreme, 2, ',', ' ') ?> $
                                </div>
                                <small style="color: #7e22ce; font-size: 0.75rem;">(soit <?= number_format($prix_mensuel_supreme, 2, ',', ' ') ?> $ / mois)</small>
                            </div>
                            
                            <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px;">Durée du contrat :</label>
                            <select name="duree_bloc_supreme" id="select-duree-supreme" onchange="calculerPrixPro()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 15px; font-weight: bold;">
                                <option value="1">1 Mois</option>
                                <option value="2">2 Mois</option>
                                <option value="3">3 Mois (Maximum)</option>
                            </select>

                            <button type="submit" id="btn-submit-supreme" disabled style="width: 100%; padding: 12px; background: #7c3aed; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.95rem; opacity: 0.5;">
                                📤 Téléverser & Payer Suprême
                            </button>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; text-align: right; margin-top: 10px;">
                        Actives globales : <strong><?= $nb_supreme_global ?> / 3</strong>
                    </div>
                </div>

                <!-- FORFAIT PREMIUM -->
                <div class="card-forfait-pro" id="card-premium" style="border-color: #2563eb; transition: all 0.3s ease;">
                    <div>
                        <!-- CASE RADIO D'ACTIVATION DÉDIÉE -->
                        <div style="background: #eff6ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; border: 1px solid #bfdbfe; display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="document.getElementById('radio-premium').click()">
                            <input type="radio" name="choix_forfait" value="premium" id="radio-premium" onchange="gererSelectionForfait()" style="transform: scale(1.3); cursor: pointer;" required>
                            <label for="radio-premium" style="font-weight: bold; color: #1e3a8a; cursor: pointer; font-size: 0.95rem;">
                                ⚡ Activer le Forfait Premium
                            </label>
                        </div>

                        <p style="font-size: 0.85rem; color: #64748b;">
                           Pavés rotatifs haute visibilité sous l'en-tête (20 slots max au total).          
                        </p>

                        <p style="font-size: 0.85rem; color: #64748b;">
                            🔲 Pour les Pavés Publicitaires (Format Carré / Carte)<BR />
                            💡 Guide de l'image (Pavé publicitaire) :<BR /><BR />

                            Format idéal : Carré ou légèrement rectangulaire (Ratio 1:1 ou 4:3).<BR />

                            Dimension conseillée : 600 x 600 pixels (minimum 400 x 400 px).<BR />

                            Formats acceptés : JPG, PNG ou WEBP (Max 5 Mo).<BR />

                            Astuce : Idéal pour mettre en avant un produit phare, une photo de votre devanture ou un logo épuré.
    
                        </p>
                        
                        <?php if ($nb_premium_global >= 20): ?>
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 0.85rem; margin: 20px 0; border: 1px solid #fecaca;">
                                ❌ ESPACE PUBLICITAIRE COMPLET<br>
                                <span style="font-size: 0.75rem; font-weight: normal;">Tous les emplacements Premium du site (20/20) sont actuellement occupés.</span>
                            </div>
                        <?php elseif ($nb_premium_actifs >= 5): ?>
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 0.85rem; margin: 20px 0; border: 1px solid #fecaca;">
                                ❌ Limite individuelle atteinte<br>
                                <span style="font-size: 0.75rem; font-weight: normal;">Vous possédez déjà 5/5 bannières Premium actives.</span>
                            </div>
                        <?php else: ?>
                            <div style="background: #eff6ff; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center;">
                                <div style="font-size: 0.8rem; color: #1e40af; font-weight: bold; text-transform: uppercase;">Tarif Total du Bloc</div>
                                <div id="affichage-prix-premium" style="font-size: 1.6rem; font-weight: 800; color: #1e3a8a;">
                                    <?= number_format($prix_mensuel_premium, 2, ',', ' ') ?> $
                                </div>
                                <small style="color: #1d4ed8; font-size: 0.75rem;">(soit <?= number_format($prix_mensuel_premium, 2, ',', ' ') ?> $ / mois)</small>
                            </div>

                            <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px;">Durée du contrat :</label>
                            <select name="duree_bloc_premium" id="select-duree-premium" onchange="calculerPrixPro()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 15px; font-weight: bold;">
                                <option value="1">1 Mois</option>
                                <option value="2">2 Mois</option>
                                <option value="3">3 Mois</option>
                                <option value="4">4 Mois</option>
                                <option value="5">5 Mois</option>
                                <option value="6">6 Mois (Maximum)</option>
                            </select>

                            <button type="submit" id="btn-submit-premium" disabled style="width: 100%; padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.95rem; opacity: 0.5;">
                                📤 Téléverser & Payer Premium
                            </button>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; text-align: right; margin-top: 10px;">
                        Globales : <strong><?= $nb_premium_global ?> / 20</strong> | Vos actives : <strong><?= $nb_premium_actifs ?> / 5</strong>
                    </div>
                </div>

            </div>

        </form>
    </div>
</div>
