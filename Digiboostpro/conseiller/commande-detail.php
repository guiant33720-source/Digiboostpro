<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'conseiller') {
    header('Location: ../login.php');
    exit();
}

$conseiller_id = $_SESSION['user_id'];
$commande_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$commande_id) {
    header('Location: commandes.php');
    exit();
}

$error = '';
$success = '';

// Mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['statut'];
    $note = trim($_POST['note'] ?? '');
    
    try {
        $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
        $stmt->execute([$new_status, $commande_id]);
        
        // Ajouter une note d'historique
        if (!empty($note)) {
            $stmt = $pdo->prepare("
                INSERT INTO commande_historique (commande_id, user_id, action, note, date_action) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$commande_id, $_SESSION['user_id'], 'Changement de statut: ' . $new_status, $note]);
        }
        
        $success = "Statut mis à jour avec succès.";
    } catch (PDOException $e) {
        $error = "Erreur lors de la mise à jour du statut.";
    }
}

// Ajouter une note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    
    if (!empty($note)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO commande_historique (commande_id, user_id, action, note, date_action) 
                VALUES (?, ?, 'Note ajoutée', ?, NOW())
            ");
            $stmt->execute([$commande_id, $_SESSION['user_id'], $note]);
            $success = "Note ajoutée avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de la note.";
        }
    }
}

