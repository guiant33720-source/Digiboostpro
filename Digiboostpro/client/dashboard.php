<?php
// DigiboostPro v1 - Tableau de bord client
require_once '../config/config.php';

// Vérifier l'authentification et le rôle
if (!is_logged_in() || !check_role('client')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer les statistiques du client
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_commandes,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'terminé' THEN 1 ELSE 0 END) as terminees,
        SUM(prix_total) as montant_total
    FROM commandes
    WHERE client_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Récupérer les dernières commandes
$stmt = $pdo->prepare("
    SELECT c.*, p.nom as pack_nom, u.nom as conseiller_nom, u.prenom as conseiller_prenom
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    LEFT JOIN users u ON c.conseiller_id = u.id
    WHERE c.client_id = ?
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll();

// Récupérer les notifications non lues
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ? AND lu = FALSE
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Récupérer les messages non lus
$stmt = $pdo->prepare("
    SELECT COUNT(*) as non_lus
    FROM messages
    WHERE destinataire_id = ? AND lu = FALSE
");
$stmt->execute([$user_id]);
$messages_non_lus = $stmt->fetch()['non_lus'];

// Récupérer les actualités récentes
$stmt = $pdo->query("
    SELECT * FROM actualites
    WHERE statut = 'publié'
    ORDER BY created_at DESC
    LIMIT 3
");
$actualites = $stmt->fetchAll();

// Statuts traduits
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
    <title>Tableau de bord - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <!-- Sidebar -->
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
            <a href="commandes.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Mes commandes</span>
                <?php if ($stats['en_cours'] > 0): ?>
                    <span class="badge"><?php echo $stats['en_cours']; ?></span>
                <?php endif; ?>
            </a>
            <a href="nouvelle-commande.php" class="menu-item">
                <i class="fas fa-plus-circle"></i>
                <span>Nouvelle commande</span>
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($messages_non_lus > 0): ?>
                    <span class="badge"><?php echo $messages_non_lus; ?></span>
                <?php endif; ?>
            </a>
            <a href="litiges.php" class="menu-item">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Litiges</span>
            </a>
            <a href="favoris.php" class="menu-item">
                <i class="fas fa-star"></i>
                <span>Favoris</span>
            </a>
            <a href="actualites.php" class="menu-item">
                <i class="fas fa-newspaper"></i>
                <span>Actualités</span>
            </a>
            <a href="profil.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Mon profil</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="aide.php" class="menu-item">
                <i class="fas fa-question-circle"></i>
                <span>Aide</span>
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
                <h1>Tableau de bord</h1>
            </div>
            <div class="header-right">
                <button class="notification-btn" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <?php if (count($notifications) > 0): ?>
                        <span class="notification-badge"><?php echo count($notifications); ?></span>
                    <?php endif; ?>
                </button>
                
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

        <!-- Notifications Dropdown -->
        <div class="notifications-dropdown" id="notificationsDropdown">
            <div class="notifications-header">
                <h3>Notifications</h3>
                <a href="notifications.php">Voir tout</a>
            </div>
            <div class="notifications-list">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <a href="<?php echo $notif['lien'] ?? '#'; ?>" class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="notification-content">
                                <strong><?php echo escape($notif['titre']); ?></strong>
                                <p><?php echo escape($notif['message']); ?></p>
                                <small><?php echo format_date($notif['created_at']); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>Aucune notification</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h2>Bonjour <?php echo escape($_SESSION['nom_complet']); ?> 👋</h2>
                <p>Bienvenue sur votre espace client DigiboostPro</p>
            </div>
            <a href="nouvelle-commande.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nouvelle commande
            </a>
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
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['en_cours'] ?? 0; ?></h3>
                    <p>En cours</p>
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
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo format_price($stats['montant_total'] ?? 0); ?></h3>
                    <p>Montant total</p>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">
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
                    <?php if (count($commandes) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service</th>
                                        <th>Statut</th>
                                        <th>Progression</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($commandes as $cmd): ?>
                                        <tr>
                                            <td>#<?php echo $cmd['id']; ?></td>
                                            <td>
                                                <strong><?php echo escape($cmd['pack_nom']); ?></strong>
                                                <?php if ($cmd['url_site']): ?>
                                                    <br><small class="text-muted"><?php echo escape($cmd['url_site']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $statuts_labels[$cmd['statut']]['class']; ?>">
                                                    <?php echo $statuts_labels[$cmd['statut']]['label']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $cmd['progression']; ?>%"></div>
                                                </div>
                                                <small><?php echo $cmd['progression']; ?>%</small>
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
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>Aucune commande</h4>
                            <p>Commencez dès maintenant avec notre audit SEO</p>
                            <a href="nouvelle-commande.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Nouvelle commande
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Latest News -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-newspaper"></i>
                        Actualités
                    </h3>
                    <a href="actualites.php" class="link-arrow">
                        Voir tout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($actualites) > 0): ?>
                        <div class="news-list">
                            <?php foreach ($actualites as $actu): ?>
                                <a href="actualite.php?id=<?php echo $actu['id']; ?>" class="news-item">
                                    <div class="news-icon">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="news-content">
                                        <strong><?php echo escape($actu['titre']); ?></strong>
                                        <p><?php echo escape(substr($actu['contenu'], 0, 100)); ?>...</p>
                                        <small class="text-muted">
                                            <i class="far fa-calendar"></i>
                                            <?php echo format_date($actu['created_at'], 'd M Y'); ?>
                                        </small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-newspaper"></i>
                            <p>Aucune actualité</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Actions rapides</h3>
            <div class="actions-grid">
                <a href="nouvelle-commande.php" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nouvelle commande</span>
                </a>
                <a href="messages.php" class="action-card">
                    <i class="fas fa-envelope"></i>
                    <span>Contacter mon conseiller</span>
                </a>
                <a href="litiges.php" class="action-card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Signaler un problème</span>
                </a>
                <a href="aide.php" class="action-card">
                    <i class="fas fa-question-circle"></i>
                    <span>Centre d'aide</span>
                </a>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationsDropdown');
            dropdown.classList.toggle('show');
        }

        // Fermer les notifications en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-btn') && !e.target.closest('.notifications-dropdown')) {
                document.getElementById('notificationsDropdown').classList.remove('show');
            }
        });
    </script>
</body>
</html>