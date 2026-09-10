<?php
// =============================================================================
// NOM DU SCRIPT : err_jv.php
// DESCRIPTION : Page d'affichage d'erreur aveugle pour la sécurité JEVEND
// =============================================================================
session_start();
$code_erreur = $_GET['code'] ?? 'ERR_JV-GEN';

// Traçabilité interne discrète dans les logs du serveur
error_log("JEVEND Sécurité - Requête bloquée [Ref: " . $code_erreur . "] IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Erreur de traitement - jevend.com</title>
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: #1e293b; padding: 2rem; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3); max-width: 400px; }
        h1 { font-size: 1.25rem; margin-bottom: 1rem; color: #f87171; }
        p { font-size: 0.95rem; color: #94a3b8; line-height: 1.5; }
        .code { margin-top: 1.5rem; font-size: 0.8rem; color: #64748b; font-family: monospace; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Une erreur est survenue</h1>
        <p>Le traitement de votre requête n'a pas pu aboutir. Veuillez réessayer ou contacter le support technique si le problème persiste.</p>
        <div class="code">Réf : <?php echo htmlspecialchars($code_erreur); ?></div>
    </div>
</body>
</html>
