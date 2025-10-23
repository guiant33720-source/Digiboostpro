<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'conseiller') {
    header('Location: ../login.php');
    exit();
}

$conseiller_id = $_SESSION['user_id'];

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$statut_filter = $_GET['statut'] ?? '';
$order = $_GET['order'] ?? 'nom';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$where_conditions = ["c.conseiller_id = ?"];
$params = [$conseiller_id];

if (!empty($search)) {
    $where_conditions[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($statut_filter)) {
    $where_conditions[] = "c.statut = ?";
    $params[] = $statut_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Ordre de tri
$order_clause = match($order) {
    'nom' => 'c.nom ASC, c.prenom ASC',
    'date' => 'c.date_inscription DESC',
    'commandes' => 'nb_commandes DESC',
    'ca' => 'total_ca DESC',
    default => 'c.nom ASC'
};

// Compter le total
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM users c
    WHERE $where_clause
");
$count_stmt->execute($params);
$total_clients = $count_stmt->fetch()['total'];
$total_pages = ceil($total_clients / $per_page);

// Récupérer les clients avec statistiques
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare("
    SELECT c.*,
           COUNT(DISTINCT co.id) as nb_commandes,
           COALESCE(SUM(CASE WHEN co.statut = 'terminee' THEN co.total ELSE 0 END), 0) as total_ca,
           MAX(co.date_commande) as derniere_commande
    FROM users c
    LEFT JOIN commandes co ON c.id = co.client_id
    WHERE $where_clause
    GROUP BY c.id
    ORDER BY $order_clause
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques globales
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT c.id) as total_clients,
        COUNT(DISTINCT co.id) as total_commandes,
        COALESCE(SUM(CASE WHEN co.statut = 'terminee' THEN co.total ELSE 0 END), 0) as ca_total
    FROM users c
    LEFT JOIN commandes co ON c.id = co.client_id
    WHERE c.conseiller_id = ?
");
$stmt->execute([$conseiller_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Clients - Digiboostpro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header-conseiller.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/sidebar-conseiller.php'; ?>
        
        <main class="dashboard-main">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Mes Clients</h1>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_clients'] ?></h3>
                        <p>Clients actifs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_commandes'] ?></h3>
                        <p>Commandes totales</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['ca_total'], 2) ?>€</h3>
                        <p>Chiffre d'affaires</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_clients'] > 0 ? number_format($stats['ca_total'] / $stats['total_clients'], 2) : 0 ?>€</h3>
                        <p>CA moyen/client</p>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="card">
                <form method="GET" class="filters-form">
                    <div class="filters-row">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Rechercher un client..." 
                                   class="form-input" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="filter-group">
                            <select name="statut" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actif</option>
                                <option value="inactif" <?= $statut_filter === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                <option value="suspendu" <?= $statut_filter === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="order" class="form-select">
                                <option value="nom" <?= $order === 'nom' ? 'selected' : '' ?>>Nom A-Z</option>
                                <option value="date" <?= $order === 'date' ? 'selected' : '' ?>>Plus récent</option>
                                <option value="commandes" <?= $order === 'commandes' ? 'selected' : '' ?>>Nb commandes</option>
                                <option value="ca" <?= $order === 'ca' ? 'selected' : '' ?>>CA décroissant</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <a href="clients.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Liste des clients -->
            <div class="card">
                <div class="card-header">
                    <h2>Liste des Clients (<?= $total_clients ?>)</h2>
                    <div class="card-actions">
                        <button class="btn btn-success" onclick="exportCSV()">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Inscription</th>
                                <th>Commandes</th>
                                <th>CA Total</th>
                                <th>Dernière commande</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <?php if (!empty($client['photo'])): ?>
                                                <img src="../uploads/profiles/<?= htmlspecialchars($client['photo']) ?>" 
                                                     alt="Photo" class="user-avatar">
                                            <?php else: ?>
                                                <div class="user-avatar-placeholder">
                                                    <?= strtoupper(substr($client['prenom'], 0, 1) . substr($client['nom'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($client['email']) ?></td>
                                    <td><?= htmlspecialchars($client['telephone'] ?? '-') ?></td>
                                    <td><?= date('d/m/Y', strtotime($client['date_inscription'])) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $client['nb_commandes'] ?></span>
                                    </td>
                                    <td><strong><?= number_format($client['total_ca'], 2) ?>€</strong></td>
                                    <td>
                                        <?= $client['derniere_commande'] ? date('d/m/Y', strtotime($client['derniere_commande'])) : 'Aucune' ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $client['statut'] ?>">
                                            <?= ucfirst($client['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="client-detail.php?id=<?= $client['id'] ?>" 
                                               class="btn btn-sm btn-primary" title="Voir le profil">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="messages.php?contact_id=<?= $client['id'] ?>" 
                                               class="btn btn-sm btn-info" title="Envoyer un message">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <a href="commandes.php?client_id=<?= $client['id'] ?>" 
                                               class="btn btn-sm btn-success" title="Voir les commandes">
                                                <i class="fas fa-shopping-cart"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($clients)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Aucun client trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $query_string = http_build_query($query_params);
                        $base_url = '?' . ($query_string ? $query_string . '&' : '');
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?= $base_url ?>page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?= $page ?> sur <?= $total_pages ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?= $base_url ?>page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function exportCSV() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            window.location.href = 'export-clients.php?' + params.toString();
        }
    </script>
</body>
</html>