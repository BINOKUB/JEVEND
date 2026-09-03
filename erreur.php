<?php
// =============================================================================
// NOM DU SCRIPT : erreur.php
// REVISION     : 1.3 - Dictionnaire d'Erreurs HTTP Enrichi
// SCRIPT COMPLET ET SUIVI
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Récupération du code d'erreur HTTP (404 par défaut)
$code_erreur = (int)($_GET['code'] ?? 404);

// DICTIONNAIRE D'ERREURS ENRICHI
$config_erreurs = [
    401 => [
        'titre'       => 'Authentification Requise',
        'badge'       => '🔑 Erreur 401',
        'message'     => 'Votre session a expiré ou vous devez être connecté pour accéder à cette page.',
        'icone'       => '🔐'
    ],
    403 => [
        'titre'       => 'Accès Interdit',
        'badge'       => '🔒 Erreur 403',
        'message'     => 'Désolé, vous n\'avez pas les autorisations nécessaires pour consulter cette ressource.',
        'icone'       => '🛡️'
    ],
    404 => [
        'titre'       => 'Page ou Annonce Introuvable',
        'badge'       => '🔍 Erreur 404',
        'message'     => 'Oups ! L\'annonce ou la page que vous cherchez a été vendue, supprimée ou l\'URL est incorrecte.',
        'icone'       => '📦'
    ],
    500 => [
        'titre'       => 'Erreur Interne du Serveur',
        'badge'       => '⚠️ Erreur 500',
        'message'     => 'Un problème technique inattendu est survenu sur notre serveur. Nos équipes travaillent à sa résolution.',
        'icone'       => '⚙️'
    ],
    503 => [
        'titre'       => 'Service Temporairement Indisponible',
        'badge'       => '🛠️ Erreur 503',
        'message'     => 'Le site jevend.com est actuellement en maintenance planifiée ou subit une forte affluence. Veuillez réespérer dans quelques minutes.',
        'icone'       => '🚧'
    ]
];

// Configuration courante (fallback 404)
$err = $config_erreurs[$code_erreur] ?? $config_erreurs[404];

// Code de réponse HTTP réel envoyé aux navigateurs et robots
http_response_code(array_key_exists($code_erreur, $config_erreurs) ? $code_erreur : 404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $err['badge'] ?> — jevend.com</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

    <!-- BARRE DE NAVIGATION ADAPTATIVE -->
    <?php 
        if (!empty($_SESSION['id_utilisateur']) && file_exists('partials/_nav_membre.php')) {
            include 'partials/_nav_membre.php';
        } elseif (file_exists('partials/_nav_publique.php')) {
            include 'partials/_nav_publique.php';
        }
    ?>

    <!-- CONTENEUR CENTRÉ -->
    <div style="display: flex; justify-content: center; align-items: center; padding: 30px 20px 50px 20px;">
        <div class="form-bloc" style="max-width: 520px; text-align: center;">
            
            <div style="font-size: 3.5rem; margin-bottom: 10px;"><?= $err['icone'] ?></div>
            
            <span style="display: inline-block; background: #eff6ff; color: #1d4ed8; font-weight: bold; font-size: 0.85rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #bfdbfe; margin-bottom: 15px;">
                <?= $err['badge'] ?>
            </span>

            <h2><?= $err['titre'] ?></h2>
            
            <p style="color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 25px;">
                <?= $err['message'] ?>
            </p>

            <!-- BOUTONS D'ACTION adaptatifs -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if ($code_erreur === 401): ?>
                    <a href="connexion.php" class="btn-action" style="text-decoration: none; flex: 1; min-width: 140px;">
                        🔑 Se connecter
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn-action" style="text-decoration: none; flex: 1; min-width: 140px;">
                        🏠 Accueil
                    </a>
                <?php endif; ?>

                <a href="nous_joindre.php" class="btn-action" style="background-color: #475569; text-decoration: none; flex: 1; min-width: 140px;">
                    ✉️ Nous Joindre
                </a>
            </div>

            <div class="liens-navigation" style="margin-top: 25px;">
                <a href="javascript:history.back()">◄ Revenir à la page précédente</a>
            </div>

        </div>
    </div>

</body>
</html>
