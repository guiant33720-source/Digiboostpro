<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'conseiller') {
    header('Location: ../login.php');
    exit();
}

$conseiller_id = $_SESSION['user_id'];

// Paramètres de filtrage
$statut_filter = $_GET['statut'] ?? '';
$client_filter = $_GET['client_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search = $_GET['search'] ?? '';
$order = $_GET['order'] ?? 'date_desc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$where_conditions = ["u.conseiller_id = ?"];
$params = [$conseiller_id];

if (!empty($statut_filter)) {
    $where_conditions[] = "c.statut = ?";
    $params[] = $statut_filter;
}

if (!empty($client_filter)) {
    $where_conditions[] = "c.client_id = ?";
    $params[] = $client_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(c.numero_commande LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_debut)) {
    $where_conditions[] = "c.date_commande >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where_conditions[] = "c.date_commande <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

$where_clause = implode(' AND ', $where_conditions);

// Ordre de tri
$order_clause = match($order) {
    'date_desc' => 'c.date_commande DESC',
    'date_asc' => 'c.date_commande ASC',
    'montant_desc' => 'c.total DESC',
    'montant_asc' => 'c.total ASC',
    'numero' => 'c.numero_commande ASC',
    default => 'c.date_commande DESC'
};

// Compter le total
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM commandes c
    JOIN users u ON c.client_id = u.id
    WHERE $where_clause
");
$count_stmt->execute($params);
$total_commandes = $count_stmt->fetch()['total'];
$total_pages = ceil($total_commandes / $per_page);

// Récupérer les commandes
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare("
    SELECT c.*, 
           u.nom, u.prenom, u.email,
           COUNT(cp.id) as nb_produits
    FROM commandes c
    JOIN users u ON c.client_id = u.id
    LEFT JOIN commande_produits cp ON c.id = cp.commande_id
    WHERE $where_clause
    GROUP BY c.id
    ORDER BY $order_clause
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_commandes,
        COALESCE(SUM(CASE WHEN c.statut = 'en_attente' THEN 1 ELSE 0 END), 0) as en_attente,
        COALESCE(SUM(CASE WHEN c.statut = 'en_cours' THEN 1 ELSE 0 END), 0) as en_cours,
        COALESCE(SUM(CASE WHEN c.statut = 'terminee' THEN 1 ELSE 0 END), 0) as terminees,
        COALESCE(SUM(c.total), 0) as ca_total,
        COALESCE(SUM(CASE WHEN c.statut = 'terminee' THEN c.total ELSE 0 END), 0) as ca_termine
    FROM commandes c
    JOIN users u ON c.client_id = u.id
    WHERE u.conseiller_id = ?
");
$stmt->execute([$conseiller_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Liste des clients pour le filtre
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nom, u.prenom 
    FROM users u
    JOIN commandes c ON u.id = c.client_id
    WHERE u.conseiller_id = ?
    ORDER BY u.nom ASC
");
$stmt->execute([$conseiller_id]);
$clients_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Digiboostpro</title>
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
                <h1><i class="fas fa-shopping-cart"></i> Gestion des Commandes</h1>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_commandes'] ?></h3>
                        <p>Total commandes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['en_attente'] ?></h3>
                        <p>En attente</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['en_cours'] ?></h3>
                        <p>En cours</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['terminees'] ?></h3>
                        <p>Terminées</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['ca_total'], 2) ?>€</h3>
                        <p>CA Total</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['ca_termine'], 2) ?>€</h3>
                        <p>CA Terminé</p>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card">
                <form method="GET" class="filters-form">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label>Recherche</label>
                            <input type="text" name="search" placeholder="N° commande, client..." 
                                   class="form-input" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Statut</label>
                            <select name="statut" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?= $statut_filter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="en_cours" <?= $statut_filter === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="terminee" <?= $statut_filter === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                                <option value="annulee" <?= $statut_filter === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Client</label>
                            <select name="client_id" class="form-select">
                                <option value="">Tous les clients</option>
                                <?php foreach ($clients_list as $cl): ?>
                                    <option value="<?= $cl['id'] ?>" <?= $client_filter == $cl['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cl['prenom'] . ' ' . $cl['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date début</label>
                            <input type="date" name="date_debut" class="form-input" value="<?= htmlspecialchars($date_debut) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Date fin</label>
                            <input type="date" name="date_fin" class="form-input" value="<?= htmlspecialchars($date_fin) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Trier par</label>
                            <select name="order" class="form-select">
                                <option value="date_desc" <?= $order === 'date_desc' ? 'selected' : '' ?>>Plus récent</option>
                                <option value="date_asc" <?= $order === 'date_asc' ? 'selected' : '' ?>>Plus ancien</option>
                                <option value="montant_desc" <?= $order === 'montant_desc' ? 'selected' : '' ?>>Montant décroissant</option>
                                <option value="montant_asc" <?= $order === 'montant_asc' ? 'selected' : '' ?>>Montant croissant</option>
                                <option value="numero" <?= $order === 'numero' ? 'selected' : '' ?>>N° commande</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                        <a href="commandes.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportData()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des commandes -->
            <div class="card">
                <div class="card-header">
                    <h2>Liste des Commandes (<?= $total_commandes ?>)</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Produits</th>
                                <th>Montant HT</th>
                                <th>TVA</th>
                                <th>Montant TTC</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $cmd): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($cmd['numero_commande']) ?></strong>
                                    </td>
                                    <td>
                                        <a href="client-detail.php?id=<?= $cmd['client_id'] ?>">
                                            <?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?>
                                        </a>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $cmd['nb_produits'] ?></span>
                                    </td>
                                    <td><?= number_format($cmd['total_ht'], 2) ?>€</td>
                                    <td><?= number_format($cmd['tva'], 2) ?>€</td>
                                    <td><strong><?= number_format($cmd['total'], 2) ?>€</strong></td>
                                    <td>
                                        <span class="badge badge-<?= $cmd['statut'] ?>">
                                            <?php
                                            echo match($cmd['statut']) {
                                                'en_attente' => 'En attente',
                                                'en_cours' => 'En cours',
                                                'terminee' => 'Terminée',
                                                'annulee' => 'Annulée',
                                                default => ucfirst($cmd['statut'])
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $cmd['statut_paiement'] ?>">
                                            <?php
                                            echo match($cmd['statut_paiement']) {
                                                'en_attente' => 'En attente',
                                                'paye' => 'Payé',
                                                'rembourse' => 'Remboursé',
                                                'echoue' => 'Échoué',
                                                default => ucfirst($cmd['statut_paiement'])
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="commande-detail.php?id=<?= $cmd['id'] ?>" 
                                               class="btn btn-sm btn-primary" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="generateInvoice(<?= $cmd['id'] ?>)" 
                                                    title="Facture PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <?php if ($cmd['statut'] !== 'terminee' && $cmd['statut'] !== 'annulee'): ?>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="updateStatus(<?= $cmd['id'] ?>)" 
                                                        title="Modifier statut">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($commandes)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">Aucune commande trouvée</td>
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
                            <a href="<?= $base_url ?>page=1" class="btn btn-sm btn-secondary">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="<?= $base_url ?>page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?= $page ?> sur <?= $total_pages ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?= $base_url ?>page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <a href="<?= $base_url ?>page=<?= $total_pages ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'excel');
            window.location.href = 'export-commandes.php?' + params.toString();
        }

        function generateInvoice(commandeId) {
            window.open('generate-invoice.php?id=' + commandeId, '_blank');
        }

        function updateStatus(commandeId) {
            window.location.href = 'commande-detail.php?id=' + commandeId + '#update-status';
        }
    </script>
</body>
</html>