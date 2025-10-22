<?php
// DigiboostPro v1 - Gestion des utilisateurs (Admin)
require_once '../config/config.php';

if (!is_logged_in() || !check_role('admin')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$edit_id = intval($_GET['id'] ?? 0);

// Créer ou modifier un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $id = intval($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $entreprise = trim($_POST['entreprise'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $statut = $_POST['statut'] ?? 'actif';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($nom) || empty($prenom) || $role_id <= 0) {
            $error = 'Tous les champs obligatoires doivent être remplis';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide';
        } else {
            try {
                if ($id > 0) {
                    // Modification
                    $sql = "UPDATE users SET email = ?, nom = ?, prenom = ?, telephone = ?, entreprise = ?, role_id = ?, statut = ?";
                    $params = [$email, $nom, $prenom, $telephone, $entreprise, $role_id, $statut];
                    
                    if (!empty($password)) {
                        $sql .= ", password = ?";
                        $params[] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    log_activity($user_id, 'Utilisateur modifié', "ID: $id");
                    $success = 'Utilisateur modifié avec succès';
                } else {
                    // Création
                    if (empty($password)) {
                        $error = 'Le mot de passe est requis pour un nouvel utilisateur';
                    } else {
                        // Vérifier email unique
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'Cet email est déjà utilisé';
                        } else {
                            $password_hash = password_hash($password, PASSWORD_BCRYPT);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO users (email, password, nom, prenom, telephone, entreprise, role_id, statut)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$email, $password_hash, $nom, $prenom, $telephone, $entreprise, $role_id, $statut]);
                            
                            $new_id = $pdo->lastInsertId();
                            log_activity($user_id, 'Utilisateur créé', "ID: $new_id");
                            $success = 'Utilisateur créé avec succès';
                            $action = 'list';
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'enregistrement';
            }
        }
    }
}

// Supprimer un utilisateur
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id != $user_id) { // Ne pas se supprimer soi-même
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            log_activity($user_id, 'Utilisateur supprimé', "ID: $delete_id");
            $success = 'Utilisateur supprimé';
        } catch (PDOException $e) {
            $error = 'Impossible de supprimer cet utilisateur';
        }
    }
}

// Récupérer les rôles
$stmt = $pdo->query("SELECT * FROM roles ORDER BY nom");
$roles = $stmt->fetchAll();

