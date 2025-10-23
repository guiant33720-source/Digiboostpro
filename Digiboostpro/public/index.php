<?php
// DigiboostPro v1 - Page d'accueil
require_once '../config/config.php';

// Récupérer les témoignages (simulés pour la démo)
$testimonials = [
    [
        'nom' => 'Sophie Martin',
        'entreprise' => 'E-Commerce Mode',
        'avatar' => 'avatar1.jpg',
        'texte' => 'Grâce à l\'audit SEO de DigiboostPro, notre trafic a augmenté de 150% en 3 mois !',
        'note' => 5
    ],
    [
        'nom' => 'Thomas Durand',
        'entreprise' => 'Restaurant Le Gourmet',
        'texte' => 'Service professionnel et résultats rapides. Je recommande vivement !',
        'note' => 5
    ],
    [
        'nom' => 'Marie Leblanc',
        'entreprise' => 'Startup Tech',
        'texte' => 'L\'équipe est à l\'écoute et les livrables sont de grande qualité.',
        'note' => 5
    ]
];

// Récupérer les dernières actualités
$stmt = $pdo->query("
    SELECT id, titre, contenu, image, date_publication 
    FROM actualites 
    WHERE statut = 'publié' 
    ORDER BY date_publication DESC 
    LIMIT 3
");
$actualites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Marketing Digital Semi-Automatique</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header / Navigation -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="nav-brand">
                    <i class="fas fa-rocket"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Accueil</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="tarifs.php">Tarifs</a></li>
                    <li><a href="actualites.php">Actualités</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <div class="nav-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="/Digiboostpro/<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary">
                         <i class="fas fa-tachometer-alt"></i> Tableau de bord
                     </a>

                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary">Connexion</a>
                        <a href="register.php" class="btn btn-primary">S'inscrire</a>
                    <?php endif; ?>
                </div>
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <span class="hero-badge">
                        <i class="fas fa-star"></i> Service Premium
                    </span>
                    <h1 class="hero-title">
                        Boostez votre <span class="gradient-text">Visibilité en Ligne</span>
                    </h1>
                    <p class="hero-description">
                        Plateforme de marketing digital semi-automatique. Audit SEO, stratégie de contenu et optimisation complète pour propulser votre business.
                    </p>
                    <div class="hero-cta">
                        <a href="tarifs.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-chart-line"></i> Commencer mon audit SEO
                        </a>
                        <a href="services.php" class="btn btn-secondary btn-lg">
                            Découvrir nos services
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat">
                            <strong>500+</strong>
                            <span>Clients satisfaits</span>
                        </div>
                        <div class="stat">
                            <strong>95%</strong>
                            <span>Taux de satisfaction</span>
                        </div>
                        <div class="stat">
                            <strong>150%</strong>
                            <span>Croissance moyenne</span>
                        </div>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-card">
                        <div class="card-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Audit SEO Premium</h3>
                        <div class="card-price">
                            <span class="old-price">199€</span>
                            <span class="new-price">99€</span>
                        </div>
                        <ul class="card-features">
                            <li><i class="fas fa-check"></i> Analyse technique complète</li>
                            <li><i class="fas fa-check"></i> Mots-clés stratégiques</li>
                            <li><i class="fas fa-check"></i> Plan d'action détaillé</li>
                            <li><i class="fas fa-check"></i> Support 30 jours</li>
                        </ul>
                        <a href="tarifs.php" class="btn btn-primary btn-block">
                            Commander maintenant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-preview">
        <div class="container">
            <div class="section-header">
                <h2>Nos Services Phares</h2>
                <p>Des solutions complètes pour votre marketing digital</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Audit SEO</h3>
                    <p>Analyse approfondie de votre site avec recommandations personnalisées</p>
                    <a href="services.php" class="link-arrow">
                        En savoir plus <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <h3>Stratégie de Contenu</h3>
                    <p>Création d'une stratégie éditoriale performante et engageante</p>
                    <span class="badge-soon">Bientôt</span>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analyse Concurrentielle</h3>
                    <p>Étude de votre marché et de vos principaux concurrents</p>
                    <span class="badge-soon">Bientôt</span>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <h3>Netlinking</h3>
                    <p>Stratégie de liens pour améliorer votre autorité de domaine</p>
                    <span class="badge-soon">Bientôt</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2>Ce que disent nos clients</h2>
                <p>Des résultats concrets et mesurables</p>
            </div>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <?php for ($i = 0; $i < $testimonial['note']; $i++): ?>
                            <i class="fas fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="testimonial-text">"<?php echo escape($testimonial['texte']); ?>"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <strong><?php echo escape($testimonial['nom']); ?></strong>
                            <span><?php echo escape($testimonial['entreprise']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Actualités Section -->
    <?php if (count($actualites) > 0): ?>
    <section class="news-preview">
        <div class="container">
            <div class="section-header">
                <h2>Dernières Actualités</h2>
                <p>Restez informé de nos nouveautés</p>
            </div>
            <div class="news-grid">
                <?php foreach ($actualites as $actu): ?>
                <article class="news-card">
                    <div class="news-image">
                        <?php if ($actu['image']): ?>
                            <img src="../uploads/actualites/<?php echo escape($actu['image']); ?>" alt="<?php echo escape($actu['titre']); ?>">
                        <?php else: ?>
                            <div class="news-placeholder">
                                <i class="fas fa-newspaper"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="news-content">
                        <div class="news-date">
                            <i class="far fa-calendar"></i>
                            <?php echo format_date($actu['created_at'], 'd M Y'); ?>
                        </div>
                        <h3><?php echo escape($actu['titre']); ?></h3>
                        <p><?php echo escape(substr($actu['contenu'], 0, 150)); ?>...</p>
                        <a href="actualite.php?id=<?php echo $actu['id']; ?>" class="link-arrow">
                            Lire la suite <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="actualites.php" class="btn btn-secondary">
                    Voir toutes les actualités
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <div class="cta-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h2>Prêt à booster votre visibilité ?</h2>
                <p>Commencez dès aujourd'hui avec notre audit SEO à 99€</p>
                <div class="cta-buttons">
                    <a href="tarifs.php" class="btn btn-white btn-lg">
                        <i class="fas fa-shopping-cart"></i> Commander maintenant
                    </a>
                    <a href="contact.php" class="btn btn-outline-white btn-lg">
                        <i class="fas fa-comments"></i> Nous contacter
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand">
                        <i class="fas fa-rocket"></i>
                        <span><?php echo SITE_NAME; ?></span>
                    </div>
                    <p>Votre partenaire pour réussir en ligne. Marketing digital semi-automatique, résultats garantis.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="services.php">Audit SEO</a></li>
                        <li><a href="services.php">Stratégie de Contenu</a></li>
                        <li><a href="services.php">Analyse Concurrentielle</a></li>
                        <li><a href="tarifs.php">Nos Tarifs</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>À propos</h4>
                    <ul>
                        <li><a href="about.php">Qui sommes-nous</a></li>
                        <li><a href="actualites.php">Actualités</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Newsletter</h4>
                    <p>Recevez nos dernières actualités et offres exclusives</p>
                    <form class="newsletter-form" method="POST" action="newsletter.php">
                        <input type="email" name="email" placeholder="Votre email" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-links">
                    <a href="mentions-legales.php">Mentions légales</a>
                    <a href="politique-confidentialite.php">Politique de confidentialité</a>
                    <a href="cgu.php">CGU</a>
                </div>
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>