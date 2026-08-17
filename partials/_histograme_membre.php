<?php
/*
====================================================
Fichier       : partials/histograme_membre.php
Révision      : v3.0 - Tableau simple à deux colonnes
Description   : Statistiques directes des annonces et listes d'envie
====================================================
*/
?>
<div style="background: #ffffff; border-radius: 8px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
    <h2 style="margin-top: 0; color: #1e3a8a; font-size: 1.4rem; display: flex; align-items: center; gap: 8px;">
        📊 Statistiques des Listes d'Envie
    </h2>
    <p style="color: #475569; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
        Consultez directement le nombre d'acheteurs potentiels ayant ajouté vos annonces à leur liste d'envie.
    </p>

    <?php if (!empty($liste_annonces)): ?>
        <div style="overflow-x: auto;">
            <!-- Tableau inspiré de ton modèle "Shampoing" -->
            <table style="width: 100%; border-collapse: collapse; border: 2px solid #94a3b8; font-size: 0.95rem;">
                <thead>
                    <tr style="background-color: #e2e8f0; color: #1e293b;">
                        <th style="padding: 12px 15px; border: 1px solid #94a3b8; text-align: left; width: 75%;">Annonces</th>
                        <th style="padding: 12px 15px; border: 1px solid #94a3b8; text-align: center; width: 25%;">Liste D'envie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liste_annonces as $item): ?>
                        <?php 
                            $titre = htmlspecialchars($item['titre_objet_nettoye'] ?? 'Annonce sans titre');
                            $nb_envies = (int)($item['nb_prospects'] ?? 0);
                        ?>
                        <tr style="background-color: #f8fafc;">
                            <td style="padding: 10px 15px; border: 1px solid #cbd5e1; color: #0f172a; font-weight: 500;">
                                <?= $titre ?>
                            </td>
                            <td style="padding: 10px 15px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold; font-size: 1.1rem; color: <?= $nb_envies > 0 ? '#16a34a' : '#64748b' ?>;">
                                <?php if ($nb_envies > 0): ?>
                                    🔥 <?= $nb_envies ?>
                                <?php else: ?>
                                    <?= $nb_envies ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 20px; border-radius: 6px; text-align: center; color: #64748b; font-size: 0.9rem;">
            Vous n'avez aucune annonce active pour le moment.
        </div>
    <?php endif; ?>
</div>
