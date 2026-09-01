<?php
// =============================================================================
// NOM DU SCRIPT : partials/_code_mail_visuel.php
// REVISION     : 2.0 - Template HTML dynamique connecté à jevend_format_email
// =============================================================================
// Variables attendues : $nom_affiche, $code_securite

// Valeurs par défaut si la base de données n'est pas accessible
$tpl = [
    'couleur_entete'         => '#0f172a',
    'couleur_bouton'         => '#2563eb',
    'couleur_fond'           => '#f1f5f9',
    'titre_salutation'       => 'Bonjour {NOM} !',
    'texte_explicatif'       => 'Voici votre code secret pour accéder à votre espace :',
    'duree_validite_minutes' => 15,
    'texte_session_longue'   => '💡 <strong>Connexion simplifiée :</strong> Vous resterez automatiquement connecté sur cet appareil tant que vous visitez le site régulièrement (sans inactivité de plus de 60 jours).',
    'texte_pied_page'        => '« Premier arrivé, premier vendu » — jevend.com'
];

if (isset($bdd)) {
    try {
        $stmt_tpl = $bdd->query("SELECT * FROM jevend_format_email WHERE cle_template = 'code_connexion' LIMIT 1");
        $data_tpl = $stmt_tpl->fetch(PDO::FETCH_ASSOC);
        if ($data_tpl) {
            $tpl = array_merge($tpl, $data_tpl);
        }
    } catch (PDOException $e) { }
}

// Remplace les balises dynamiques dans la salutation
$salutation = str_replace('{NOM}', htmlspecialchars($nom_affiche ?? 'Membre'), $tpl['titre_salutation']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: <?= htmlspecialchars($tpl['couleur_fond']) ?>; font-family: Arial, sans-serif;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: <?= htmlspecialchars($tpl['couleur_fond']) ?>; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table width="100%" style="max-width: 550px; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;" border="0" cellspacing="0" cellpadding="0">
                    
                    <!-- EN-TÊTE DU MAIL -->
                    <tr>
                        <td align="center" style="background-color: <?= htmlspecialchars($tpl['couleur_entete']) ?>; padding: 25px;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 1.8rem; font-style: italic; letter-spacing: -1px;">jevend.com</h1>
                        </td>
                    </tr>
                    
                    <!-- CORPS DU MESSAGE -->
                    <tr>
                        <td style="padding: 40px 30px; color: #334155; text-align: center;">
                            <h2 style="margin: 0 0 15px 0; font-size: 1.4rem; color: #0f172a;">
                                <?= $salutation ?>
                            </h2>
                            <p style="margin: 0 0 25px 0; font-size: 0.95rem; color: #475569;">
                                <?= htmlspecialchars($tpl['texte_explicatif']) ?>
                            </p>
                            
                            <!-- BLOC DU CODE SECRET -->
                            <div style="text-align: center; margin-bottom: 25px;">
                                <div style="background-color: <?= htmlspecialchars($tpl['couleur_bouton']) ?>; color: #ffffff; font-size: 2.2rem; font-weight: bold; letter-spacing: 8px; padding: 15px 25px; display: inline-block; border-radius: 8px; font-family: Arial, sans-serif; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                                    <?= htmlspecialchars($code_securite ?? '123456') ?>
                                </div>
                            </div>
                            
                            <p style="margin: 0 0 20px 0; font-size: 0.85rem; color: #64748b; line-height: 1.4;">
                                ⏱️ Ce code secret est valide pendant <strong><?= (int)$tpl['duree_validite_minutes'] ?> minutes</strong> pour cette connexion.
                            </p>

                            <!-- ENCADRÉ D'INFORMATION SESSION LONGUE (60 JOURS) -->
                            <?php if (!empty($tpl['texte_session_longue'])): ?>
                            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; margin-top: 20px; text-align: left;">
                                <p style="margin: 0; font-size: 0.85rem; color: #166534; line-height: 1.5;">
                                    <?= $tpl['texte_session_longue'] ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- PIED DE PAGE -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 15px; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #94a3b8;">
                            <?= htmlspecialchars($tpl['texte_pied_page']) ?>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