// Récupérer les détails de la commande
$stmt = $pdo->prepare("
    SELECT c.*, 
           u.nom, u.prenom, u.email, u.telephone, u.adresse, u.ville, u.code_postal, u.pays,
           cons.nom as conseiller_nom, cons.prenom as conseiller_prenom
    FROM commandes c
    JOIN users u ON c.client_id = u.id
    LEFT JOIN users cons ON u.conseiller_id = cons.id
    WHERE c.id = ? AND u.conseiller_id = ?
");
$stmt->execute([$commande_id, $conseiller_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header('Location: commandes.php');
    exit();
}

// Récupérer les produits de la commande
$stmt = $pdo->prepare("
    SELECT cp.*, s.nom as service_nom, s.description
    FROM commande_produits cp
    JOIN services s ON cp.service_id = s.id
    WHERE cp.commande_id = ?
");
$stmt->execute([$commande_id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer l'historique
$stmt = $pdo->prepare("
    SELECT ch.*, u.nom, u.prenom, u.role
    FROM commande_historique ch
    JOIN users u ON ch.user_id = u.id
    WHERE ch.commande_id = ?
    ORDER BY ch.date_action DESC
");
$stmt->execute([$commande_id]);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les paiements
$stmt = $pdo->prepare("
    SELECT * FROM paiements 
    WHERE commande_id = ?
    ORDER BY date_paiement DESC
");
$stmt->execute([$commande_id]);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #<?= htmlspecialchars($commande['numero_commande']) ?> - Digiboostpro</title>
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
                <div>
                    <a href="commandes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <h1><i class="fas fa-file-invoice"></i> Commande #<?= htmlspecialchars($commande['numero_commande']) ?></h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-info" onclick="generateInvoice()">
                        <i class="fas fa-file-pdf"></i> Facture PDF
                    </button>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="order-detail-grid">
                <!-- Informations principales -->
                <div class="card">
                    <div class="card-header">
                        <h2>Informations de la commande</h2>
                        <span class="badge badge-<?= $commande['statut'] ?> badge-lg">
                            <?php
                            echo match($commande['statut']) {
                                'en_attente' => 'En attente',
                                'en_cours' => 'En cours',
                                'terminee' => 'Terminée',
                                'annulee' => 'Annulée',
                                default => ucfirst($commande['statut'])
                            };
                            ?>
                        </span>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Numéro de commande</label>
                            <span><strong><?= htmlspecialchars($commande['numero_commande']) ?></strong></span>
                        </div>
                        <div class="info-item">
                            <label>Date de commande</label>
                            <span><?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Statut</label>
                            <span class="badge badge-<?= $commande['statut'] ?>">
                                <?= match($commande['statut']) {
                                    'en_attente' => 'En attente',
                                    'en_cours' => 'En cours',
                                    'terminee' => 'Terminée',
                                    'annulee' => 'Annulée',
                                    default => ucfirst($commande['statut'])
                                } ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Statut paiement</label>
                            <span class="badge badge-<?= $commande['statut_paiement'] ?>">
                                <?= match($commande['statut_paiement']) {
                                    'en_attente' => 'En attente',
                                    'paye' => 'Payé',
                                    'rembourse' => 'Remboursé',
                                    'echoue' => 'Échoué',
                                    default => ucfirst($commande['statut_paiement'])
                                } ?>
                            </span>
                        </div>
                        <?php if ($commande['date_livraison']): ?>
                            <div class="info-item">
                                <label>Date de livraison</label>
                                <span><?= date('d/m/Y', strtotime($commande['date_livraison'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <label>Mode de paiement</label>
                            <span><?= htmlspecialchars($commande['mode_paiement'] ?? 'Non renseigné') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="card">
                    <div class="card-header">
                        <h2>Informations client</h2>
                    </div>
                    
                    <div class="client-info">
                        <div class="client-header">
                            <i class="fas fa-user-circle fa-3x"></i>
                            <div>
                                <h3><?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?></h3>
                                <p><?= htmlspecialchars($commande['email']) ?></p>
                                <?php if ($commande['telephone']): ?>
                                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($commande['telephone']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($commande['adresse']): ?>
                            <div class="client-address">
                                <h4><i class="fas fa-map-marker-alt"></i> Adresse</h4>
                                <p>
                                    <?= htmlspecialchars($commande['adresse']) ?><br>
                                    <?= htmlspecialchars($commande['code_postal']) ?> <?= htmlspecialchars($commande['ville']) ?><br>
                                    <?= htmlspecialchars($commande['pays']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="client-actions">
                            <a href="client-detail.php?id=<?= $commande['client_id'] ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-eye"></i> Voir le profil
                            </a>
                            <a href="messages.php?contact_id=<?= $commande['client_id'] ?>" class="btn btn-info btn-block">
                                <i class="fas fa-envelope"></i> Envoyer un message
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Produits/Services -->
            <div class="card">
                <div class="card-header">
                    <h2>Produits et Services</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Description</th>
                                <th>Quantité</th>
                                <th>Prix unitaire HT</th>
                                <th>TVA</th>
                                <th>Total HT</th>
                                <th>Total TTC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $produit): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($produit['service_nom']) ?></strong></td>
                                    <td><?= htmlspecialchars($produit['description']) ?></td>
                                    <td><?= $produit['quantite'] ?></td>
                                    <td><?= number_format($produit['prix_unitaire'], 2) ?>€</td>
                                    <td><?= $produit['tva'] ?>%</td>
                                    <td><?= number_format($produit['prix_unitaire'] * $produit['quantite'], 2) ?>€</td>
                                    <td><?= number_format($produit['prix_unitaire'] * $produit['quantite'] * (1 + $produit['tva']/100), 2) ?>€</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"><strong>Sous-total HT</strong></td>
                                <td colspan="2"><strong><?= number_format($commande['total_ht'], 2) ?>€</strong></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-right"><strong>TVA</strong></td>
                                <td colspan="2"><strong><?= number_format($commande['tva'], 2) ?>€</strong></td>
                            </tr>
                            <?php if ($commande['remise'] > 0): ?>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Remise</strong></td>
                                    <td colspan="2"><strong>-<?= number_format($commande['remise'], 2) ?>€</strong></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="5" class="text-right"><strong>TOTAL TTC</strong></td>
                                <td colspan="2"><strong class="total-amount"><?= number_format($commande['total'], 2) ?>€</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="order-detail-grid">
                <!-- Paiements -->
                <div class="card">
                    <div class="card-header">
                        <h2>Historique des paiements</h2>
                    </div>
                    
                    <?php if (!empty($paiements)): ?>
                        <div class="payments-list">
                            <?php foreach ($paiements as $paiement): ?>
                                <div class="payment-item">
                                    <div class="payment-info">
                                        <div class="payment-icon">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div>
                                            <strong><?= number_format($paiement['montant'], 2) ?>€</strong>
                                            <p>
                                                <?= htmlspecialchars($paiement['mode_paiement']) ?>
                                                - <?= date('d/m/Y H:i', strtotime($paiement['date_paiement'])) ?>
                                            </p>
                                            <?php if ($paiement['transaction_id']): ?>
                                                <small>Transaction: <?= htmlspecialchars($paiement['transaction_id']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-<?= $paiement['statut'] ?>">
                                        <?= ucfirst($paiement['statut']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Aucun paiement enregistré</p>
                    <?php endif; ?>
                </div>

                <!-- Mise à jour du statut -->
                <div class="card" id="update-status">
                    <div class="card-header">
                        <h2>Gestion du statut</h2>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Nouveau statut</label>
                            <select name="statut" class="form-select" required>
                                <option value="en_attente" <?= $commande['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="en_cours" <?= $commande['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="terminee" <?= $commande['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                                <option value="annulee" <?= $commande['statut'] === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note (optionnelle)</label>
                            <textarea name="note" class="form-textarea" rows="3" 
                                placeholder="Ajouter une note explicative..."></textarea>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-warning btn-block">
                            <i class="fas fa-sync-alt"></i> Mettre à jour le statut
                        </button>
                    </form>
                </div>
            </div>

            <!-- Historique et notes -->
            <div class="card">
                <div class="card-header">
                    <h2>Historique et Notes</h2>
                    <button class="btn btn-sm btn-primary" id="addNoteBtn">
                        <i class="fas fa-plus"></i> Ajouter une note
                    </button>
                </div>
                
                <div class="timeline">
                    <?php foreach ($historique as $event): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <strong><?= htmlspecialchars($event['prenom'] . ' ' . $event['nom']) ?></strong>
                                    <span class="timeline-role">(<?= ucfirst($event['role']) ?>)</span>
                                    <span class="timeline-date"><?= date('d/m/Y H:i', strtotime($event['date_action'])) ?></span>
                                </div>
                                <div class="timeline-action">
                                    <strong><?= htmlspecialchars($event['action']) ?></strong>
                                </div>
                                <?php if ($event['note']): ?>
                                    <div class="timeline-note">
                                        <?= nl2br(htmlspecialchars($event['note'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($historique)): ?>
                        <p class="no-data">Aucun historique disponible</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Ajouter une note -->
            <div id="noteModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Ajouter une Note</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" class="form-textarea" rows="5" required 
                                placeholder="Saisissez votre note..."></textarea>
                        </div>
                        <button type="submit" name="add_note" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer la note
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Modal gestion
        const modal = document.getElementById('noteModal');
        const btn = document.getElementById('addNoteBtn');
        const span = document.getElementsByClassName('close')[0];

        if (btn) {
            btn.onclick = () => modal.style.display = 'block';
        }
        if (span) {
            span.onclick = () => modal.style.display = 'none';
        }
        window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; }

        function generateInvoice() {
            window.open('generate-invoice.php?id=<?= $commande_id ?>', '_blank');
        }
    </script>
</body>
</html>