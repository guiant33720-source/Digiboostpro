<?php
// DigiboostPro v1 - Tableau de bord administrateur
require_once '../config/config.php';

if (!is_logged_in() || !check_role('admin')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];

// KPI Globaux
$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role_id IN (SELECT id FROM roles WHERE nom = 'client')) as total_clients,
        (SELECT COUNT(*) FROM commandes) as total_commandes,
        (SELECT SUM(prix_total) FROM commandes WHERE paiement_statut = 'payé') as revenus_total,
        (SELECT COUNT(*) FROM commandes WHERE statut = 'en_cours') as commandes_actives,
        (SELECT COUNT(*) FROM litiges WHERE statut IN ('ouvert', 'en_cours')) as litiges_actifs,
        (SELECT COUNT(*) FROM users WHERE role_id IN (SELECT id FROM roles WHERE nom = 'conseiller')) as total_conseillers
");
$kpi = $stmt->fetch();

// Commandes récentes
$stmt = $pdo->query("
    SELECT c.*, 
           p.nom as pack_nom,
           u.nom as client_nom, u.prenom as client_prenom,
           cons.nom as conseiller_nom, cons.prenom as conseiller_prenom
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    JOIN users u ON c.client_id = u.id
    LEFT JOIN users cons ON c.conseiller_id = cons.id
    ORDER BY c.created_at DESC
    LIMIT 10
");
$commandes_recentes = $stmt->fetchAll();

// Statistiques par mois (6 derniers mois)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as mois,
        COUNT(*) as nb_commandes,
        SUM(prix_total) as revenus
    FROM commandes
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mois ASC
");
$stats_mensuelles = $stmt->fetchAll();

// Tableau de risques/anomalies
$stmt = $pdo->query("
    SELECT 
        'Client inactif' as type,
        CONCAT(u.prenom, ' ', u.nom) as description,
        u.last_login as date,
        'warning' as severite
    FROM users u
    WHERE u.role_id = (SELECT id FROM roles WHERE nom = 'client')
    AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 3 MONTH))
    LIMIT 5
");
$risques = $stmt->fetchAll();