// Liste des utilisateurs avec filtres
$role_filter = $_GET['role'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if ($role_filter !== 'all') {
    $where[] = "r.nom = ?";
    $params[] = $role_filter;
}

if (!empty($search)) {
    $where[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT u.*, r.nom as role_name,
           (SELECT COUNT(*) FROM commandes WHERE client_id = u.id) as nb_commandes
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE $where_clause
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Si édition, récupérer l'utilisateur
$user_edit = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $user_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion utilisateurs - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <!-- Sidebar Admin -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-rocket"></i>
                <span><?php echo SITE_NAME; ?></span>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="users.php" class="menu-item active">
                <i class="fas fa-users"></i>
                <span>Utilisateurs</span>
            </a>
            <a href="commandes.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Commandes</span>
            </a>
            <a href="services.php" class="menu-item">
                <i class="fas fa-cogs"></i>
                <span>Services & Packs</span>
            </a>
            <a href="coupons.php" class="menu-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Promotions</span>
            </a>
            <a href="litiges.php" class="menu-item">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Litiges</span>
            </a>
            <a href="actualites.php" class="menu-item">
                <i class="fas fa-newspaper"></i>
                <span>Actualités</span>
            </a>
            <a href="pages-editor.php" class="menu-item">
                <i class="fas fa-edit"></i>
                <span>Éditeur de pages</span>
            </a>
            <a href="logs.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Logs d'activité</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-sliders-h"></i>
                <span>Paramètres</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../public/index.php" class="menu-item">
                <i class="fas fa-globe"></i>
                <span>Voir le site</span>
            </a>
            <a href="../public/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Gestion des utilisateurs</h1>
            </div>
            <div class="header-right">
                <?php if ($action === 'list'): ?>
                    <a href="?action=new" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nouvel utilisateur
                    </a>
                <?php endif; ?>
                <div class="user-menu">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="user-info">
                        <strong><?php echo escape($_SESSION['nom_complet']); ?></strong>
                        <span>Administrateur</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo escape($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- Filtres et recherche -->
                <div class="filters-bar">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <label>Rôle :</label>
                            <select name="role" onchange="this.form.submit()">
                                <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>Tous</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrateurs</option>
                                <option value="conseiller" <?php echo $role_filter === 'conseiller' ? 'selected' : ''; ?>>Conseillers</option>
                                <option value="client" <?php echo $role_filter === 'client' ? 'selected' : ''; ?>>Clients</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <input type="text" name="search" placeholder="Rechercher..." value="<?php echo escape($search); ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <div class="results-info">
                        <?php echo count($users); ?> utilisateur(s)
                    </div>
                </div>

                <!-- Table utilisateurs -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Statut</th>
                                        <th>Commandes</th>
                                        <th>Dernière connexion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><strong>#<?php echo $u['id']; ?></strong></td>
                                            <td>
                                                <strong><?php echo escape($u['prenom'] . ' ' . $u['nom']); ?></strong>
                                                <?php if ($u['entreprise']): ?>
                                                    <br><small class="text-muted"><?php echo escape($u['entreprise']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo escape($u['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $u['role_name'] === 'admin' ? 'danger' : ($u['role_name'] === 'conseiller' ? 'primary' : 'info'); ?>">
                                                    <?php echo ucfirst($u['role_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $u['statut'] === 'actif' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($u['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $u['nb_commandes']; ?></td>
                                            <td>
                                                <?php if ($u['last_login']): ?>
                                                    <?php echo format_date($u['last_login'], 'd/m/Y H:i'); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Jamais</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($u['id'] != $user_id): ?>
                                                        <a href="?delete=<?php echo $u['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Supprimer cet utilisateur ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Formulaire création/édition -->
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-<?php echo $action === 'new' ? 'plus' : 'edit'; ?>"></i>
                            <?php echo $action === 'new' ? 'Nouvel utilisateur' : 'Modifier l\'utilisateur'; ?>
                        </h3>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Retour
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="save_user" value="1">
                            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="prenom">
                                        <i class="fas fa-user"></i>
                                        Prénom *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="prenom" 
                                        name="prenom" 
                                        class="form-control"
                                        value="<?php echo escape($user_edit['prenom'] ?? ''); ?>"
                                        required
                                    >
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
                                        class="form-control"
                                        value="<?php echo escape($user_edit['nom'] ?? ''); ?>"
                                        required
                                    >
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
                                    class="form-control"
                                    value="<?php echo escape($user_edit['email'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="form-row-2">
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
                                        value="<?php echo escape($user_edit['telephone'] ?? ''); ?>"
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
                                        value="<?php echo escape($user_edit['entreprise'] ?? ''); ?>"
                                    >
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="role_id">
                                        <i class="fas fa-user-tag"></i>
                                        Rôle *
                                    </label>
                                    <select id="role_id" name="role_id" class="form-control" required>
                                        <option value="">Sélectionnez un rôle</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['id']; ?>" 
                                                <?php echo ($user_edit && $user_edit['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($role['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="statut">
                                        <i class="fas fa-toggle-on"></i>
                                        Statut *
                                    </label>
                                    <select id="statut" name="statut" class="form-control" required>
                                        <option value="actif" <?php echo ($user_edit && $user_edit['statut'] === 'actif') ? 'selected' : ''; ?>>
                                            Actif
                                        </option>
                                        <option value="inactif" <?php echo ($user_edit && $user_edit['statut'] === 'inactif') ? 'selected' : ''; ?>>
                                            Inactif
                                        </option>
                                        <option value="suspendu" <?php echo ($user_edit && $user_edit['statut'] === 'suspendu') ? 'selected' : ''; ?>>
                                            Suspendu
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i>
                                    Mot de passe <?php echo $action === 'new' ? '*' : '(laisser vide pour ne pas modifier)'; ?>
                                </label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control"
                                    <?php echo $action === 'new' ? 'required' : ''; ?>
                                >
                                <small class="help-text">
                                    <i class="fas fa-info-circle"></i>
                                    Minimum 8 caractères, 1 majuscule, 1 chiffre
                                </small>
                            </div>

                            <div class="form-actions">
                                <a href="users.php" class="btn btn-secondary">
                                    Annuler
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--gray-200);
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .form-row-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>