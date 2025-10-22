<?php
// DigiboostPro v1 - Page d'inscription
require_once '../config/config.php';

if (is_logged_in()) {
    redirect('/' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $entreprise = trim($_POST['entreprise'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Validation
        if (empty($nom)) $errors['nom'] = 'Le nom est requis';
        if (empty($prenom)) $errors['prenom'] = 'Le prénom est requis';
        if (empty($email)) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins une majuscule';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }
        
        // Vérifier si l'email existe déjà
        if (empty($errors['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Cet email est déjà utilisé';
            }
        }
        
        if (empty($errors)) {
            try {
                // Récupérer l'ID du rôle client
                $stmt = $pdo->query("SELECT id FROM roles WHERE nom = 'client'");
                $role_client = $stmt->fetch();
                
                // Hasher le mot de passe
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                // Insérer l'utilisateur
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, role_id, nom, prenom, telephone, entreprise, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'actif')
                ");
                $stmt->execute([
                    $email,
                    $password_hash,
                    $role_client['id'],
                    $nom,
                    $prenom,
                    $telephone,
                    $entreprise
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // Logger l'activité
                log_activity($user_id, 'Inscription', 'Nouveau compte créé');
                
                // Envoyer email de bienvenue (simulé)
                send_email(
                    $email,
                    'Bienvenue sur DigiboostPro',
                    "Bonjour $prenom $nom,\n\nVotre compte a été créé avec succès !\n\nVous pouvez maintenant vous connecter et profiter de nos services."
                );
                
                // Créer une notification de bienvenue
                create_notification(
                    $user_id,
                    'bienvenue',
                    'Bienvenue sur DigiboostPro !',
                    'Votre compte a été créé avec succès. Explorez nos services et commandez votre premier audit SEO.',
                    '/client/dashboard.php'
                );
                
                redirect('/public/login.php?registered=1');
            } catch (PDOException $e) {
                $error = 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.';
            }
        } else {
            $error = 'Veuillez corriger les erreurs ci-dessous';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Créer un compte</h1>
                <p>Rejoignez DigiboostPro et boostez votre visibilité</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prenom">
                            <i class="fas fa-user"></i>
                            Prénom *
                        </label>
                        <input 
                            type="text" 
                            id="prenom" 
                            name="prenom" 
                            class="form-control <?php echo isset($errors['prenom']) ? 'is-invalid' : ''; ?>" 
                            placeholder="Jean"
                            value="<?php echo escape($_POST['prenom'] ?? ''); ?>"
                            required
                        >
                        <?php if (isset($errors['prenom'])): ?>
                            <small class="error-text"><?php echo $errors['prenom']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="nom">
                            <i class="fas fa-user"></i>
                            Nom *
                        </label>
                        <input 
                            type="text" 
                            id="nom" 
                            name="nom" 
                            class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>" 
                            placeholder="Dupont"
                            value="<?php echo escape($_POST['nom'] ?? ''); ?>"
                            required
                        >
                        <?php if (isset($errors['nom'])): ?>
                            <small class="error-text"><?php echo $errors['nom']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                        placeholder="jean.dupont@exemple.com"
                        value="<?php echo escape($_POST['email'] ?? ''); ?>"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <small class="error-text"><?php echo $errors['email']; ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="telephone">
                        <i class="fas fa-phone"></i>
                        Téléphone
                    </label>
                    <input 
                        type="tel" 
                        id="telephone" 
                        name="telephone" 
                        class="form-control" 
                        placeholder="06 12 34 56 78"
                        value="<?php echo escape($_POST['telephone'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="entreprise">
                        <i class="fas fa-building"></i>
                        Entreprise
                    </label>
                    <input 
                        type="text" 
                        id="entreprise" 
                        name="entreprise" 
                        class="form-control" 
                        placeholder="Nom de votre entreprise"
                        value="<?php echo escape($_POST['entreprise'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Mot de passe *
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                            placeholder="Minimum 8 caractères"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <small class="error-text"><?php echo $errors['password']; ?></small>
                    <?php else: ?>
                        <small class="help-text">
                            <i class="fas fa-info-circle"></i>
                            8 caractères min, 1 majuscule, 1 chiffre
                        </small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">
                        <i class="fas fa-lock"></i>
                        Confirmer le mot de passe *
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" 
                            placeholder="Retapez votre mot de passe"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password_confirm')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <small class="error-text"><?php echo $errors['password_confirm']; ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>J'accepte les <a href="cgu.php" target="_blank" class="link">conditions générales d'utilisation</a></span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-user-plus"></i>
                    Créer mon compte
                </button>
            </form>

            <div class="auth-divider">
                <span>ou</span>
            </div>

            <div class="auth-footer">
                <p>Vous avez déjà un compte ?</p>
                <a href="login.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </a>
            </div>

            <div class="auth-back">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-control.is-invalid {
            border-color: var(--danger);
        }
        
        .error-text {
            display: block;
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .help-text {
            display: block;
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .help-text i {
            margin-right: 0.25rem;
        }
    </style>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                button.classList.remove('fa-eye');
                button.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                button.classList.remove('fa-eye-slash');
                button.classList.add('fa-eye');
            }
        }
        
        // Validation en temps réel du mot de passe
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password)
            };
            
            // Vous pouvez ajouter des indicateurs visuels ici
        });
    </script>
</body>
</html>