// Ajouter les paiements échoués
$stmt = $pdo->query("
    SELECT 
        'Paiement échoué' as type,
        CONCAT('Commande #', c.id, ' - ', u.prenom, ' ', u.nom) as description,
        c.created_at as date,
        'danger' as severite
    FROM commandes c
    JOIN users u ON c.client_id = u.id
    WHERE c.paiement_statut = 'échoué'
    ORDER BY c.created_at DESC
    LIMIT 5
");
$paiements_echoues = $stmt->fetchAll();
$risques = array_merge($risques, $paiements_echoues);

// Ajouter les litiges ouverts
$stmt = $pdo->query("
    SELECT 
        'Litige ouvert' as type,
        CONCAT('Commande #', l.commande_id, ' - ', u.prenom, ' ', u.nom) as description,
        l.created_at as date,
        'danger' as severite
    FROM litiges l
    JOIN users u ON l.client_id = u.id
    WHERE l.statut IN ('ouvert', 'en_cours')
    ORDER BY l.created_at DESC
    LIMIT 5
");
$litiges_ouverts = $stmt->fetchAll();
$risques = array_merge($risques, $litiges_ouverts);

$statuts_labels = [
    'en_attente' => ['label' => 'En attente', 'class' => 'warning'],
    'en_cours' => ['label' => 'En cours', 'class' => 'info'],
    'en_revision' => ['label' => 'En révision', 'class' => 'primary'],
    'terminé' => ['label' => 'Terminé', 'class' => 'success'],
    'annulé' => ['label' => 'Annulé', 'class' => 'danger']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard - <?php echo SITE_NAME; ?></title>
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
            <a href="dashboard.php" class="menu-item active">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Tableau de bord Administrateur</h1>
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

        <!-- Welcome Banner -->
        <div class="welcome-banner admin-banner">
            <div class="welcome-content">
                <h2>Bienvenue dans l'administration 🚀</h2>
                <p>Vue d'ensemble de votre plateforme DigiboostPro</p>
            </div>
            <div class="quick-stats">
                <div class="quick-stat-item">
                    <i class="fas fa-users"></i>
                    <strong><?php echo $kpi['total_clients']; ?></strong>
                    <span>Clients</span>
                </div>
                <div class="quick-stat-item">
                    <i class="fas fa-shopping-cart"></i>
                    <strong><?php echo $kpi['commandes_actives']; ?></strong>
                    <span>Actives</span>
                </div>
                <div class="quick-stat-item">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong><?php echo $kpi['litiges_actifs']; ?></strong>
                    <span>Litiges</span>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $kpi['total_clients']; ?></h3>
                    <p>Clients inscrits</p>
                    <small class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i> +12% ce mois
                    </small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $kpi['total_commandes']; ?></h3>
                    <p>Commandes totales</p>
                    <small class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i> +8% ce mois
                    </small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo format_price($kpi['revenus_total'] ?? 0); ?></h3>
                    <p>Revenus totaux</p>
                    <small class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i> +15% ce mois
                    </small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $kpi['total_conseillers']; ?></h3>
                    <p>Conseillers actifs</p>
                    <small class="stat-trend neutral">
                        <i class="fas fa-minus"></i> Stable
                    </small>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="admin-grid">
            <!-- Charts -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Évolution des commandes
                    </h3>
                    <select class="chart-filter">
                        <option>6 derniers mois</option>
                        <option>12 derniers mois</option>
                        <option>Cette année</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="chart-placeholder">
                        <canvas id="commandesChart" width="400" height="200"></canvas>
                    </div>
                    <div class="chart-legend">
                        <?php foreach ($stats_mensuelles as $stat): ?>
                            <div class="legend-item">
                                <span class="legend-month"><?php echo date('M Y', strtotime($stat['mois'] . '-01')); ?></span>
                                <span class="legend-value"><?php echo $stat['nb_commandes']; ?> commandes</span>
                                <span class="legend-revenue"><?php echo format_price($stat['revenus']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Risques et Anomalies -->
            <div class="card risks-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-exclamation-triangle"></i>
                        Alertes & Risques
                    </h3>
                    <span class="badge badge-danger"><?php echo count($risques); ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($risques) > 0): ?>
                        <div class="risks-list">
                            <?php foreach (array_slice($risques, 0, 8) as $risque): ?>
                                <div class="risk-item risk-<?php echo $risque['severite']; ?>">
                                    <div class="risk-icon">
                                        <i class="fas fa-<?php echo $risque['severite'] === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'; ?>"></i>
                                    </div>
                                    <div class="risk-content">
                                        <strong><?php echo escape($risque['type']); ?></strong>
                                        <p><?php echo escape($risque['description']); ?></p>
                                        <small><?php echo format_date($risque['date'], 'd/m/Y'); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>Aucune alerte</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-shopping-cart"></i>
                    Commandes récentes
                </h3>
                <a href="commandes.php" class="link-arrow">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Conseiller</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes_recentes as $cmd): ?>
                                <tr>
                                    <td><strong>#<?php echo $cmd['id']; ?></strong></td>
                                    <td><?php echo escape($cmd['client_prenom'] . ' ' . $cmd['client_nom']); ?></td>
                                    <td><?php echo escape($cmd['pack_nom']); ?></td>
                                    <td>
                                        <?php if ($cmd['conseiller_nom']): ?>
                                            <?php echo escape($cmd['conseiller_prenom'] . ' ' . $cmd['conseiller_nom']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non assigné</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo format_price($cmd['prix_total']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $statuts_labels[$cmd['statut']]['class']; ?>">
                                            <?php echo $statuts_labels[$cmd['statut']]['label']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_date($cmd['created_at'], 'd/m/Y'); ?></td>
                                    <td>
                                        <a href="commande-detail.php?id=<?php echo $cmd['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Actions rapides</h3>
            <div class="actions-grid">
                <a href="users.php?action=new" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <span>Ajouter un utilisateur</span>
                </a>
                <a href="services.php?action=new" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nouveau service</span>
                </a>
                <a href="coupons.php?action=new" class="action-card">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Créer un coupon</span>
                </a>
                <a href="actualites.php?action=new" class="action-card">
                    <i class="fas fa-newspaper"></i>
                    <span>Publier une actualité</span>
                </a>
                <a href="backup.php" class="action-card">
                    <i class="fas fa-download"></i>
                    <span>Backup base de données</span>
                </a>
                <a href="settings.php" class="action-card">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres système</span>
                </a>
            </div>
        </div>
    </div>

    <style>
        .admin-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .quick-stats {
            display: flex;
            gap: 2rem;
        }

        .quick-stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius);
        }

        .quick-stat-item i {
            font-size: 2rem;
        }

        .quick-stat-item strong {
            font-size: 2rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .stat-trend.positive {
            color: var(--success);
        }

        .stat-trend.negative {
            color: var(--danger);
        }

        .stat-trend.neutral {
            color: var(--gray-500);
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            padding: 0 2rem 2rem;
        }

        .chart-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-filter {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 0.9rem;
        }

        .chart-placeholder {
            min-height: 250px;
            background: var(--gray-50);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .chart-legend {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            font-size: 0.9rem;
        }

        .legend-month {
            font-weight: 600;
            color: var(--gray-700);
        }

        .legend-value {
            color: var(--gray-600);
        }

        .legend-revenue {
            color: var(--primary);
            font-weight: 600;
        }

        .risks-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .risk-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: var(--radius);
            border-left: 4px solid;
        }

        .risk-item.risk-warning {
            background: #fef3c7;
            border-color: var(--warning);
        }

        .risk-item.risk-danger {
            background: #fee2e2;
            border-color: var(--danger);
        }

        .risk-icon {
            font-size: 1.5rem;
        }

        .risk-item.risk-warning .risk-icon {
            color: var(--warning);
        }

        .risk-item.risk-danger .risk-icon {
            color: var(--danger);
        }

        .risk-content strong {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--gray-900);
        }

        .risk-content p {
            color: var(--gray-700);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .risk-content small {
            color: var(--gray-600);
            font-size: 0.8rem;
        }

        @media (max-width: 1200px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }

            .quick-stats {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {
            .admin-grid {
                padding: 0 1rem 1rem;
            }

            .quick-stats {
                gap: 1rem;
            }

            .quick-stat-item {
                padding: 0.75rem 1rem;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Chart des commandes
        const ctx = document.getElementById('commandesChart');
        if (ctx) {
            const labels = <?php echo json_encode(array_map(function($s) { return date('M Y', strtotime($s['mois'] . '-01')); }, $stats_mensuelles)); ?>;
            const data = <?php echo json_encode(array_map(function($s) { return $s['nb_commandes']; }, $stats_mensuelles)); ?>;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Commandes',
                        data: data,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    </script>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>