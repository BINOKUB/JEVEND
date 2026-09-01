<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_admin_format_email.php
// REVISION     : 1.0 - Gestion du design et aperçu live des courriels
// =============================================================================

$message_email_info = '';

// Traitement de la sauvegarde du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_sauvegarder_email_format'])) {
    try {
        $stmt_upd = $bdd->prepare("
            UPDATE jevend_format_email SET 
                couleur_entete = ?,
                couleur_bouton = ?,
                couleur_fond = ?,
                titre_salutation = ?,
                texte_explicatif = ?,
                duree_validite_minutes = ?,
                texte_session_longue = ?,
                texte_pied_page = ?
            WHERE cle_template = 'code_connexion'
        ");
        
        $stmt_upd->execute([
            trim($_POST['couleur_entete']),
            trim($_POST['couleur_bouton']),
            trim($_POST['couleur_fond']),
            trim($_POST['titre_salutation']),
            trim($_POST['texte_explicatif']),
            (int)$_POST['duree_validite_minutes'],
            trim($_POST['texte_session_longue']),
            trim($_POST['texte_pied_page'])
        ]);
        
        $message_email_info = '<div style="background-color: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">✅ Format de courriel mis à jour avec succès !</div>';
    } catch (PDOException $e) {
        $message_email_info = '<div style="background-color: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px;">❌ Erreur lors de la sauvegarde : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Chargement des données actuelles
$stmt_get = $bdd->query("SELECT * FROM jevend_format_email WHERE cle_template = 'code_connexion' LIMIT 1");
$cfg_email = $stmt_get->fetch(PDO::FETCH_ASSOC);

if (!$cfg_email) {
    $cfg_email = [
        'couleur_entete' => '#0f172a',
        'couleur_bouton' => '#2563eb',
        'couleur_fond' => '#f1f5f9',
        'titre_salutation' => 'Bonjour {NOM} !',
        'texte_explicatif' => 'Voici votre code secret pour accéder à votre espace :',
        'duree_validite_minutes' => 15,
        'texte_session_longue' => '💡 <strong>Connexion simplifiée :</strong> Vous resterez automatiquement connecté sur cet appareil tant que vous visitez le site régulièrement (sans inactivité de plus de 60 jours).',
        'texte_pied_page' => '« Premier arrivé, premier vendu » — jevend.com'
    ];
}
?>

<div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <h2 style="margin-top:0; color:#0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        ✉️ Personnalisation des Courriels (Codes Secrets)
    </h2>
    
    <?= $message_email_info ?>

    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        
        <!-- FORMULAIRE DE CONFIGURATION (GAUCHE) -->
        <div style="flex: 1; min-width: 320px;">
            <form method="POST" action="#onglet-format-email">
                <input type="hidden" name="action_sauvegarder_email_format" value="1">
                
                <h3 style="color:#1e293b; font-size:1.1rem; margin-top:0;">🎨 Couleurs & Habillage</h3>
                
                <div style="display:flex; gap:15px; margin-bottom: 15px;">
                    <div style="flex:1;">
                        <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">En-tête</label>
                        <input type="color" name="couleur_entete" value="<?= htmlspecialchars($cfg_email['couleur_entete']) ?>" style="width:100%; height:40px; cursor:pointer; border:1px solid #ccc; border-radius:4px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Bouton / Code</label>
                        <input type="color" name="couleur_bouton" value="<?= htmlspecialchars($cfg_email['couleur_bouton']) ?>" style="width:100%; height:40px; cursor:pointer; border:1px solid #ccc; border-radius:4px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Fond Arrière</label>
                        <input type="color" name="couleur_fond" value="<?= htmlspecialchars($cfg_email['couleur_fond']) ?>" style="width:100%; height:40px; cursor:pointer; border:1px solid #ccc; border-radius:4px;">
                    </div>
                </div>

                <h3 style="color:#1e293b; font-size:1.1rem; margin-top:20px;">📝 Textes & Contenu</h3>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Salutation (utilisez {NOM})</label>
                    <input type="text" name="titre_salutation" value="<?= htmlspecialchars($cfg_email['titre_salutation']) ?>" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:4px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Texte explicatif du code</label>
                    <input type="text" name="texte_explicatif" value="<?= htmlspecialchars($cfg_email['texte_explicatif']) ?>" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:4px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Durée de validité (Minutes)</label>
                    <input type="number" name="duree_validite_minutes" value="<?= (int)$cfg_email['duree_validite_minutes'] ?>" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:4px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Encadré d'information (HTML autorisé)</label>
                    <textarea name="texte_session_longue" rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:4px; box-sizing:border-box; font-family:sans-serif;"><?= htmlspecialchars($cfg_email['texte_session_longue']) ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Pied de page</label>
                    <input type="text" name="texte_pied_page" value="<?= htmlspecialchars($cfg_email['texte_pied_page']) ?>" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:4px; box-sizing:border-box;">
                </div>

                <button type="submit" style="background:#2563eb; color:#fff; border:none; padding:12px 25px; border-radius:6px; font-weight:bold; cursor:pointer; font-size:1rem; width:100%;">
                    💾 Enregistrer les modifications
                </button>
            </form>
        </div>

        <!-- APERÇU EN TEMPS RÉEL (DROITE) -->
        <div style="flex: 1; min-width: 340px; background:#f8fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
            <h3 style="margin-top:0; color:#0f172a; text-align:center;">👁️ Aperçu Live du Courriel</h3>
            <div style="border:1px solid #cbd5e1; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                <?php
                // Simulation avec des valeurs de test pour l'aperçu
                $nom_affiche = "Daniel";
                $code_securite = "849201";
                include __DIR__ . '/../partials/_code_mail_visuel.php';
                ?>
            </div>
        </div>

    </div>
</div>
