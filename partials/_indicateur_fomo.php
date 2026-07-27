<?php
// NOM DU SCRIPT : partials/_indicateur_fomo.php
// REVISION : 1.1 - Affichage strict de la vérité historique (0 par défaut)
// MODULE UNIQUE

// Pour l'instant, tant que la table 'jevend_favoris' n'est pas créée, la valeur réelle est 0
$nb_interets = 0; 
?>

<?php if ($nb_interets > 0): ?>
    <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 6px; padding: 10px; margin-bottom: 15px; text-align: center; font-size: 0.85rem; color: #dc2626; font-weight: bold;">
        🔥 <?php echo $nb_interets; ?> <?php echo ($nb_interets === 1) ? 'personne a' : 'personnes ont'; ?> cette vitrine dans leur liste d'envie !
    </div>
<?php endif; ?>
