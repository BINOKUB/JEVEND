<?php
// =============================================================================
// NOM DU SCRIPT : admin_memoire.php
// DESCRIPTION : Formulaire d'administration sécurisé pour la section "Mémoire & Mystères"
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// 1. Récupération des données de l'environnement client
$ip_client = $_SERVER['REMOTE_ADDR'];
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$accept_lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

// 2. Filtres de sécurité anti-robots et de langue
$est_un_robot = (empty($user_agent) || preg_match('/bot|crawler|spider|curl|python|wget|libwww|scanner|nikto|ltx71/i', $user_agent));
$langue_francaise = (strpos($accept_lang, 'fr') !== false);

// 3. Vérification de l'administrateur (Email + IP dans le champ 'neq') combinée aux filtres
$stmt = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE courriel = 'douimet61@gmail.com' AND role = 'admin' AND neq = ?");
$stmt->execute([$ip_client]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// L'accès est validé uniquement si l'admin est reconnu ET que ce n'est pas un robot ET que la langue est française
$autorise = ($admin !== false && !$est_un_robot && $langue_francaise);

$message_succes = "";
$message_erreur = "";

// 4. Traitement du formulaire si l'administrateur est autorisé
if ($autorise && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre   = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    if (!empty($titre) && !empty($contenu)) {
        try {
            $stmt_insert = $bdd->prepare("INSERT INTO jevend_memoires (titre, contenu, date_creation) VALUES (?, ?, NOW())");
            $stmt_insert->execute([$titre, $contenu]);
            $message_succes = "La chronique a été publiée avec succès dans la section Mémoire & Mystères !";
        } catch (PDOException $e) {
            $message_erreur = "Erreur de base de données : " . $e->getMessage();
        }
    } else {
        $message_erreur = "Le titre et le contenu de la chronique sont obligatoires.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Mémoire & Mystères</title>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .form-container {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 750px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        }
        .ip-badge {
            background: #0f172a;
            border: 1px solid #38bdf8;
            color: #38bdf8;
            padding: 10px 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9rem;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            background: #0f172a;
            border: 1px solid #475569;
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group textarea {
            height: 250px;
            resize: vertical;
            line-height: 1.5;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #38bdf8;
            outline: none;
            box-shadow: 0 0 6px rgba(56, 189, 248, 0.3);
        }
        .btn-publier {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-publier:hover { background: #1d4ed8; }
        .alerte-refus {
            text-align: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 40px;
            max-width: 450px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        }
        .succes-msg {
            background: rgba(22, 163, 74, 0.2);
            border: 1px solid #16a34a;
            color: #4ade80;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .erreur-msg {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #f87171;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #38bdf8;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <?php if (!$autorise): ?>
        <!-- ÉCRAN DE REFUS NEUTRE (PAGE INTROUVABLE) -->
        <div class="alerte-refus">
            <h2 style="color: #f8fafc; margin-top:0;">Page introuvable</h2>
            <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.5;">
                La page que vous essayez d'atteindre n'existe pas ou a été déplacée.
            </p>
        </div>
    <?php else: ?>
        <!-- FORMULAIRE DE PUBLICATION SÉCURISÉ -->
        <div class="form-container">
            <a href="actualite.php" class="back-link">← Retour à la salle des nouvelles</a>
            
            <div class="ip-badge">
                <span>🛡️ Session Admin (Mémoire & Mystères)</span>
                <span>Votre IP : <strong><?= htmlspecialchars($ip_client) ?></strong></span>
            </div>

            <h2 style="margin-top: 0; font-size: 1.3rem; border-bottom: 1px solid #334155; padding-bottom: 12px; margin-bottom: 20px;">
                📜 Publier une Chronique Patrimoniale
            </h2>

            <?php if (!empty($message_succes)): ?>
                <div class="succes-msg"><?= $message_succes ?></div>
            <?php endif; ?>

            <?php if (!empty($message_erreur)): ?>
                <div class="erreur-msg"><?= $message_erreur ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                
                <div class="form-group">
                    <label for="titre">Titre de la chronique</label>
                    <input type="text" id="titre" name="titre" required placeholder="Ex: La grande épopée du train de la Matapédia...">
                </div>

                <div class="form-group">
                    <label for="contenu">Contenu de l'histoire (Paragraphes)</label>
                    <textarea id="contenu" name="contenu" required placeholder="Colle le texte complet de la chronique ici..."></textarea>
                </div>

                <button type="submit" class="btn-publier">🚀 Publier la chronique</button>
            </form>
        </div>
    <?php endif; ?>

</body>
</html>
