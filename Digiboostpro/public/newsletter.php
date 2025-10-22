<?php
// DigiboostPro v1 - Inscription Newsletter
require_once '../config/config.php';

$redirect_url = $_SERVER['HTTP_REFERER'] ?? '/public/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        redirect($redirect_url . '?newsletter=error_empty');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect($redirect_url . '?newsletter=error_invalid');
    }
    
    try {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id, actif FROM newsletter WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if ($existing['actif']) {
                redirect($redirect_url . '?newsletter=already');
            } else {
                // Réactiver l'email
                $stmt = $pdo->prepare("UPDATE newsletter SET actif = TRUE WHERE email = ?");
                $stmt->execute([$email]);
                redirect($redirect_url . '?newsletter=reactivated');
            }
        } else {
            // Nouvelle inscription
            $stmt = $pdo->prepare("INSERT INTO newsletter (email, actif) VALUES (?, TRUE)");
            $stmt->execute([$email]);
            
            // Envoyer email de confirmation (simulé)
            send_email(
                $email,
                'Bienvenue dans notre newsletter - ' . SITE_NAME,
                "Bonjour,\n\nMerci de vous être inscrit à notre newsletter !\n\nVous recevrez désormais nos actualités, conseils et offres exclusives.\n\nÀ bientôt !\nL'équipe " . SITE_NAME
            );
            
            // Logger l'activité
            log_activity(null, 'Inscription newsletter', "Email: $email");
            
            redirect($redirect_url . '?newsletter=success');
        }
    } catch (PDOException $e) {
        error_log("Erreur newsletter: " . $e->getMessage());
        redirect($redirect_url . '?newsletter=error');
    }
} else {
    redirect($redirect_url);
}
?>