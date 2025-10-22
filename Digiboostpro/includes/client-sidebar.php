<?php
// DigiboostPro v1 - Sidebar Client
// Ce fichier doit être inclus dans toutes les pages du client

// Compter les messages non lus
$stmt = $pdo->prepare("SELECT COUNT(*) as non_lus FROM messages WHERE destinataire_id = ? AND lu = FALSE");
$stmt->execute([$_SESSION['user_id']]);
$messages_non_lus = $stmt->fetch()['non_lus'];

// Compter les commandes en cours
$stmt = $pdo->prepare("SELECT COUNT(*) as en_cours FROM commandes WHERE client_id = ? AND statut IN ('en_cours', 'en_revision')");
$stmt->execute([$_SESSION['user_id']]);
$commandes_en_cours = $stmt->fetch()['en_cours'];

// Page actuelle pour le menu actif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-rocket"></i>
            <span><?php echo SITE_NAME; ?></span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
        </a>
        
        <a href="commandes.php" class="menu-item <?php echo ($current_page === 'commandes.php' || $current_page === 'commande-detail.php') ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Mes commandes</span>
            <?php if ($commandes_en_cours > 0): ?>
                <span class="badge"><?php echo $commandes_en_cours; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="nouvelle-commande.php" class="menu-item <?php echo ($current_page === 'nouvelle-commande.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Nouvelle commande</span>
        </a>
        
        <a href="messages.php" class="menu-item <?php echo ($current_page === 'messages.php') ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
            <?php if ($messages_non_lus > 0): ?>
                <span class="badge"><?php echo $messages_non_lus; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="litiges.php" class="menu-item <?php echo ($current_page === 'litiges.php') ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Litiges</span>
        </a>
        
        <a href="favoris.php" class="menu-item <?php echo ($current_page === 'favoris.php') ? 'active' : ''; ?>">
            <i class="fas fa-star"></i>
            <span>Favoris</span>
        </a>
        
        <a href="actualites.php" class="menu-item <?php echo ($current_page === 'actualites.php') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i>
            <span>Actualités</span>
        </a>
        
        <a href="profil.php" class="menu-item <?php echo ($current_page === 'profil.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Mon profil</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="aide.php" class="menu-item <?php echo ($current_page === 'aide.php') ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i>
            <span>Aide</span>
        </a>
        <a href="../public/logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>