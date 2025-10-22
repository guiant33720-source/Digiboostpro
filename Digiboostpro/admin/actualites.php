<?php
// DigiboostPro v1 - Gestion des actualités (Admin)
require_once '../config/config.php';

if (!is_logged_in() || !check_role('admin')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$edit_id = intval($_GET['id'] ?? 0);

// Créer ou modifier une actualité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_actualite'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $id = intval($_POST['id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $statut = $_POST['statut'] ?? 'brouillon';
        $image = $_POST['current_image'] ?? '';
        
        // Upload image si fournie
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Type de fichier non autorisé';
            } elseif ($file_size > 5242880) { // 5MB
                $error = 'Fichier trop volumineux (max 5MB)';
            } else {
                $upload_dir = UPLOAD_PATH . '/actualites/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = $new_filename;
                    
                    // Supprimer ancienne image si modification
                    if ($id > 0 && !empty($_POST['current_image'])) {
                        $old_image = $upload_dir . $_POST['current_image'];
                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                }
            }
        }
        
        if (empty($error)) {
            if (empty($titre) || empty($contenu)) {
                $error = 'Le titre et le contenu sont requis';
            } else {
                try {
                    if ($id > 0) {
                        // Modification
                        $stmt = $pdo->prepare("
                            UPDATE actualites 
                            SET titre = ?, contenu = ?, image = ?, categorie = ?, statut = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$titre, $contenu, $image, $categorie, $statut, $id]);
                        
                        log_activity($user_id, 'Actualité modifiée', "ID: $id");
                        $success = 'Actualité modifiée avec succès';
                    } else {
                        // Création
                        $stmt = $pdo->prepare("
                            INSERT INTO actualites (titre, contenu, image, auteur_id, categorie, statut)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$titre, $contenu, $image, $user_id, $categorie, $statut]);
                        
                        $new_id = $pdo->lastInsertId();
                        log_activity($user_id, 'Actualité créée', "ID: $new_id");
                        $success = 'Actualité créée avec succès';
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    $error = 'Erreur lors de l\'enregistrement';
                }
            }
        }
    }
}

