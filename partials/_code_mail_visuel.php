<?php
// =============================================================================
// NOM DU SCRIPT : partials/_code_mail_visuel.php
// REVISION     : 1.0 - Template HTML isolé avec mention de Session 60 Jours
// =============================================================================
// Variables attendues : $nom_affiche, $code_securite
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, sans-serif;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table width="100%" style="max-width: 550px; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;" border="0" cellspacing="0" cellpadding="0">
                    
                    <!-- EN-TÊTE DU MAIL -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 25px;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 1.8rem; font-style: italic; letter-spacing: -1px;">jevend.com</h1>
                        </td>
                    </tr>
                    
                    <!-- CORPS DU MESSAGE -->
                    <tr>
                        <td style="padding: 40px 30px; color: #334155; text-align: center;">
                            <h2 style="margin: 0 0 15px 0; font-size: 1.4rem; color: #0f172a;">
                                Bonjour <?= $nom_affiche ?> !
                            </h2>
                            <p style="margin: 0 0 25px 0; font-size: 0.95rem; color: #475569;">
                                Voici votre code secret pour accéder à votre espace :
                            </p>
                            
                            <!-- BLOC DU CODE SECRET -->
                            <div style="text-align: center; margin-bottom: 25px;">
                                <div style="background-color: #2563eb; color: #ffffff; font-size: 2.2rem; font-weight: bold; letter-spacing: 8px; padding: 15px 25px; display: inline-block; border-radius: 8px; font-family: Arial, sans-serif; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                                    <?= $code_securite ?>
                                </div>
                            </div>
                            
                            <p style="margin: 0 0 20px 0; font-size: 0.85rem; color: #64748b; line-height: 1.4;">
                                ⏱️ Ce code secret est valide pendant <strong>15 minutes</strong> pour cette connexion.
                            </p>

                            <!-- ENCADRÉ D'INFORMATION SESSION LONGUE (60 JOURS) -->
                            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; margin-top: 20px; text-align: left;">
                    <p style="margin: 0; font-size: 0.85rem; color: #166534; line-height: 1.5;"> 💡 <strong>Connexion simplifiée :</strong> Vous resterez automatiquement connecté sur cet appareil tant que vous visitez le site régulièremment (sans inactivité de plus de 60 jours).</p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- PIED DE PAGE -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 15px; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #94a3b8;">
                            « Premier arrivé, premier vendu » — jevend.com
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
