<!-- partials/_admin_ban.php -->
<?php
// =============================================================================
// NOM DU SCRIPT : partials/_admin_ban.php
// REVISION     : 1.0 - Affichage de la bannière d'information officielle de la direction
// DESCRIPTION  : Pavé 468x60 textuel sur l'index, positionné entre le carrousel
//                Suprême et les Pavés Premium lorsque le statut BDD est 'actif'.
// =============================================================================

if (!isset($bdd)) { 
    return; 
}

try {
    $stmt_admin_ban = $bdd->query("SELECT etat, texte FROM jevend_admin_ban WHERE id = 1");
    $data_admin_ban = $stmt_admin_ban->fetch(PDO::FETCH_ASSOC);

    if ($data_admin_ban && $data_admin_ban['etat'] === 'actif' && !empty(trim($data_admin_ban['texte']))) :
?>

<!-- BANNIÈRE ADMIN OFFICIELLE (468x60 RESPONSIVE) -->
<div style="display: flex; justify-content: center; width: 100%; margin: 20px 0 10px 0; padding: 0 10px; box-sizing: border-box;">
    <div style="
        width: 468px; 
        max-width: 100%; 
        min-height: 60px; 
        background: linear-gradient(135deg, #fffbe3 0%, #fef3c7 100%); 
        border: 2px solid #f59e0b; 
        border-radius: 8px; 
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.18); 
        padding: 8px 14px; 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        box-sizing: border-box;
        overflow: hidden;
    ">
        <div style="font-size: 1.4rem; line-height: 1; flex-shrink: 0;">📢</div>
        <div style="
            font-size: 0.88rem; 
            font-weight: 700; 
            color: #78350f; 
            line-height: 1.35; 
            overflow: hidden; 
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            word-break: break-word;
        ">
            <?= htmlspecialchars(stripslashes($data_admin_ban['texte']), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</div>

<?php 
    endif;
} catch (PDOException $e) { }
?>
