<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Récupérer les infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Mise à jour des informations personnelles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $ville = trim($_POST['ville']);
    $code_postal = trim($_POST['code_postal']);
    $pays = trim($_POST['pays']);
    
    if (!empty($nom) && !empty($prenom) && !empty($email)) {
        // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, email = ?, telephone = ?, 
                        adresse = ?, ville = ?, code_postal = ?, pays = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $ville, $code_postal, $pays, $user_id]);
                $success = "Informations mises à jour avec succès.";
                
                // Recharger les données
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $user['password'])) {
        $error = "Mot de passe actuel incorrect.";
    } elseif (strlen($new_password) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $success = "Mot de passe modifié avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors du changement de mot de passe.";
        }
    }
}

// Upload de photo de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($_FILES['photo']['size'] <= $max_size) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $filetype;
                $upload_path = '../uploads/profiles/' . $new_filename;
                
                // Créer le dossier si nécessaire
                if (!is_dir('../uploads/profiles/')) {
                    mkdir('../uploads/profiles/', 0755, true);
                }
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne photo si elle existe
                    if (!empty($user['photo']) && file_exists('../uploads/profiles/' . $user['photo'])) {
                        unlink('../uploads/profiles/' . $user['photo']);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?");
                    $stmt->execute([$new_filename, $user_id]);
                    $success = "Photo de profil mise à jour.";
                    
                    // Recharger les données
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Erreur lors de l'upload de la photo.";
                }
            } else {
                $error = "La photo ne doit pas dépasser 5MB.";
            }
        } else {
            $error = "Format de fichier non autorisé. Utilisez JPG, PNG ou GIF.";
        }
    }
}

// Statistiques du client
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM commandes WHERE client_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT SUM(total) as total_depense FROM commandes WHERE client_id = ? AND statut = 'terminee'");
$stmt->execute([$user_id]);
$depenses = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Digiboostpro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header-client.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/sidebar-client.php'; ?>
        
        <main class="dashboard-main">
            <div class="page-header">
                <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="profile-grid">
                <!-- Carte Profil -->
                <div class="card profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php if (!empty($user['photo'])): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($user['photo']) ?>" alt="Photo de profil">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
                            <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                            <span class="badge badge-success">Client actif</span>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-shopping-cart"></i>
                            <div>
                                <h3><?= $stats['total'] ?></h3>
                                <p>Commandes</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-euro-sign"></i>
                            <div>
                                <h3><?= number_format($depenses['total_depense'] ?? 0, 2) ?>€</h3>
                                <p>Total dépensé</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-calendar"></i>
                            <div>
                                <h3><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></h3>
                                <p>Membre depuis</p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="photo-upload-form">
                        <label for="photo" class="btn btn-secondary btn-block">
                            <i class="fas fa-camera"></i> Changer la photo
                        </label>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        <input type="hidden" name="upload_photo" value="1">
                    </form>
                </div>

                <!-- Informations personnelles -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Informations Personnelles</h2>
                    </div>
                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="nom" class="form-input" 
                                       value="<?= htmlspecialchars($user['nom']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Prénom *</label>
                                <input type="text" name="prenom" class="form-input" 
                                       value="<?= htmlspecialchars($user['prenom']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-input" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" name="telephone" class="form-input" 
                                       value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Adresse</label>
                            <input type="text" name="adresse" class="form-input" 
                                   value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ville</label>
                                <input type="text" name="ville" class="form-input" 
                                       value="<?= htmlspecialchars($user['ville'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Code Postal</label>
                                <input type="text" name="code_postal" class="form-input" 
                                       value="<?= htmlspecialchars($user['code_postal'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Pays</label>
                                <input type="text" name="pays" class="form-input" 
                                       value="<?= htmlspecialchars($user['pays'] ?? 'France') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_info" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>

                <!-- Sécurité -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-lock"></i> Sécurité</h2>
                    </div>
                    <form method="POST" class="password-form">
                        <div class="form-group">
                            <label>Mot de passe actuel *</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe *</label>
                            <input type="password" name="new_password" class="form-input" 
                                   minlength="8" required>
                            <small>Minimum 8 caractères</small>
                        </div>
                        <div class="form-group">
                            <label>Confirmer le nouveau mot de passe *</label>
                            <input type="password" name="confirm_password" class="form-input" 
                                   minlength="8" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>

                <!-- Préférences -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-cog"></i> Préférences</h2>
                    </div>
                    <div class="preferences-list">
                        <div class="preference-item">
                            <div>
                                <h4>Notifications par email</h4>
                                <p>Recevoir les notifications importantes par email</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="preference-item">
                            <div>
                                <h4>Newsletter</h4>
                                <p>Recevoir les offres et actualités</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="preference-item">
                            <div>
                                <h4>Alertes SMS</h4>
                                <p>Recevoir des alertes par SMS</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Validation du formulaire de mot de passe
        document.querySelector('.password-form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
            }
        });
    </script>
</body>
</html>