<?php
// DigiboostPro v1 - Logs d'activité (Admin)
require_once '../config/config.php';

if (!is_logged_in() || !check_role('admin')) {
    redirect('/public/login.php');
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filtres
$user_filter = intval($_GET['user'] ?? 0);
$action_filter = trim($_GET['action'] ?? '');
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$where = ["1=1"];
$params = [];

if ($user_filter > 0) {
    $where[] = "l.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($action_filter)) {
    $where[] = "l.action LIKE ?";
    $params[] = "%$action_filter%";
}

if (!empty($date_debut)) {
    $where[] = "DATE(l.created_at) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where[] = "DATE(l.created_at) <= ?";
    $params[] = $date_fin;
}

$where_clause = implode(' AND ', $where);

// Compter le total
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs_activite l WHERE $where_clause");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Récupérer les logs
$stmt = $pdo->prepare("
    SELECT l.*, u.nom as user_nom, u.prenom as user_prenom, u.email as user_email
    FROM logs_activite l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE $where_clause
    ORDER BY l.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Récupérer la liste des utilisateurs pour le filtre
$stmt = $pdo->query("SELECT id, nom, prenom FROM users ORDER BY nom, prenom");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs d'activité - <?php echo SITE_NAME; ?></title>
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
            <a href="actualites.php" class="menu-item">
                <i class="fas fa-newspaper"></i>
                <span>Actualités</span>
            </a>
            <a href="pages-editor.php" class="menu-item">
                <i class="fas fa-edit"></i>
                <span>Éditeur de pages</span>
            </a>
            <a href="logs.php" class="menu-item active">
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
                <h1>Logs d'activité</h1>
            </div>
            <div class="header-right">
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
            <!-- Filtres -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        Filtres
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="filters-form-advanced">
                        <div class="form-row-4">
                            <div class="form-group">
                                <label for="user">Utilisateur</label>
                                <select name="user" id="user" class="form-control">
                                    <option value="0">Tous</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo $user_filter == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo escape($u['prenom'] . ' ' . $u['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="action">Action</label>
                                <input 
                                    type="text" 
                                    id="action" 
                                    name="action" 
                                    class="form-control"
                                    placeholder="Ex: Connexion, Commande..."
                                    value="<?php echo escape($action_filter); ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="date_debut">Date début</label>
                                <input 
                                    type="date" 
                                    id="date_debut" 
                                    name="date_debut" 
                                    class="form-control"
                                    value="<?php echo escape($date_debut); ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="date_fin">Date fin</label>
                                <input 
                                    type="date" 
                                    id="date_fin" 
                                    name="date_fin" 
                                    class="form-control"
                                    value="<?php echo escape($date_fin); ?>"
                                >
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Filtrer
                            </button>
                            <a href="logs.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i>
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total; ?></h3>
                        <p>Logs trouvés</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM logs_activite WHERE DATE(created_at) = CURDATE()");
                        $today_count = $stmt->fetch()['count'];
                        ?>
                        <h3><?php echo $today_count; ?></h3>
                        <p>Aujourd'hui</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM logs_activite WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                        $week_count = $stmt->fetch()['count'];
                        ?>
                        <h3><?php echo $week_count; ?></h3>
                        <p>7 derniers jours</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM logs_activite WHERE user_id IS NOT NULL");
                        $users_count = $stmt->fetch()['count'];
                        ?>
                        <h3><?php echo $users_count; ?></h3>
                        <p>Utilisateurs actifs</p>
                    </div>
                </div>
            </div>

            <!-- Table des logs -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-history"></i>
                        Journal d'activité
                    </h3>
                    <span class="results-count"><?php echo count($logs); ?> entrées sur cette page</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table logs-table">
                            <thead>
                                <tr>
                                    <th>Date/Heure</th>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Détails</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo format_date($log['created_at'], 'd/m/Y'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo format_date($log['created_at'], 'H:i:s'); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($log['user_id']): ?>
                                                <strong><?php echo escape($log['user_prenom'] . ' ' . $log['user_nom']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo escape($log['user_email']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Système</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="log-action-badge">
                                                <?php echo escape($log['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['details']): ?>
                                                <small><?php echo escape($log['details']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code><?php echo escape($log['ip_address']); ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&user=<?php echo $user_filter; ?>&action=<?php echo urlencode($action_filter); ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-secondary">
                                    <i class="fas fa-chevron-left"></i>
                                    Précédent
                                </a>
                            <?php endif; ?>

                            <div class="pagination-pages">
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&user=<?php echo $user_filter; ?>&action=<?php echo urlencode($action_filter); ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" 
                                       class="pagination-page <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&user=<?php echo $user_filter; ?>&action=<?php echo urlencode($action_filter); ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-secondary">
                                    Suivant
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .filters-form-advanced {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-row-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .logs-table {
            font-size: 0.9rem;
        }

        .logs-table td {
            vertical-align: top;
        }

        .log-action-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-800);
            border-radius: var(--radius);
            font-size: 0.85rem;
            font-weight: 500;
        }

        code {
            background: var(--gray-100);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius);
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

        .results-count {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        @media (max-width: 1200px) {
            .form-row-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-row-4 {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>