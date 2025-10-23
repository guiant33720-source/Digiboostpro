<?php
// DigiboostPro v1 - Liste des commandes client
require_once '../config/config.php';

if (!is_logged_in() || !check_role('client')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];

// Filtres
$statut_filter = $_GET['statut'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construire la requête
$where = ["c.client_id = ?"];
$params = [$user_id];

if ($statut_filter !== 'all') {
    $where[] = "c.statut = ?";
    $params[] = $statut_filter;
}

if (!empty($search)) {
    $where[] = "(c.url_site LIKE ? OR p.nom LIKE ? OR c.id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where);

// Compter le total
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    WHERE $where_clause
");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Récupérer les commandes
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.nom as pack_nom,
           u.nom as conseiller_nom, u.prenom as conseiller_prenom
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    LEFT JOIN users u ON c.conseiller_id = u.id
    WHERE $where_clause
    ORDER BY c.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Statuts
$statuts_labels = [
    'en_attente' => ['label' => 'En attente', 'class' => 'warning', 'icon' => 'clock'],
    'en_cours' => ['label' => 'En cours', 'class' => 'info', 'icon' => 'spinner'],
    'en_revision' => ['label' => 'En révision', 'class' => 'primary', 'icon' => 'edit'],
    'terminé' => ['label' => 'Terminé', 'class' => 'success', 'icon' => 'check-circle'],
    'annulé' => ['label' => 'Annulé', 'class' => 'danger', 'icon' => 'times-circle']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <?php include '../includes/client-sidebar.php'; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Mes commandes</h1>
            </div>
            <div class="header-right">
                <a href="nouvelle-commande.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Nouvelle commande
                </a>
                <div class="user-menu">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <strong><?php echo escape($_SESSION['nom_complet']); ?></strong>
                        <span>Client</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- Filtres et Recherche -->
            <div class="filters-bar">
                <form method="GET" action="" class="filters-form">
                    <div class="filter-group">
                        <label for="statut">Statut :</label>
                        <select name="statut" id="statut" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $statut_filter === 'all' ? 'selected' : ''; ?>>Tous</option>
                            <option value="en_attente" <?php echo $statut_filter === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="en_cours" <?php echo $statut_filter === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="en_revision" <?php echo $statut_filter === 'en_revision' ? 'selected' : ''; ?>>En révision</option>
                            <option value="terminé" <?php echo $statut_filter === 'terminé' ? 'selected' : ''; ?>>Terminées</option>
                            <option value="annulé" <?php echo $statut_filter === 'annulé' ? 'selected' : ''; ?>>Annulées</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Rechercher une commande..."
                            value="<?php echo escape($search); ?>"
                            class="search-input"
                        >
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <div class="results-info">
                    <span><?php echo $total; ?> commande(s) trouvée(s)</span>
                </div>
            </div>

            <!-- Liste des commandes -->
            <?php if (count($commandes) > 0): ?>
                <div class="commandes-grid">
                    <?php foreach ($commandes as $cmd): ?>
                        <div class="commande-card">
                            <div class="commande-header">
                                <div class="commande-id">
                                    <i class="fas fa-hashtag"></i>
                                    <?php echo $cmd['id']; ?>
                                </div>
                                <span class="badge badge-<?php echo $statuts_labels[$cmd['statut']]['class']; ?>">
                                    <i class="fas fa-<?php echo $statuts_labels[$cmd['statut']]['icon']; ?>"></i>
                                    <?php echo $statuts_labels[$cmd['statut']]['label']; ?>
                                </span>
                            </div>

                            <div class="commande-body">
                                <h3><?php echo escape($cmd['pack_nom']); ?></h3>
                                
                                <div class="commande-info">
                                    <div class="info-item">
                                        <i class="fas fa-globe"></i>
                                        <span><?php echo escape($cmd['url_site']); ?></span>
                                    </div>
                                    
                                    <?php if ($cmd['conseiller_nom']): ?>
                                        <div class="info-item">
                                            <i class="fas fa-user-tie"></i>
                                            <span><?php echo escape($cmd['conseiller_prenom'] . ' ' . $cmd['conseiller_nom']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="info-item">
                                        <i class="far fa-calendar"></i>
                                        <span><?php echo format_date($cmd['created_at'], 'd/m/Y'); ?></span>
                                    </div>
                                </div>

                                <!-- Progression -->
                                <div class="commande-progress">
                                    <div class="progress-header">
                                        <span>Progression</span>
                                        <strong><?php echo $cmd['progression']; ?>%</strong>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $cmd['progression']; ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="commande-footer">
                                <div class="commande-price">
                                    <span>Montant</span>
                                    <strong><?php echo format_price($cmd['prix_total']); ?></strong>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="commande-detail.php?id=<?php echo $cmd['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        Voir détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&statut=<?php echo $statut_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i>
                                Précédent
                            </a>
                        <?php endif; ?>

                        <div class="pagination-pages">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&statut=<?php echo $statut_filter; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="pagination-page <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&statut=<?php echo $statut_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">
                                Suivant
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Aucune commande trouvée</h3>
                    <p>Vous n'avez pas encore de commande<?php echo !empty($search) ? ' correspondant à votre recherche' : ''; ?>.</p>
                    <a href="nouvelle-commande.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i>
                        Créer ma première commande
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .content-wrapper {
            padding: 2rem;
        }

        .filters-bar {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .filters-form {
            display: flex;
            gap: 1.5rem;
            flex: 1;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 0.95rem;
        }

        .search-group {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 0.95rem;
        }

        .results-info {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .commandes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .commande-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            overflow: hidden;
        }

        .commande-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .commande-header {
            padding: 1.5rem;
            background: var(--gray-50);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gray-200);
        }

        .commande-id {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .commande-body {
            padding: 1.5rem;
        }

        .commande-body h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .commande-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .info-item i {
            color: var(--primary);
            width: 20px;
        }

        .commande-progress {
            margin-top: 1.5rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .commande-footer {
            padding: 1.5rem;
            background: var(--gray-50);
            border-top: 2px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .commande-price span {
            display: block;
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .commande-price strong {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
        }

        .pagination-pages {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-page {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: 2px solid var(--gray-200);
            color: var(--gray-700);
            font-weight: 600;
            transition: var(--transition);
        }

        .pagination-page:hover,
        .pagination-page.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .commandes-grid {
                grid-template-columns: 1fr;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-form {
                flex-direction: column;
            }

            .search-group {
                max-width: 100%;
            }
        }
    </style>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>