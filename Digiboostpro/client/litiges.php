<?php
// DigiboostPro v1 - Gestion des litiges client
require_once '../config/config.php';

if (!is_logged_in() || !check_role('client')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Créer un nouveau litige
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_litige'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $commande_id = intval($_POST['commande_id'] ?? 0);
        $type = trim($_POST['type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($type) || empty($description)) {
            $error = 'Tous les champs sont requis';
        } elseif ($commande_id <= 0) {
            $error = 'Commande invalide';
        } else {
            // Vérifier que la commande appartient bien au client
            $stmt = $pdo->prepare("SELECT id FROM commandes WHERE id = ? AND client_id = ?");
            $stmt->execute([$commande_id, $user_id]);
            if (!$stmt->fetch()) {
                $error = 'Commande non trouvée';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO litiges (commande_id, client_id, type, description, statut, priorite)
                        VALUES (?, ?, ?, ?, 'ouvert', 'moyenne')
                    ");
                    $stmt->execute([$commande_id, $user_id, $type, $description]);
                    
                    $litige_id = $pdo->lastInsertId();
                    
                    // Notifier les admins
                    $stmt_admins = $pdo->query("
                        SELECT u.id FROM users u 
                        JOIN roles r ON u.role_id = r.id 
                        WHERE r.nom = 'admin'
                    ");
                    while ($admin = $stmt_admins->fetch()) {
                        create_notification(
                            $admin['id'],
                            'litige',
                            'Nouveau litige',
                            "Un client a signalé un problème sur la commande #$commande_id",
                            '/admin/litiges.php?id=' . $litige_id
                        );
                    }
                    
                    log_activity($user_id, 'Litige créé', "Litige #$litige_id sur commande #$commande_id");
                    $success = 'Votre litige a été créé. Un administrateur le prendra en charge rapidement.';
                    
                } catch (PDOException $e) {
                    $error = 'Erreur lors de la création du litige';
                }
            }
        }
    }
}

// Récupérer les commandes du client pour le formulaire
$stmt = $pdo->prepare("
    SELECT c.id, p.nom as pack_nom, c.created_at
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    WHERE c.client_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll();

// Récupérer les litiges du client
$stmt = $pdo->prepare("
    SELECT l.*, 
           c.id as commande_id,
           p.nom as pack_nom,
           u_res.nom as resolveur_nom, u_res.prenom as resolveur_prenom
    FROM litiges l
    JOIN commandes c ON l.commande_id = c.id
    JOIN packs p ON c.pack_id = p.id
    LEFT JOIN users u_res ON l.resolu_par = u_res.id
    WHERE l.client_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$user_id]);
$litiges = $stmt->fetchAll();

$statuts = [
    'ouvert' => ['label' => 'Ouvert', 'class' => 'danger', 'icon' => 'exclamation-circle'],
    'en_cours' => ['label' => 'En cours', 'class' => 'warning', 'icon' => 'sync'],
    'résolu' => ['label' => 'Résolu', 'class' => 'success', 'icon' => 'check-circle'],
    'fermé' => ['label' => 'Fermé', 'class' => 'secondary', 'icon' => 'times-circle']
];

