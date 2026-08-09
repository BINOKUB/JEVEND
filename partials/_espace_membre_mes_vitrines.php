<?php
// =============================================================================
// NOM DU SCRIPT : partials/_espace_membre_mes_vitrines.php
// DESCRIPTION  : Contenu de l'onglet Mes Vitrines (Annonces et Liste d'envie)
// =============================================================================
?>
<!-- ONGLET 1 : MES VITRINES -->
<div id="onglet-vitrines" class="contenu-onglet actif">
    <h2>Gestion de vos vitrines</h2>

    <!-- VÉRIFICATION DU QUOTA GLOBAL RPM : ALERTE ET BLOCAGE PRÉVENTIF -->
    <?php if (isset($quota_annonces_atteint) && $quota_annonces_atteint): ?>
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 18px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            <div style="font-weight: 900; font-size: 1.05rem; display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span>⚠️</span> Capacité maximale du réseau atteinte (<?= $total_annonces_reseau ?> / <?= $limite_globale_annonces ?> annonces)
            </div>
            <div style="font-size: 0.9rem; line-height: 1.4; color: #7f1d1d;">
                Le quota global des annonces fixé par l'administration est actuellement atteint. La création de nouvelles vitrines est temporairement suspendue. Veuillez s'il vous plaît revenir plus tard si vous souhaitez en ajouter une nouvelle.
            </div>
        </div>

        <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 6px; text-align: center; color: #64748b; margin-bottom: 25px; font-weight: bold; font-size: 0.9rem;">
            🔒 Bouton d'ajout d'annonce temporairement désactivé par mesure de régulation.
        </div>
    <?php else: ?>
        <!-- BOUTON D'AJOUT CLASSIQUE (Affiché uniquement si le quota n'est pas atteint) -->
        <div style="margin-bottom: 25px;">
            <a href="creer_annonce.php" class="btn-action" style="display: inline-block; background-color: #2563eb; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 0.9rem;">
                ✨ + Créer une nouvelle annonce
            </a>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php if (count($liste_annonces) > 0): ?>
            <?php foreach ($liste_annonces as $annonce): ?>
                <?php include '_card.php'; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="admin-bloc-vide">Vous n'avez aucune annonce en ligne actuellement.</div>
        <?php endif; ?>
    </div>
    
    <?php include '_liste_envie.php'; ?>
</div>
