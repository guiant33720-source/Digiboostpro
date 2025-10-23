<?php
/**
 * Page de liste des actualités publiques
 * Affiche toutes les actualités publiées avec pagination
 */

require_once '../config/config.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Catégorie filter (optionnel)
$categorie = isset($_GET['cat']) ? $_GET['cat'] : '';

try {
    // Comptage total
    $count_sql = "SELECT COUNT(*) as total FROM actualites WHERE statut = 'publie'";
    if ($categorie) {
        $count_sql .= " AND categorie = :categorie";
    }
    $count_stmt = $pdo->prepare($count_sql);
    if ($categorie) {
        $count_stmt->bindParam(':categorie', $categorie);
    }
    $count_stmt->execute();
    $total_actualites = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_actualites / $per_page);

    // Récupération des actualités
    $sql = "SELECT a.*, u.nom as auteur_nom, u.prenom as auteur_prenom 
            FROM actualites a 
            LEFT JOIN users u ON a.auteur_id = u.id 
            WHERE a.statut = 'publie'";
    
    if ($categorie) {
        $sql .= " AND a.categorie = :categorie";
    }
    
    $sql .= " ORDER BY a.date_publication DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    if ($categorie) {
        $stmt->bindParam(':categorie', $categorie);
    }
    $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $actualites = $stmt->fetchAll();

    // Récupération des catégories disponibles
    $cat_stmt = $pdo->query("SELECT DISTINCT categorie FROM actualites WHERE statut = 'publie' ORDER BY categorie");
    $categories = $cat_stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Erreur lors du chargement des actualités : " . $e->getMessage();
    $actualites = [];
    $categories = [];
}

$page_title = "Actualités - DigiboostPro";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="logo">
                    <i class="fas fa-rocket"></i> DigiboostPro
                </a>
                <ul class="nav-menu">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="tarifs.php">Tarifs</a></li>
                    <li><a href="actualites.php" class="active">Actualités</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <div class="nav-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Connexion</a>
                        <a href="register.php" class="btn btn-primary">Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-newspaper"></i> Nos Actualités</h1>
            <p>Restez informé des dernières nouveautés et conseils digitaux</p>
        </div>
    </section>

    <!-- Filtres -->
    <section class="filters-section">
        <div class="container">
            <div class="filters-wrapper">
                <a href="actualites.php" class="filter-btn <?php echo !$categorie ? 'active' : ''; ?>">
                    Toutes
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?cat=<?php echo urlencode($cat['categorie']); ?>" 
                       class="filter-btn <?php echo $categorie === $cat['categorie'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars(ucfirst($cat['categorie'])); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Liste des actualités -->
    <section class="actualites-section">
        <div class="container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($actualites)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucune actualité disponible</h3>
                    <p>Revenez bientôt pour découvrir nos dernières nouveautés !</p>
                </div>
            <?php else: ?>
                <div class="actualites-grid">
                    <?php foreach ($actualites as $actu): ?>
                        <article class="actualite-card">
                            <?php if ($actu['image_url']): ?>
                                <div class="actualite-image">
                                    <img src="<?php echo htmlspecialchars($actu['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($actu['titre']); ?>">
                                    <span class="actualite-badge">
                                        <?php echo htmlspecialchars(ucfirst($actu['categorie'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="actualite-content">
                                <div class="actualite-meta">
                                    <span><i class="fas fa-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($actu['date_publication'])); ?>
                                    </span>
                                    <?php if ($actu['auteur_nom']): ?>
                                        <span><i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($actu['auteur_prenom'] . ' ' . $actu['auteur_nom']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="actualite-title">
                                    <a href="actualite.php?id=<?php echo $actu['id']; ?>">
                                        <?php echo htmlspecialchars($actu['titre']); ?>
                                    </a>
                                </h3>
                                
                                <p class="actualite-excerpt">
                                    <?php 
                                    // Extrait du contenu (200 caractères)
                                    $contenu = strip_tags($actu['contenu']);
                                    echo htmlspecialchars(mb_substr($contenu, 0, 200)) . '...'; 
                                    ?>
                                </p>
                                
                                <a href="actualite.php?id=<?php echo $actu['id']; ?>" class="btn-read-more">
                                    Lire la suite <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $categorie ? '&cat=' . urlencode($categorie) : ''; ?>" 
                               class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-numbers">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $categorie ? '&cat=' . urlencode($categorie) : ''; ?>" 
                                   class="pagination-number <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $categorie ? '&cat=' . urlencode($categorie) : ''; ?>" 
                               class="pagination-btn">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Newsletter CTA -->
    <section class="newsletter-cta">
        <div class="container">
            <div class="newsletter-box">
                <h2><i class="fas fa-envelope"></i> Restez informé !</h2>
                <p>Inscrivez-vous à notre newsletter pour recevoir nos actualités directement par email</p>
                <a href="newsletter.php" class="btn btn-primary">S'inscrire à la newsletter</a>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
<style>
        .actualites-section {
            padding: 5rem 0;
        }

        .filters-section {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .filters-left h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .category-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 0.5rem 1.25rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .category-btn:hover,
        .category-btn.active {
            background: var(--primary);
            color: var(--white);
        }

        .results-count {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .actualites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .actualite-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .actualite-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .actualite-image {
            display: block;
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .actualite-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .actualite-card:hover .actualite-image img {
            transform: scale(1.1);
        }

        .actualite-placeholder {
            width: 100%;
            height: 100%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 4rem;
        }

        .actualite-category {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--primary);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .actualite-content {
            padding: 1.5rem;
        }

        .actualite-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .meta-item i {
            color: var(--primary);
        }

        .actualite-content h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .actualite-content h3 a {
            color: var(--gray-900);
            transition: var(--transition);
        }

        .actualite-content h3 a:hover {
            color: var(--primary);
        }

        .actualite-excerpt {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .actualite-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .actualite-stats {
            display: flex;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .stat-item i {
            color: var(--gray-500);
        }

        .read-more {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .read-more:hover {
            gap: 0.75rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
        }

        .pagination-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--white);
            color: var(--gray-700);
            border-radius: var(--radius);
            border: 2px solid var(--gray-200);
            font-weight: 500;
            transition: var(--transition);
        }

        .pagination-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }

        .pagination-numbers {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-number {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: 2px solid var(--gray-200);
            color: var(--gray-700);
            font-weight: 600;
            transition: var(--transition);
        }

        .pagination-number:hover,
        .pagination-number.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }

        .newsletter-cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--white);
        }

        .newsletter-cta-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 2rem;
        }

        .newsletter-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .newsletter-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .newsletter-text p {
            font-size: 1.15rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
        }

        .newsletter-form-inline {
            display: flex;
            gap: 1rem;
            max-width: 500px;
            width: 100%;
        }

        .newsletter-form-inline input {
            flex: 1;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            border: none;
            font-size: 1rem;
        }

        .newsletter-form-inline button {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .actualites-grid {
                grid-template-columns: 1fr;
            }

            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-left {
                width: 100%;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .pagination-numbers {
                order: -1;
                width: 100%;
                justify-content: center;
            }

            .newsletter-form-inline {
                flex-direction: column;
            }

            .newsletter-form-inline button {
                width: 100%;
            }

            .newsletter-text h2 {
                font-size: 2rem;
            }
        }
    </style>
    <script src="../assets/js/main.js"></script>
</body>
</html>