// Supprimer une actualité
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    try {
        // Récupérer l'image pour la supprimer
        $stmt = $pdo->prepare("SELECT image FROM actualites WHERE id = ?");
        $stmt->execute([$delete_id]);
        $actu = $stmt->fetch();
        
        if ($actu && !empty($actu['image'])) {
            $image_path = UPLOAD_PATH . '/actualites/' . $actu['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM actualites WHERE id = ?");
        $stmt->execute([$delete_id]);
        log_activity($user_id, 'Actualité supprimée', "ID: $delete_id");
        $success = 'Actualité supprimée';
    } catch (PDOException $e) {
        $error = 'Impossible de supprimer cette actualité';
    }
}

// Liste des actualités
$stmt = $pdo->query("
    SELECT a.*, u.nom as auteur_nom, u.prenom as auteur_prenom,
           (SELECT COUNT(*) FROM likes WHERE actualite_id = a.id) as likes_count,
           (SELECT COUNT(*) FROM commentaires WHERE actualite_id = a.id) as commentaires_count
    FROM actualites a
    JOIN users u ON a.auteur_id = u.id
    ORDER BY a.created_at DESC
");
$actualites = $stmt->fetchAll();

// Si édition, récupérer l'actualité
$actualite_edit = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM actualites WHERE id = ?");
    $stmt->execute([$edit_id]);
    $actualite_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion actualités - <?php echo SITE_NAME; ?></title>
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
            <a href="users.php" class="menu-item">
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
            <a href="actualites.php" class="menu-item active">
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
                <h1>Gestion des actualités</h1>
            </div>
            <div class="header-right">
                <?php if ($action === 'list'): ?>
                    <a href="?action=new" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nouvelle actualité
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
                <!-- Liste des actualités -->
                <div class="actualites-admin-grid">
                    <?php foreach ($actualites as $actu): ?>
                        <div class="actualite-admin-card">
                            <?php if ($actu['image']): ?>
                                <div class="actualite-admin-image" style="background-image: url('../uploads/actualites/<?php echo escape($actu['image']); ?>');"></div>
                            <?php else: ?>
                                <div class="actualite-admin-image actualite-no-image">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="actualite-admin-content">
                                <div class="actualite-admin-header">
                                    <h3><?php echo escape($actu['titre']); ?></h3>
                                    <span class="badge badge-<?php echo $actu['statut'] === 'publié' ? 'success' : ($actu['statut'] === 'brouillon' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($actu['statut']); ?>
                                    </span>
                                </div>

                                <?php if ($actu['categorie']): ?>
                                    <div class="actualite-category">
                                        <i class="fas fa-tag"></i>
                                        <?php echo escape($actu['categorie']); ?>
                                    </div>
                                <?php endif; ?>

                                <p class="actualite-excerpt">
                                    <?php echo escape(substr(strip_tags($actu['contenu']), 0, 150)); ?>...
                                </p>

                                <div class="actualite-meta">
                                    <span><i class="far fa-user"></i> <?php echo escape($actu['auteur_prenom'] . ' ' . $actu['auteur_nom']); ?></span>
                                    <span><i class="far fa-calendar"></i> <?php echo format_date($actu['created_at'], 'd/m/Y'); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo $actu['vues']; ?></span>
                                    <span><i class="far fa-heart"></i> <?php echo $actu['likes_count']; ?></span>
                                    <span><i class="far fa-comment"></i> <?php echo $actu['commentaires_count']; ?></span>
                                </div>

                                <div class="actualite-actions">
                                    <a href="../public/actualite.php?id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-eye"></i>
                                        Voir
                                    </a>
                                    <a href="?action=edit&id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                        Modifier
                                    </a>
                                    <a href="?delete=<?php echo $actu['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Supprimer cette actualité ?')">
                                        <i class="fas fa-trash"></i>
                                        Supprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Formulaire création/édition -->
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-<?php echo $action === 'new' ? 'plus' : 'edit'; ?>"></i>
                            <?php echo $action === 'new' ? 'Nouvelle actualité' : 'Modifier l\'actualité'; ?>
                        </h3>
                        <a href="actualites.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Retour
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="save_actualite" value="1">
                            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                            <input type="hidden" name="current_image" value="<?php echo escape($actualite_edit['image'] ?? ''); ?>">

                            <div class="form-group">
                                <label for="titre">
                                    <i class="fas fa-heading"></i>
                                    Titre *
                                </label>
                                <input 
                                    type="text" 
                                    id="titre" 
                                    name="titre" 
                                    class="form-control"
                                    value="<?php echo escape($actualite_edit['titre'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="categorie">
                                        <i class="fas fa-tag"></i>
                                        Catégorie
                                    </label>
                                    <input 
                                        type="text" 
                                        id="categorie" 
                                        name="categorie" 
                                        class="form-control"
                                        value="<?php echo escape($actualite_edit['categorie'] ?? ''); ?>"
                                        placeholder="Ex: Actualités, Tutoriels, Conseils..."
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="statut">
                                        <i class="fas fa-toggle-on"></i>
                                        Statut *
                                    </label>
                                    <select id="statut" name="statut" class="form-control" required>
                                        <option value="brouillon" <?php echo ($actualite_edit && $actualite_edit['statut'] === 'brouillon') ? 'selected' : ''; ?>>
                                            Brouillon
                                        </option>
                                        <option value="publié" <?php echo ($actualite_edit && $actualite_edit['statut'] === 'publié') ? 'selected' : ''; ?>>
                                            Publié
                                        </option>
                                        <option value="archivé" <?php echo ($actualite_edit && $actualite_edit['statut'] === 'archivé') ? 'selected' : ''; ?>>
                                            Archivé
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="contenu">
                                    <i class="fas fa-align-left"></i>
                                    Contenu *
                                </label>
                                <textarea 
                                    id="contenu" 
                                    name="contenu" 
                                    class="form-control"
                                    rows="12"
                                    required
                                ><?php echo escape($actualite_edit['contenu'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="image">
                                    <i class="fas fa-image"></i>
                                    Image de couverture
                                </label>
                                
                                <?php if ($actualite_edit && !empty($actualite_edit['image'])): ?>
                                    <div class="current-image">
                                        <img src="../uploads/actualites/<?php echo escape($actualite_edit['image']); ?>" alt="Image actuelle">
                                        <p class="help-text">Image actuelle (sera remplacée si vous en uploadez une nouvelle)</p>
                                    </div>
                                <?php endif; ?>
                                
                                <input 
                                    type="file" 
                                    id="image" 
                                    name="image" 
                                    class="form-control"
                                    accept="image/jpeg,image/jpg,image/png,image/gif"
                                >
                                <small class="help-text">
                                    <i class="fas fa-info-circle"></i>
                                    JPG, PNG ou GIF - Max 5MB
                                </small>
                            </div>

                            <div class="form-actions">
                                <a href="actualites.php" class="btn btn-secondary">
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
        .actualites-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .actualite-admin-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .actualite-admin-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .actualite-admin-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }

        .actualite-no-image {
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 4rem;
        }

        .actualite-admin-content {
            padding: 1.5rem;
        }

        .actualite-admin-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .actualite-admin-header h3 {
            font-size: 1.25rem;
            margin: 0;
            flex: 1;
        }

        .actualite-category {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .actualite-excerpt {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .actualite-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1rem 0;
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .actualite-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .actualite-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .current-image {
            margin-bottom: 1rem;
        }
        .current-image img {
            max-width: 300px;
            height: auto;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .current-image .help-text {
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .actualites-admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>