<?php
// DigiboostPro v1 - Messagerie client
require_once '../config/config.php';

if (!is_logged_in() || !check_role('client')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];
$conversation_id = intval($_GET['with'] ?? 0);
$success = '';
$error = '';

// Récupérer les conseillers disponibles pour démarrer une conversation
$stmt = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.email
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.nom IN ('conseiller', 'admin') AND u.statut = 'actif'
    ORDER BY u.nom, u.prenom
");
$conseillers = $stmt->fetchAll();

// Si conversation_id fourni, récupérer l'interlocuteur
$interlocuteur = null;
if ($conversation_id > 0) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.email
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$conversation_id]);
    $interlocuteur = $stmt->fetch();
}

// Envoyer un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $destinataire_id = intval($_POST['destinataire_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $commande_id = intval($_POST['commande_id'] ?? 0) ?: null;
        
        if (empty($message)) {
            $error = 'Le message ne peut pas être vide';
        } elseif ($destinataire_id <= 0) {
            $error = 'Destinataire invalide';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (expediteur_id, destinataire_id, commande_id, message, lu)
                    VALUES (?, ?, ?, ?, FALSE)
                ");
                $stmt->execute([$user_id, $destinataire_id, $commande_id, $message]);
                
                // Créer notification pour le destinataire
                create_notification(
                    $destinataire_id,
                    'message',
                    'Nouveau message',
                    substr($message, 0, 100),
                    '/conseiller/messages.php?with=' . $user_id
                );
                
                log_activity($user_id, 'Message envoyé', "Destinataire: $destinataire_id");
                $success = 'Message envoyé avec succès';
                $conversation_id = $destinataire_id;
                
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'envoi du message';
            }
        }
    }
}

// Récupérer les conversations (liste des personnes avec qui on a échangé)
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.expediteur_id = ? THEN m.destinataire_id
            ELSE m.expediteur_id
        END as contact_id,
        u.nom, u.prenom, u.email,
        (SELECT COUNT(*) FROM messages WHERE expediteur_id = contact_id AND destinataire_id = ? AND lu = FALSE) as non_lus,
        (SELECT message FROM messages WHERE (expediteur_id = ? AND destinataire_id = contact_id) OR (expediteur_id = contact_id AND destinataire_id = ?) ORDER BY created_at DESC LIMIT 1) as dernier_message,
        (SELECT created_at FROM messages WHERE (expediteur_id = ? AND destinataire_id = contact_id) OR (expediteur_id = contact_id AND destinataire_id = ?) ORDER BY created_at DESC LIMIT 1) as derniere_date
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END
    WHERE m.expediteur_id = ? OR m.destinataire_id = ?
    ORDER BY derniere_date DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// Si une conversation est sélectionnée, récupérer les messages
