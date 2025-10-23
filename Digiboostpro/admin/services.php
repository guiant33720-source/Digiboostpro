<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Ajouter un service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $nom = trim($_POST['nom']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $description_longue = trim($_POST['description_longue']);
    $prix_ht = floatval($_POST['prix_ht']);
    $tva = floatval($_POST['tva']);
    $categorie = trim($_POST['categorie']);
    
    if (!empty($nom) && !empty($slug) && $prix_ht > 0) {
        // Vérifier slug unique
        $stmt = $pdo->prepare("SELECT id FROM services WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if ($stmt->fetch()) {
            $error = "Ce slug existe déjà.";
        } else {
            // Gérer l'upload d'image
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'service_' . time() . '.' . $filetype;
                    $upload_path = '../uploads/services/' . $new_filename;
                    
                    if (!is_dir('../uploads/services/')) {
                        mkdir('../uploads/services/', 0755, true);
                    }
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image = $new_filename;
                    }
                }
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO services (nom, slug, description, description_longue, prix_ht, tva, categorie, image, statut) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'actif')
                ");
                $stmt->execute([$nom, $slug, $description, $description_longue, $prix_ht, $tva, $categorie, $image]);
                $success = "Service créé avec succès.";
            } catch (PDOException $e) {
                $error = "Erreur lors de la création du service.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Modifier un service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $service_id = $_POST['service_id'];
    $nom = trim($_POST['nom']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $description_longue = trim($_POST['description_longue']);
    $prix_ht = floatval($_POST['prix_ht']);
    $tva = floatval($_POST['tva']);
    $categorie = trim($_POST['categorie']);
    $statut = $_POST['statut'];
    
    // Gérer l'upload d'image
    $image_update = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'service_' . time() . '.' . $filetype;
            $upload_path = '../uploads/services/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Supprimer l'ancienne image
                $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                $old_image = $stmt->fetchColumn();
                if ($old_image && file_exists('../uploads/services/' . $old_image)) {
                    unlink('../uploads/services/' . $old_image);
                }
                $image_update = ", image = '$new_filename'";
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE services 
            SET nom = ?, slug = ?, description = ?, description_longue = ?, 
                prix_ht = ?, tva = ?, categorie = ?, statut = ? $image_update
            WHERE id = ?
        ");
        $stmt->execute([$nom, $slug, $description, $description_longue, $prix_ht, $tva, $categorie, $statut, $service_id]);
        $success = "Service modifié avec succès.";
    } catch (PDOException $e) {
        $error = "Erreur lors de la modification.";
    }
}

// Supprimer un service
if (isset($_GET['delete'])) {
    $service_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    try {
        // Supprimer l'image
        $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        $image = $stmt->fetchColumn();
        if ($image && file_exists('../uploads/services/' . $image)) {
            unlink('../uploads/services/' . $image);
        }
        
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        $success = "Service supprimé avec succès.";
    } catch (PDOException $e) {
        $error = "Impossible de supprimer ce service (commandes liées).";
    }
}

