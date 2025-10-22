<?php
// DigiboostPro v1 - Détail d'une commande
require_once '../config/config.php';

if (!is_logged_in() || !check_role('client')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];
$commande_id = intval($_GET['id'] ?? 0);
$success = $_GET['success'] ?? '';

if ($commande_id <= 0) {
    redirect('/client/commandes.php');
}

// Récupérer la commande (vérifier qu'elle appartient bien au client)
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.nom as pack_nom, p.description as pack_description, p.delai_livraison,
           u.nom as conseiller_nom, u.prenom as conseiller_prenom, u.email as conseiller_email
    FROM commandes c
    JOIN packs p ON c.pack_id = p.id
    LEFT JOIN users u ON c.conseiller_id = u.id
    WHERE c.id = ? AND c.client_id = ?
");
$stmt->execute([$commande_id, $user_id]);
$commande = $stmt->fetch();

if (!$commande) {
    redirect('/client/commandes.php');
}

// Récupérer les documents
$stmt = $pdo->prepare("
    SELECT * FROM documents 
    WHERE commande_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$commande_id]);
$documents = $stmt->fetchAll();

// Récupérer les messages liés à cette commande
$stmt = $pdo->prepare("
    SELECT m.*, 
           u.nom as expediteur_nom, u.prenom as expediteur_prenom
    FROM messages m
    JOIN users u ON m.expediteur_id = u.id
    WHERE m.commande_id = ? 
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt->execute([$commande_id]);
$messages = $stmt->fetchAll();

// Vérifier si en favoris
$stmt = $pdo->prepare("SELECT id FROM favoris WHERE user_id = ? AND commande_id = ?");
$stmt->execute([$user_id, $commande_id]);
$is_favorite = $stmt->fetch() ? true : false;

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
    <title>Commande #<?php echo $commande['id']; ?> - <?php echo SITE_NAME; ?></title>
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
                <div>
                    <a href="commandes.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Retour aux commandes
                    </a>
                    <h1>Commande #<?php echo $commande['id']; ?></h1>
                </div>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="toggleFavorite(<?php echo $commande_id; ?>)">
                    <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-star" id="favoriteIcon"></i>
                    <span id="favoriteText"><?php echo $is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?></span>
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
            <?php if ($success === '1'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Votre commande a été créée avec succès ! Un conseiller vous sera assigné prochainement.
                </div>
            <?php endif; ?>

            <div class="detail-grid">
                <!-- Informations principales -->
                <div class="detail-main">
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-info-circle"></i>
                                Informations générales
                            </h3>
                            <span class="badge badge-<?php echo $statuts_labels[$commande['statut']]['class']; ?> badge-lg">
                                <i class="fas fa-<?php echo $statuts_labels[$commande['statut']]['icon']; ?>"></i>
                                <?php echo $statuts_labels[$commande['statut']]['label']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Service :</span>
                                    <strong><?php echo escape($commande['pack_nom']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Montant :</span>
                                    <strong class="price"><?php echo format_price($commande['prix_total']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Date de commande :</span>
                                    <span><?php echo format_date($commande['created_at'], 'd/m/Y H:i'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Livraison estimée :</span>
                                    <span><?php echo format_date($commande['date_livraison_estimee'], 'd/m/Y'); ?></span>
                                </div>
                                <?php if ($commande['date_livraison_reelle']): ?>
                                <div class="info-item">
                                    <span class="info-label">Livrée le :</span>
                                    <span><?php echo format_date($commande['date_livraison_reelle'], 'd/m/Y'); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <span class="info-label">URL du site :</span>
                                    <a href="<?php echo escape($commande['url_site']); ?>" target="_blank" class="link">
                                        <?php echo escape($commande['url_site']); ?>
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="brief-section">
                                <h4><i class="fas fa-file-alt"></i> Brief du projet</h4>
                                <div class="brief-content">
                                    <?php echo nl2br(escape($commande['brief'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progression -->
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-tasks"></i>
                                Progression du projet
                            </h3>
                            <span class="progress-percentage"><?php echo $commande['progression']; ?>%</span>
                        </div>
                        <div class="card-body">
                            <div class="progress-bar-large">
                                <div class="progress-fill" style="width: <?php echo $commande['progression']; ?>%"></div>
                            </div>

                            <div class="timeline">
                                <div class="timeline-item <?php echo $commande['progression'] >= 0 ? 'completed' : ''; ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5>Commande reçue</h5>
                                        <p><?php echo format_date($commande['created_at'], 'd/m/Y H:i'); ?></p>
                                    </div>
                                </div>
                                <div class="timeline-item <?php echo in_array($commande['statut'], ['en_cours', 'en_revision', 'terminé']) ? 'completed' : ''; ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5>Analyse en cours</h5>
                                        <p>Votre projet est en cours de traitement</p>
                                    </div>
                                </div>
                                <div class="timeline-item <?php echo $commande['statut'] === 'en_revision' || $commande['statut'] === 'terminé' ? 'completed' : ''; ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5>En révision</h5>
                                        <p>Vérification qualité et finalisation</p>
                                    </div>
                                </div>
                                <div class="timeline-item <?php echo $commande['statut'] === 'terminé' ? 'completed' : ''; ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5>Livraison</h5>
                                        <p>Votre projet est prêt</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-file"></i>
                                Documents (<?php echo count($documents); ?>)
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($documents) > 0): ?>
                                <div class="documents-list">
                                    <?php foreach ($documents as $doc): ?>
                                        <div class="document-item">
                                            <div class="document-icon">
                                                <i class="fas fa-file-<?php echo in_array($doc['type_fichier'], ['pdf']) ? 'pdf' : (in_array($doc['type_fichier'], ['jpg', 'jpeg', 'png']) ? 'image' : 'alt'); ?>"></i>
                                            </div>
                                            <div class="document-info">
                                                <strong><?php echo escape($doc['nom_fichier']); ?></strong>
                                                <span><?php echo round($doc['taille_fichier'] / 1024, 2); ?> KB - <?php echo format_date($doc['created_at'], 'd/m/Y'); ?></span>
                                            </div>
                                            <a href="../uploads/documents/<?php echo escape($doc['chemin_fichier']); ?>" class="btn btn-sm btn-secondary" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-folder-open"></i>
                                    <p>Aucun document pour le moment</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="detail-sidebar">
                    <!-- Conseiller -->
                    <?php if ($commande['conseiller_nom']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-user-tie"></i>
                                Votre conseiller
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="conseiller-card">
                                <div class="conseiller-avatar">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="conseiller-info">
                                    <strong><?php echo escape($commande['conseiller_prenom'] . ' ' . $commande['conseiller_nom']); ?></strong>
                                    <span><?php echo escape($commande['conseiller_email']); ?></span>
                                </div>
                                <a href="messages.php?to=<?php echo $commande['conseiller_id']; ?>" class="btn btn-primary btn-block">
                                    <i class="fas fa-envelope"></i>
                                    Contacter
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state-small">
                                <i class="fas fa-user-clock"></i>
                                <p>Un conseiller vous sera assigné prochainement</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions rapides -->
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-bolt"></i>
                                Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="actions-list">
                                <a href="messages.php?commande=<?php echo $commande_id; ?>" class="action-btn">
                                    <i class="fas fa-comments"></i>
                                    <span>Messages</span>
                                </a>
                                <?php if ($commande['statut'] === 'terminé'): ?>
                                <a href="#" onclick="showFeedbackModal()" class="action-btn">
                                    <i class="fas fa-star"></i>
                                    <span>Donner un avis</span>
                                </a>
                                <?php endif; ?>
                                <a href="litiges.php?commande=<?php echo $commande_id; ?>" class="action-btn">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Signaler un problème</span>
                                </a>
                                <a href="#" onclick="window.print()" class="action-btn">
                                    <i class="fas fa-print"></i>
                                    <span>Imprimer</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Messages récents -->
                    <?php if (count($messages) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-comments"></i>
                                Messages récents
                            </h3>
                            <a href="messages.php?commande=<?php echo $commande_id; ?>" class="link-arrow">
                                Voir tout
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="messages-preview">
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message-preview-item">
                                        <div class="message-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="message-content">
                                            <strong><?php echo escape($msg['expediteur_prenom'] . ' ' . $msg['expediteur_nom']); ?></strong>
                                            <p><?php echo escape(substr($msg['message'], 0, 60)); ?>...</p>
                                            <small><?php echo format_date($msg['created_at'], 'd/m/Y H:i'); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .badge-lg {
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .price {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .brief-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--gray-200);
        }

        .brief-section h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .brief-content {
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            line-height: 1.7;
            color: var(--gray-700);
        }

        .progress-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-bar-large {
            height: 20px;
            background: var(--gray-200);
            border-radius: 1rem;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .timeline {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            position: relative;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 30px;
            bottom: -20px;
            width: 2px;
            background: var(--gray-300);
        }

        .timeline-item.completed::before {
            background: var(--success);
        }

        .timeline-marker {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid var(--gray-300);
            background: var(--white);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .timeline-item.completed .timeline-marker {
            border-color: var(--success);
            background: var(--success);
        }

        .timeline-content h5 {
            margin-bottom: 0.25rem;
            color: var(--gray-900);
        }

        .timeline-content p {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .documents-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
        }

        .document-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
        }

        .document-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .document-info span {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .conseiller-card {
            text-align: center;
        }

        .conseiller-avatar {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }

        .conseiller-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .conseiller-info strong {
            font-size: 1.1rem;
        }

        .conseiller-info span {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .actions-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .action-btn:hover {
            background: var(--primary);
            color: var(--white);
        }

        .action-btn i {
            font-size: 1.25rem;
        }

        .messages-preview {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message-preview-item {
            display: flex;
            gap: 0.75rem;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
        }

        .message-content strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .message-content p {
            color: var(--gray-700);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .message-content small {
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .empty-state-small {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--gray-500);
        }

        .empty-state-small i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 1024px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function toggleFavorite(commandeId) {
            // Appel AJAX pour ajouter/retirer des favoris
            fetch('ajax/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ commande_id: commandeId })
            })
            .then(response => response.json())
            .then(data => {
                const icon = document.getElementById('favoriteIcon');
                const text = document.getElementById('favoriteText');
                
                if (data.success) {
                    if (data.is_favorite) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        text.textContent = 'Retirer des favoris';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        text.textContent = 'Ajouter aux favoris';
                    }
                    showNotification(data.message, 'success');
                }
            });
        }

        function showFeedbackModal() {
            alert('Modal de feedback à implémenter');
        }
    </script>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>