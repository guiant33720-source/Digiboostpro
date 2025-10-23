<?php
// DigiboostPro v1 - Tableau de bord conseiller
require_once '../config/config.php';

// Vérification authentification et rôle
if (!is_logged_in() || !check_role('conseiller')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer les statistiques du conseiller avec alias SQL explicites
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT c.id) as total_commandes,
        SUM(CASE WHEN c.statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN c.statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN c.statut = 'terminé' THEN 1 ELSE 0 END) as terminees,
        COUNT(DISTINCT c.client_id) as nb_clients,
        AVG(c.note_client) as note_moyenne
    FROM commandes c
    WHERE c.conseiller_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Récupérer les commandes récentes avec alias explicites
$stmt = $pdo->prepare("
    SELECT 
        c.id as commande_id,
        c.statut as commande_statut,
        c.progression as commande_progression,
        c.created_at as commande_date,
        c.url_site as commande_url,
        p.nom as pack_nom,
        u.id as client_id,
        u.nom as client_nom,
        u.prenom as client_prenom
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    JOIN users u ON c.client_id = u.id
    WHERE c.conseiller_id = ?
    ORDER BY c.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$commandes_recentes = $stmt->fetchAll();

// Récupérer les messages non lus avec alias explicites
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.id) as non_lus
    FROM messages m
    WHERE m.destinataire_id = ? AND m.lu = FALSE
");
$stmt->execute([$user_id]);
$messages_non_lus = $stmt->fetch()['non_lus'];

// Récupérer les clients assignés avec alias explicites
$stmt = $pdo->prepare("
    SELECT DISTINCT
        u.id as client_id,
        u.nom as client_nom,
        u.prenom as client_prenom,
        u.email as client_email,
        u.entreprise as client_entreprise,
        COUNT(DISTINCT c.id) as nb_commandes,
        MAX(c.created_at) as derniere_commande
    FROM users u
    LEFT JOIN commandes c ON c.client_id = u.id AND c.conseiller_id = ?
    WHERE c.id IS NOT NULL
    GROUP BY u.id, u.nom, u.prenom, u.email, u.entreprise
    ORDER BY derniere_commande DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$clients = $stmt->fetchAll();

// Statuts avec configuration
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
    <title>Tableau de bord Conseiller - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <!-- Sidebar Conseiller -->
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
            <a href="clients.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Mes clients</span>
                <?php if ($stats['nb_clients'] > 0): ?>
                    <span class="badge"><?php echo $stats['nb_clients']; ?></span>
                <?php endif; ?>
            </a>
            <a href="commandes.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Commandes</span>
                <?php if ($stats['en_cours'] > 0): ?>
                    <span class="badge"><?php echo $stats['en_cours']; ?></span>
                <?php endif; ?>
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($messages_non_lus > 0): ?>
                    <span class="badge"><?php echo $messages_non_lus; ?></span>
                <?php endif; ?>
            </a>
            <a href="statistiques.php" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Statistiques</span>
            </a>
            <a href="profil.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Mon profil</span>
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
                <h1>Tableau de bord Conseiller</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="user-info">
                        <strong><?php echo escape($_SESSION['nom_complet']); ?></strong>
                        <span>Conseiller</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h2>Bonjour <?php echo escape($_SESSION['nom_complet']); ?> 👋</h2>
                <p>Voici un aperçu de votre activité</p>
            </div>
            <div class="quick-stats">
                <div class="quick-stat-item">
                    <i class="fas fa-users"></i>
                    <strong><?php echo $stats['nb_clients'] ?? 0; ?></strong>
                    <span>Clients</span>
                </div>
                <div class="quick-stat-item">
                    <i class="fas fa-clock"></i>
                    <strong><?php echo $stats['en_cours'] ?? 0; ?></strong>
                    <span>En cours</span>
                </div>
                <div class="quick-stat-item">
                    <i class="fas fa-star"></i>
                    <strong><?php echo number_format($stats['note_moyenne'] ?? 0, 1); ?></strong>
                    <span>Note moyenne</span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_commandes'] ?? 0; ?></h3>
                    <p>Commandes totales</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['en_attente'] ?? 0; ?></h3>
                    <p>En attente</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['terminees'] ?? 0; ?></h3>
                    <p>Terminées</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['nb_clients'] ?? 0; ?></h3>
                    <p>Clients assignés</p>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Commandes récentes -->
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
                    <?php if (count($commandes_recentes) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Service</th>
                                        <th>Statut</th>
                                        <th>Progression</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($commandes_recentes as $cmd): ?>
                                        <tr>
                                            <td><strong>#<?php echo $cmd['commande_id']; ?></strong></td>
                                            <td><?php echo escape($cmd['client_prenom'] . ' ' . $cmd['client_nom']); ?></td>
                                            <td><?php echo escape($cmd['pack_nom']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $statuts_labels[$cmd['commande_statut']]['class']; ?>">
                                                    <i class="fas fa-<?php echo $statuts_labels[$cmd['commande_statut']]['icon']; ?>"></i>
                                                    <?php echo $statuts_labels[$cmd['commande_statut']]['label']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $cmd['commande_progression']; ?>%"></div>
                                                </div>
                                                <small><?php echo $cmd['commande_progression']; ?>%</small>
                                            </td>
                                            <td><?php echo format_date($cmd['commande_date'], 'd/m/Y'); ?></td>
                                            <td>
                                                <a href="commande-detail.php?id=<?php echo $cmd['commande_id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Aucune commande assignée</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mes clients -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-users"></i>
                        Mes clients
                    </h3>
                    <a href="clients.php" class="link-arrow">
                        Voir tout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($clients) > 0): ?>
                        <div class="clients-list">
                            <?php foreach ($clients as $client): ?>
                                <div class="client-item">
                                    <div class="client-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="client-info">
                                        <strong><?php echo escape($client['client_prenom'] . ' ' . $client['client_nom']); ?></strong>
                                        <?php if ($client['client_entreprise']): ?>
                                            <span class="client-company"><?php echo escape($client['client_entreprise']); ?></span>
                                        <?php endif; ?>
                                        <div class="client-meta">
                                            <span><i class="fas fa-shopping-cart"></i> <?php echo $client['nb_commandes']; ?> commande(s)</span>
                                            <span><i class="far fa-calendar"></i> <?php echo format_date($client['derniere_commande'], 'd/m/Y'); ?></span>
                                        </div>
                                    </div>
                                    <div class="client-actions">
                                        <a href="messages.php?with=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>Aucun client assigné</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Actions rapides</h3>
            <div class="actions-grid">
                <a href="commandes.php?statut=en_attente" class="action-card">
                    <i class="fas fa-clock"></i>
                    <span>Commandes en attente</span>
                </a>
                <a href="messages.php" class="action-card">
                    <i class="fas fa-envelope"></i>
                    <span>Mes messages</span>
                </a>
                <a href="clients.php" class="action-card">
                    <i class="fas fa-users"></i>
                    <span>Gérer mes clients</span>
                </a>
                <a href="statistiques.php" class="action-card">
                    <i class="fas fa-chart-bar"></i>
                    <span>Voir statistiques</span>
                </a>
            </div>
        </div>
    </div>

    <style>
        .clients-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .client-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .client-item:hover {
            background: var(--gray-100);
        }

        .client-avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .client-info {
            flex: 1;
        }

        .client-info strong {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--gray-900);
        }

        .client-company {
            display: block;
            font-size: 0.9rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .client-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .client-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .client-actions {
            display: flex;
            gap: 0.5rem;
        }
    </style>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>y