// Récupérer les services
$search = $_GET['search'] ?? '';
$categorie_filter = $_GET['categorie'] ?? '';
$statut_filter = $_GET['statut'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(nom LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($categorie_filter)) {
    $where_conditions[] = "categorie = ?";
    $params[] = $categorie_filter;
}

if (!empty($statut_filter)) {
    $where_conditions[] = "statut = ?";
    $params[] = $statut_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("
    SELECT s.*,
           COUNT(DISTINCT cp.commande_id) as nb_commandes,
           SUM(cp.quantite) as total_ventes
    FROM services s
    LEFT JOIN commande_produits cp ON s.id = cp.service_id
    $where_clause
    GROUP BY s.id
    ORDER BY s.ordre ASC, s.nom ASC
");
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catégories disponibles
$stmt = $pdo->query("SELECT DISTINCT categorie FROM services WHERE categorie IS NOT NULL ORDER BY categorie");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Statistiques
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
        AVG(prix_ht) as prix_moyen
    FROM services
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Services - Admin Digiboostpro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header-admin.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/sidebar-admin.php'; ?>
        
        <main class="dashboard-main">
            <div class="page-header">
                <h1><i class="fas fa-box"></i> Gestion des Services</h1>
                <button class="btn btn-primary" id="addServiceBtn">
                    <i class="fas fa-plus"></i> Nouveau service
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total'] ?></h3>
                        <p>Total services</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['actifs'] ?></h3>
                        <p>Services actifs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['prix_moyen'], 2) ?>€</h3>
                        <p>Prix moyen HT</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= count($categories) ?></h3>
                        <p>Catégories</p>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card">
                <form method="GET" class="filters-form">
                    <div class="filters-row">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Rechercher un service..." 
                                   class="form-input" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="filter-group">
                            <select name="categorie" class="form-select">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" 
                                            <?= $categorie_filter === $cat ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="statut" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actif</option>
                                <option value="inactif" <?= $statut_filter === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                <option value="archive" <?= $statut_filter === 'archive' ? 'selected' : '' ?>>Archivé</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                        <a href="services.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Liste des services -->
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <div class="service-image">
                            <?php if ($service['image']): ?>
                                <img src="../uploads/services/<?= htmlspecialchars($service['image']) ?>" alt="<?= htmlspecialchars($service['nom']) ?>">
                            <?php else: ?>
                                <div class="service-no-image">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <span class="service-status badge badge-<?= $service['statut'] ?>">
                                <?= ucfirst($service['statut']) ?>
                            </span>
                        </div>
                        <div class="service-content">
                            <div class="service-header">
                                <h3><?= htmlspecialchars($service['nom']) ?></h3>
                                <?php if ($service['categorie']): ?>
                                    <span class="service-category"><?= htmlspecialchars($service['categorie']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                            <div class="service-stats">
                                <div class="stat-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <span><strong><?= number_format($service['prix_ht'], 2) ?>€</strong> HT</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span><?= $service['nb_commandes'] ?? 0 ?> commandes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-chart-line"></i>
                                    <span><?= $service['total_ventes'] ?? 0 ?> ventes</span>
                                </div>
                            </div>
                        </div>
                        <div class="service-actions">
                            <button class="btn btn-sm btn-warning" 
                                    onclick='editService(<?= json_encode($service) ?>)'>
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <a href="?delete=<?= $service['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Confirmer la suppression ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($services)): ?>
                    <p class="no-data">Aucun service trouvé</p>
                <?php endif; ?>
            </div>

            <!-- Modal Ajouter -->
            <div id="addServiceModal" class="modal">
                <div class="modal-content modal-large">
                    <span class="close" onclick="closeModal('addServiceModal')">&times;</span>
                    <h2>Nouveau Service</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom du service *</label>
                                <input type="text" name="nom" id="edit_nom" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Slug (URL) *</label>
                                <input type="text" name="slug" id="edit_slug" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description courte *</label>
                            <textarea name="description" id="edit_description" class="form-textarea" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description détaillée</label>
                            <textarea name="description_longue" id="edit_description_longue" class="form-textarea" rows="5"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Prix HT *</label>
                                <input type="number" name="prix_ht" id="edit_prix_ht" class="form-input" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>TVA (%) *</label>
                                <input type="number" name="tva" id="edit_tva" class="form-input" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Catégorie</label>
                                <input type="text" name="categorie" id="edit_categorie" class="form-input" list="categories-list">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut" id="edit_statut" class="form-select">
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                                <option value="archive">Archivé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nouvelle image</label>
                            <input type="file" name="image" class="form-input" accept="image/*">
                            <small>Laisser vide pour conserver l'image actuelle</small>
                        </div>
                        <button type="submit" name="edit_service" class="btn btn-warning">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const addServiceModal = document.getElementById('addServiceModal');
        const editServiceModal = document.getElementById('editServiceModal');
        const addBtn = document.getElementById('addServiceBtn');

        addBtn.onclick = () => addServiceModal.style.display = 'block';

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editService(service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_nom').value = service.nom;
            document.getElementById('edit_slug').value = service.slug;
            document.getElementById('edit_description').value = service.description;
            document.getElementById('edit_description_longue').value = service.description_longue || '';
            document.getElementById('edit_prix_ht').value = service.prix_ht;
            document.getElementById('edit_tva').value = service.tva;
            document.getElementById('edit_categorie').value = service.categorie || '';
            document.getElementById('edit_statut').value = service.statut;
            editServiceModal.style.display = 'block';
        }

        window.onclick = (e) => {
            if (e.target == addServiceModal) addServiceModal.style.display = 'none';
            if (e.target == editServiceModal) editServiceModal.style.display = 'none';
        }

        // Auto-génération du slug
        document.querySelector('#addServiceModal input[name="nom"]').addEventListener('input', function(e) {
            const slug = e.target.value
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.querySelector('#addServiceModal input[name="slug"]').value = slug;
        });
    </script>
</body>
</html><label>Nom du service *</label>
                                <input type="text" name="nom" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Slug (URL) *</label>
                                <input type="text" name="slug" class="form-input" required 
                                       placeholder="ex: creation-site-web">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description courte *</label>
                            <textarea name="description" class="form-textarea" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description détaillée</label>
                            <textarea name="description_longue" class="form-textarea" rows="5"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Prix HT *</label>
                                <input type="number" name="prix_ht" class="form-input" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>TVA (%) *</label>
                                <input type="number" name="tva" class="form-input" step="0.01" value="20" required>
                            </div>
                            <div class="form-group">
                                <label>Catégorie</label>
                                <input type="text" name="categorie" class="form-input" list="categories-list">
                                <datalist id="categories-list">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" class="form-input" accept="image/*">
                        </div>
                        <button type="submit" name="add_service" class="btn btn-primary">
                            <i class="fas fa-save"></i> Créer le service
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal Modifier -->
            <div id="editServiceModal" class="modal">
                <div class="modal-content modal-large">
                    <span class="close" onclick="closeModal('editServiceModal')">&times;</span>
                    <h2>Modifier le Service</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="service_id" id="edit_service_id">
                        <div class="form-row">
                            <div class="form-group">