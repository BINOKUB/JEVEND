<?php
// =============================================================================
// SCRIPT      : _chat_live_security.php
// PROJET      : JEVEND | BRANCHE : main
// REVISION    : 2.0 | AUTEUR : Dan | DATE : 2026-08-29
// DESC        : Module central de sécurité, capture IP et gestion identité Chat
// NOM DU SCRIPT: admin_modules/_chat_live_security.php
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Récupère l'adresse IP réelle du visiteur (support CDN / Proxys)
 */
function get_client_ip_chat() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Génère le message d'accueil personnalisé (Membre vs Anonyme)
 */
function get_chat_welcome_message($bdd) {
    $est_connecte = isset($_SESSION['id_utilisateur']) && (int)$_SESSION['id_utilisateur'] > 0;
    
    if ($est_connecte) {
        $id_membre = (int)$_SESSION['id_utilisateur'];
        $nom_affichage = $_SESSION['pseudo'] ?? $_SESSION['nom'] ?? null;

        if (!$nom_affichage && isset($bdd)) {
            try {
                $stmt = $bdd->prepare("SELECT pseudo, nom, prenom FROM jevend_membres WHERE id = ? LIMIT 1");
                $stmt->execute([$id_membre]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $nom_affichage = $user['pseudo'] ?? $user['prenom'] ?? $user['nom'];
                }
            } catch (PDOException $e) {}
        }
        
        $nom_final = htmlspecialchars($nom_affichage ?? 'Membre');
        return "Bienvenue <strong>{$nom_final}</strong> ! Choisissez une question ci-dessous ou posez la vôtre.";
    }

    return "Bonjour <strong>Anonyme</strong> ! Choisissez une question ci-dessous ou posez la vôtre.";
}