$types_litiges = [
    'qualité' => 'Problème de qualité',
    'délai' => 'Retard de livraison',
    'communication' => 'Problème de communication',
    'remboursement' => 'Demande de remboursement',
    'autre' => 'Autre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Litiges - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets<link rel="stylesheet href="../assets/css/style.css">
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
                <h1>Gestion des litiges</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="showNewLitigeModal()">
                    <i class="fas fa-plus"></i>
                    Nouveau litige
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

            <!-- Info box -->
            <div class="info-box">
                <div class="info-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-content">
                    <h4>Besoin d'aide ?</h4>
                    <p>Si vous rencontrez un problème avec votre commande, n'hésitez pas à créer un litige. Notre équipe traitera votre demande dans les plus brefs délais.</p>
                </div>
            </div>

            <!-- Liste des litiges -->
            <?php if (count($litiges) > 0): ?>
                <div class="litiges-grid">
                    <?php foreach ($litiges as $litige): ?>
                        <div class="litige-card">
                            <div class="litige-header">
                                <div class="litige-id">
                                    <i class="fas fa-hashtag"></i>
                                    <?php echo $litige['id']; ?>
                                </div>
                                <span class="badge badge-<?php echo $statuts[$litige['statut']]['class']; ?>">
                                    <i class="fas fa-<?php echo $statuts[$litige['statut']]['icon']; ?>"></i>
                                    <?php echo $statuts[$litige['statut']]['label']; ?>
                                </span>
                            </div>

                            <div class="litige-body">
                                <div class="litige-type">
                                    <i class="fas fa-tag"></i>
                                    <strong><?php echo $types_litiges[$litige['type']]; ?></strong>
                                </div>

                                <div class="litige-commande">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Commande #<?php echo $litige['commande_id']; ?> - <?php echo escape($litige['pack_nom']); ?></span>
                                </div>

                                <div class="litige-description">
                                    <?php echo nl2br(escape($litige['description'])); ?>
                                </div>

                                <?php if ($litige['reponse_admin']): ?>
                                    <div class="litige-response">
                                        <div class="response-header">
                                            <i class="fas fa-reply"></i>
                                            <strong>Réponse de l'équipe</strong>
                                            <?php if ($litige['resolveur_nom']): ?>
                                                <span class="response-author">
                                                    par <?php echo escape($litige['resolveur_prenom'] . ' ' . $litige['resolveur_nom']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="response-content">
                                            <?php echo nl2br(escape($litige['reponse_admin'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="litige-footer">
                                <div class="litige-dates">
                                    <span class="date-item">
                                        <i class="far fa-calendar"></i>
                                        Créé le <?php echo format_date($litige['created_at'], 'd/m/Y H:i'); ?>
                                    </span>
                                    <?php if ($litige['date_resolution']): ?>
                                        <span class="date-item">
                                            <i class="fas fa-check"></i>
                                            Résolu le <?php echo format_date($litige['date_resolution'], 'd/m/Y H:i'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Aucun litige</h3>
                    <p>Vous n'avez aucun litige en cours. Si vous rencontrez un problème, n'hésitez pas à nous le signaler.</p>
                    <button class="btn btn-primary btn-lg" onclick="showNewLitigeModal()">
                        <i class="fas fa-plus"></i>
                        Créer un litige
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal nouveau litige -->
    <div class="modal" id="newLitigeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Créer un litige</h3>
                <button class="modal-close" onclick="hideLitigeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="create_litige" value="1">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="commande_id">
                            <i class="fas fa-shopping-cart"></i>
                            Commande concernée *
                        </label>
                        <select name="commande_id" id="commande_id" class="form-control" required>
                            <option value="">Sélectionnez une commande</option>
                            <?php foreach ($commandes as $cmd): ?>
                                <option value="<?php echo $cmd['id']; ?>">
                                    #<?php echo $cmd['id']; ?> - <?php echo escape($cmd['pack_nom']); ?> 
                                    (<?php echo format_date($cmd['created_at'], 'd/m/Y'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type">
                            <i class="fas fa-tag"></i>
                            Type de problème *
                        </label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="">Sélectionnez un type</option>
                            <?php foreach ($types_litiges as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-align-left"></i>
                            Description détaillée *
                        </label>
                        <textarea 
                            name="description" 
                            id="description" 
                            class="form-control" 
                            rows="6"
                            placeholder="Décrivez votre problème en détail..."
                            required
                        ></textarea>
                        <small class="help-text">
                            <i class="fas fa-info-circle"></i>
                            Plus votre description est précise, plus nous pourrons vous aider rapidement
                        </small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideLitigeModal()">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Créer le litige
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .info-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .info-content h4 {
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .info-content p {
            color: var(--gray-700);
            margin: 0;
            line-height: 1.6;
        }

        .litiges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
        }

        .litige-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .litige-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .litige-header {
            padding: 1.5rem;
            background: var(--gray-50);
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .litige-id {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .litige-body {
            padding: 1.5rem;
        }

        .litige-type,
        .litige-commande {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--gray-700);
        }

        .litige-type i,
        .litige-commande i {
            color: var(--primary);
            width: 20px;
        }

        .litige-description {
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            margin: 1.5rem 0;
            line-height: 1.6;
            color: var(--gray-700);
        }

        .litige-response {
            margin-top: 1.5rem;
            padding: 1rem;
            background: linear-gradient(135deg, #d1fae515 0%, #a7f3d015 100%);
            border-left: 3px solid var(--success);
            border-radius: var(--radius);
        }

        .response-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: var(--success);
            font-weight: 600;
        }

        .response-author {
            margin-left: auto;
            font-size: 0.85rem;
            color: var(--gray-600);
            font-weight: 400;
        }

        .response-content {
            color: var(--gray-700);
            line-height: 1.6;
        }

        .litige-footer {
            padding: 1.5rem;
            background: var(--gray-50);
            border-top: 2px solid var(--gray-200);
        }

        .litige-dates {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .date-item i {
            color: var(--gray-500);
        }

        @media (max-width: 768px) {
            .litiges-grid {
                grid-template-columns: 1fr;
            }

            .info-box {
                flex-direction: column;
            }
        }
    </style>

    <script>
        function showNewLitigeModal() {
            document.getElementById('newLitigeModal').classList.add('show');
        }

        function hideLitigeModal() {
            document.getElementById('newLitigeModal').classList.remove('show');
        }

        // Fermer modal en cliquant à l'extérieur
        document.getElementById('newLitigeModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideLitigeModal();
            }
        });
    </script>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>