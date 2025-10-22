<?php
// DigiboostPro v1 - Déconnexion
require_once '../config/config.php';

if (is_logged_in()) {
    // Logger l'activité
    log_activity($_SESSION['user_id'], 'Déconnexion', 'Déconnexion réussie');
    
    // Détruire la session
    session_unset();
    session_destroy();
    
    // Créer une nouvelle session pour le message
    session_start();
}

redirect('/public/login.php?logout=1');
?>