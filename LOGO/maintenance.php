<?php
// =============================================================================
// NOM DU SCRIPT : maintenance.php
// REVISION     : 1.1 - Visuel Devanture de Commerce Fermé Dynamique
// =============================================================================

// Récupération des données dynamiques si non transmises
if (!isset($heure_ouverture) || !isset($message_maintenance)) {
    try {
        $stmt_m = $bdd->query("SELECT cle_parametre, valeur_parametre FROM jevend_parametres WHERE cle_parametre IN ('maintenance_heure_ouverture', 'maintenance_message')");
        $params_m = $stmt_m->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $heure_ouverture = $params_m['maintenance_heure_ouverture'] ?? '14:00';
        $message_maintenance = $params_m['maintenance_message'] ?? 'Mise à jour de notre système en cours. Réouverture imminente !';
    } catch (PDOException $e) {
        $heure_ouverture = 'Bientôt';
        $message_maintenance = 'Mise à jour du système en cours.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>jevend.com — Fermé pour maintenance</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .storefront {
            background-color: #1e293b;
            border: 2px solid #334155;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .store-header {
            background-color: #0f172a;
            padding: 20px;
            border-bottom: 2px solid #334155;
        }
        .store-logo {
            font-size: 2rem;
            font-weight: bold;
            font-style: italic;
            color: #2563eb;
            letter-spacing: -1px;
        }
        .store-body {
            padding: 40px 25px;
        }
        .sign-closed {
            background-color: #dc2626;
            color: #ffffff;
            display: inline-block;
            padding: 8px 20px;
            font-weight: bold;
            font-size: 1.1rem;
            letter-spacing: 2px;
            border-radius: 4px;
            text-transform: uppercase;
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.4);
            margin-bottom: 25px;
        }
        .opening-time {
            background-color: #0f172a;
            border: 1px dashed #475569;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .opening-time span {
            display: block;
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        .opening-time strong {
            color: #38bdf8;
            font-size: 1.5rem;
        }
        .message {
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .store-footer {
            background-color: #0f172a;
            padding: 15px;
            border-top: 1px solid #334155;
            font-size: 0.8rem;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="storefront">
    <div class="store-header">
        <div class="store-logo">jevend.com</div>
    </div>
    
    <div class="store-body">
        <div class="sign-closed">FERMÉ TEMPORAIREMENT</div>
        
        <p class="message"><?= htmlspecialchars($message_maintenance) ?></p>
        
        <div class="opening-time">
            <span>RÉOUVERTURE PRÉVUE À</span>
            <strong><?= htmlspecialchars($heure_ouverture) ?></strong>
        </div>
        
        <p class="message" style="font-size: 0.85rem; color: #94a3b8;">
            Nos portes sont présentement closes pour ajustements techniques. Merci de votre patience !
        </p>
    </div>
    
    <div class="store-footer">
        « Premier arrivé, premier vendu » — jevend.com
    </div>
</div>

</body>
</html>
<?php exit(); ?>
