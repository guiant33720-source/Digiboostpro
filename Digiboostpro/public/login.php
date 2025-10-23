<?php
// DigiboostPro v1 - Page de connexion
require_once '../config/config.php';

// Si déjà connecté, rediriger vers le tableau de bord approprié
if (is_logged_in()) {
    redirect('/' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Veuillez remplir tous les champs';
        } else {
            // Vérifier les tentatives de connexion
            $stmt = $pdo->prepare("
                SELECT u.*, r.nom as role_name FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Vérifier si le compte est verrouillé
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $minutes = ceil((strtotime($user['locked_until']) - time()) / 60);
                    $error = "Compte temporairement verrouillé. Réessayez dans $minutes minute(s).";
                } elseif ($user['statut'] !== 'actif') {
                    $error = 'Votre compte a été suspendu. Contactez l\'administrateur.';
                } elseif (password_verify($password, $user['password'])) {
                    // Connexion réussie
                    // Réinitialiser les tentatives échouées
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$user['id']]);
                    
                    // Créer la session
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = trim($user['role_name']);
                    $_SESSION['nom_complet'] = $user['prenom'] . ' ' . $user['nom'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Logger l'activité
                    log_activity($user['id'], 'Connexion', 'Connexion réussie');
                    
                    // Vérifier si 2FA activé
                    if ($user['two_fa_enabled']) {
                        $_SESSION['pending_2fa'] = true;
                        redirect('/public/verify-2fa.php');
                    } else {
                        // Rediriger vers le tableau de bord approprié
                        redirect('/' . trim($user['role_name']) . '/dashboard.php');
                    }
                } else {
                    // Mot de passe incorrect
                    $attempts = $user['failed_login_attempts'] + 1;
                    
                    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                        $locked_until = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET failed_login_attempts = ?, locked_until = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$attempts, $locked_until, $user['id']]);
                        $error = 'Trop de tentatives échouées. Compte verrouillé pour 15 minutes.';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET failed_login_attempts = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$attempts, $user['id']]);
                        $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                        $error = "Email ou mot de passe incorrect. Il vous reste $remaining tentative(s).";
                    }
                    
                    log_activity($user['id'], 'Tentative de connexion échouée', 'Mot de passe incorrect');
                }
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }
    }
}

if (isset($_GET['registered'])) {
    $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
}

if (isset($_GET['logout'])) {
    $success = 'Vous avez été déconnecté avec succès.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-rocket"></i>
                </div>
                <h1>Connexion</h1>
                <p>Accédez à votre espace personnel</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo escape($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="votreemail@exemple.com"
                        value="<?php echo escape($_POST['email'] ?? ''); ?>"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Mot de passe
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Votre mot de passe"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Se souvenir de moi</span>
                    </label>
                    <a href="forgot-password.php" class="link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>

            <div class="auth-divider">
                <span>ou</span>
            </div>

            <div class="auth-footer">
                <p>Pas encore de compte ?</p>
                <a href="register.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-user-plus"></i>
                    Créer un compte
                </a>
            </div>

            <div class="auth-back">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    Retour à l'accueil
                </a>
            </div>

            <div class="demo-accounts">
                <p><strong>Comptes de test :</strong></p>
                <ul>
                    <li>Admin : admin@digiboostpro.fr</li>
                    <li>Conseiller : conseiller@digiboostpro.fr</li>
                    <li>Client : client@test.fr</li>
                    <li>Mot de passe : password123</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>