$messages_conversation = [];
if ($conversation_id > 0) {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u_exp.nom as expediteur_nom, u_exp.prenom as expediteur_prenom,
               u_dest.nom as destinataire_nom, u_dest.prenom as destinataire_prenom
        FROM messages m
        JOIN users u_exp ON m.expediteur_id = u_exp.id
        JOIN users u_dest ON m.destinataire_id = u_dest.id
        WHERE (m.expediteur_id = ? AND m.destinataire_id = ?)
           OR (m.expediteur_id = ? AND m.destinataire_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $conversation_id, $conversation_id, $user_id]);
    $messages_conversation = $stmt->fetchAll();
    
    // Marquer les messages reçus comme lus
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET lu = TRUE, date_lecture = NOW()
        WHERE destinataire_id = ? AND expediteur_id = ? AND lu = FALSE
    ");
    $stmt->execute([$user_id, $conversation_id]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - <?php echo SITE_NAME; ?></title>
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
                <h1>Messagerie</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="showNewMessageModal()">
                    <i class="fas fa-plus"></i>
                    Nouveau message
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

        <div class="messages-container">
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

            <div class="messages-layout">
                <!-- Liste des conversations -->
                <div class="conversations-sidebar">
                    <div class="conversations-header">
                        <h3>Conversations</h3>
                        <span class="conversations-count"><?php echo count($conversations); ?></span>
                    </div>
                    
                    <div class="conversations-list">
                        <?php if (count($conversations) > 0): ?>
                            <?php foreach ($conversations as $conv): ?>
                                <a href="?with=<?php echo $conv['contact_id']; ?>" 
                                   class="conversation-item <?php echo $conversation_id == $conv['contact_id'] ? 'active' : ''; ?>">
                                    <div class="conversation-avatar">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-header-row">
                                            <strong><?php echo escape($conv['prenom'] . ' ' . $conv['nom']); ?></strong>
                                            <?php if ($conv['non_lus'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['non_lus']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="last-message"><?php echo escape(substr($conv['dernier_message'], 0, 50)); ?>...</p>
                                        <small><?php echo format_date($conv['derniere_date'], 'd/m/Y H:i'); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-conversations">
                                <i class="fas fa-comments"></i>
                                <p>Aucune conversation</p>
                                <button class="btn btn-primary btn-sm" onclick="showNewMessageModal()">
                                    Démarrer une conversation
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Zone de conversation -->
                <div class="conversation-area">
                    <?php if ($conversation_id > 0 && $interlocuteur): ?>
                        <div class="conversation-header">
                            <div class="contact-info">
                                <div class="contact-avatar">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <strong><?php echo escape($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']); ?></strong>
                                    <span><?php echo escape($interlocuteur['email']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="messages-list" id="messagesList">
                            <?php foreach ($messages_conversation as $msg): ?>
                                <div class="message-bubble <?php echo $msg['expediteur_id'] == $user_id ? 'sent' : 'received'; ?>">
                                    <div class="message-content">
                                        <?php echo nl2br(escape($msg['message'])); ?>
                                    </div>
                                    <div class="message-meta">
                                        <span><?php echo format_date($msg['created_at'], 'd/m/Y H:i'); ?></span>
                                        <?php if ($msg['expediteur_id'] == $user_id): ?>
                                            <i class="fas fa-check<?php echo $msg['lu'] ? '-double' : ''; ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="message-input-area">
                            <form method="POST" action="" class="message-form">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="destinataire_id" value="<?php echo $conversation_id; ?>">
                                <input type="hidden" name="send_message" value="1">
                                
                                <textarea 
                                    name="message" 
                                    placeholder="Écrivez votre message..." 
                                    rows="3"
                                    required
                                ></textarea>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    Envoyer
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-conversation-selected">
                            <i class="fas fa-comments"></i>
                            <h3>Sélectionnez une conversation</h3>
                            <p>Choisissez une conversation dans la liste ou démarrez-en une nouvelle</p>
                            <button class="btn btn-primary" onclick="showNewMessageModal()">
                                <i class="fas fa-plus"></i>
                                Nouvelle conversation
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal nouveau message -->
    <div class="modal" id="newMessageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nouveau message</h3>
                <button class="modal-close" onclick="hideNewMessageModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="send_message" value="1">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Destinataire</label>
                        <select name="destinataire_id" class="form-control" required>
                            <option value="">Sélectionnez un conseiller</option>
                            <?php foreach ($conseillers as $conseiller): ?>
                                <option value="<?php echo $conseiller['id']; ?>">
                                    <?php echo escape($conseiller['prenom'] . ' ' . $conseiller['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideNewMessageModal()">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .messages-container {
            padding: 2rem;
            height: calc(100vh - 100px);
        }

        .messages-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 0;
            height: 100%;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .conversations-sidebar {
            border-right: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversations-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .conversations-count {
            background: var(--gray-200);
            color: var(--gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            transition: var(--transition);
            cursor: pointer;
        }

        .conversation-item:hover {
            background: var(--gray-50);
        }

        .conversation-item.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, transparent 100%);
            border-left: 3px solid var(--primary);
        }

        .conversation-avatar {
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

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .unread-badge {
            background: var(--danger);
            color: var(--white);
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .last-message {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin: 0.25rem 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .conversation-info small {
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .empty-conversations {
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray-500);
        }

        .empty-conversations i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .conversation-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .conversation-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .contact-avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.25rem;
        }

        .contact-info strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .contact-info span {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .messages-list {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message-bubble {
            max-width: 70%;
            display: flex;
            flex-direction: column;
        }

        .message-bubble.sent {
            align-self: flex-end;
        }

        .message-bubble.received {
            align-self: flex-start;
        }

        .message-content {
            padding: 1rem 1.25rem;
            border-radius: var(--radius);
            line-height: 1.5;
        }

        .message-bubble.sent .message-content {
            background: var(--primary);
            color: var(--white);
            border-bottom-right-radius: 0;
        }

        .message-bubble.received .message-content {
            background: var(--gray-100);
            color: var(--gray-900);
            border-bottom-left-radius: 0;
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .message-bubble.sent .message-meta {
            justify-content: flex-end;
        }

        .message-input-area {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .message-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .message-form textarea {
            flex: 1;
            padding: 0.875rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            resize: none;
            font-family: inherit;
        }

        .message-form textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .no-conversation-selected {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
            padding: 3rem;
            text-align: center;
        }

        .no-conversation-selected i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-600);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 1024px) {
            .messages-layout {
                grid-template-columns: 1fr;
            }

            .conversations-sidebar {
                display: none;
            }

            .messages-container {
                padding: 1rem;
            }
        }
    </style>

    <script>
        function showNewMessageModal() {
            document.getElementById('newMessageModal').classList.add('show');
        }

        function hideNewMessageModal() {
            document.getElementById('newMessageModal').classList.remove('show');
        }

        // Auto-scroll vers le bas des messages
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }

        // Fermer modal en cliquant à l'extérieur
        document.getElementById('newMessageModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideNewMessageModal();
            }
        });
    </script>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>