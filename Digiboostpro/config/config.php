<?php
// DigiboostPro v1 - Configuration principale
// config/config.php

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'digiboostpro');
define('DB_USER', 'root');  // Modifier si nécessaire
define('DB_PASS', '');      // Modifier si mot de passe MySQL défini

// Configuration du site
define('SITE_NAME', 'DigiboostPro');
define('SITE_URL', 'http://localhost/digiboostpro');
define('SITE_EMAIL', 'contact@digiboostpro.fr');

// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Sécurité
define('SESSION_LIFETIME', 3600); // 1 heure
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Paramètres des fichiers
define('MAX_FILE_SIZE', 10485760); // 10 MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mettre à 1 en production avec HTTPS
    session_start();
}

// Fonction pour échapper les données
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction pour générer un token CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fonction pour vérifier l'authentification
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Fonction pour vérifier le rôle
function check_role($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $allowed_roles = [
        'admin' => ['admin'],
        'conseiller' => ['admin', 'conseiller'],
        'client' => ['admin', 'conseiller', 'client']
    ];
    
    return in_array($_SESSION['role'], $allowed_roles[$required_role] ?? []);
}

/**
 * Redirection sécurisée
 * @param string $url URL relative ou absolue
 */
function redirect($url) {
    // Si l'URL est vide, rediriger vers la page d'accueil
    if (empty($url)) {
        $url = '/';
    }

    // Supprimer retours à la ligne et espaces invisibles
    $url = trim(str_replace(["\r", "\n"], '', $url));

    // Si l'URL ne commence pas par http ou /, on ajoute /
    if (!preg_match('#^https?://#i', $url) && $url[0] !== '/') {
        $url = '/' . $url;
    }

    // Redirection
    header("Location: " . SITE_URL . $url);
    exit();
}
// Fonction pour logger une activité
function log_activity($user_id, $action, $details = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO logs_activite (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

// Fonction pour créer une notification
function create_notification($user_id, $type, $titre, $message, $lien = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, titre, message, lien)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$user_id, $type, $titre, $message, $lien]);
}

// Fonction pour formater une date
function format_date($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

// Fonction pour formater un prix
function format_price($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

// Fonction pour générer un mot de passe sécurisé
function generate_secure_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
}

// Fonction pour envoyer un email (simulation locale)
function send_email($to, $subject, $message) {
    // En local, on peut utiliser MailHog ou simplement logger
    $log_file = ROOT_PATH . '/logs/emails.log';
    $log_content = date('Y-m-d H:i:s') . "\n";
    $log_content .= "To: $to\n";
    $log_content .= "Subject: $subject\n";
    $log_content .= "Message: $message\n";
    $log_content .= str_repeat('-', 50) . "\n\n";
    
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_content, FILE_APPEND);
    
    return true;
}

// Créer les dossiers nécessaires s'ils n'existent pas
$directories = [
    UPLOAD_PATH,
    UPLOAD_PATH . '/documents',
    UPLOAD_PATH . '/avatars',
    UPLOAD_PATH . '/livrables',
    ROOT_PATH . '/logs',
    ROOT_PATH . '/backups'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>