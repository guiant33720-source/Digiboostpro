<?php
// DigiboostPro v1 - Page Services
require_once '../config/config.php';

// Récupérer tous les packs actifs
$stmt = $pdo->query("
    SELECT * FROM packs 
    WHERE actif = TRUE 
    ORDER BY prix ASC
");
$packs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="services.php" class="active">Services</a></li>
                    <li><a href="tarifs.php">Tarifs</a></li>
                    <li><a href="actualites.php">Actualités</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <div class="nav-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="/<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary">
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
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-content">
                <h1>Nos Services</h1>
                <p>Des solutions complètes pour propulser votre visibilité en ligne</p>
                <div class="breadcrumb">
                    <a href="index.php">Accueil</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Services</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="services-section">
        <div class="container">
            <div class="services-intro">
                <h2>Services Disponibles</h2>
                <p>Découvrez nos services de marketing digital conçus pour faire croître votre entreprise</p>
            </div>

            <div class="services-grid">
                <?php foreach ($packs as $pack): ?>
                    <div class="service-detail-card">
                        <div class="service-badge"><?php echo escape($pack['type']); ?></div>
                        <div class="service-icon-large">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3><?php echo escape($pack['nom']); ?></h3>
                        <p class="service-description"><?php echo escape($pack['description']); ?></p>
                        
                        <div class="service-price">
                            <span class="price-amount"><?php echo format_price($pack['prix']); ?></span>
                            <span class="price-label">par projet</span>
                        </div>

                        <div class="service-features-list">
                            <h4>Inclus dans ce service :</h4>
                            <?php 
                            $features = explode("\n", $pack['features']);
                            foreach ($features as $feature): 
                                if (trim($feature)):
                            ?>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?php echo escape($feature); ?></span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>

                        <div class="service-delivery">
                            <i class="far fa-clock"></i>
                            Livraison sous <?php echo $pack['delai_livraison']; ?> jours
                        </div>

                        <a href="<?php echo is_logged_in() ? '../client/nouvelle-commande.php?pack=' . $pack['id'] : 'register.php'; ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-shopping-cart"></i>
                            Commander maintenant
                        </a>
                    </div>
                <?php endforeach; ?>

                <!-- Services à venir -->
                <div class="service-detail-card coming-soon">
                    <div class="service-badge badge-soon">Bientôt</div>
                    <div class="service-icon-large">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <h3>Stratégie de Contenu</h3>
                    <p class="service-description">Création d'une stratégie éditoriale complète avec calendrier de publication et recommandations SEO.</p>
                    <button class="btn btn-secondary btn-block" disabled>
                        <i class="fas fa-hourglass-half"></i>
                        Bientôt disponible
                    </button>
                </div>

                <div class="service-detail-card coming-soon">
                    <div class="service-badge badge-soon">Bientôt</div>
                    <div class="service-icon-large">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analyse Concurrentielle</h3>
                    <p class="service-description">Étude approfondie de votre marché et analyse des stratégies de vos concurrents directs.</p>
                    <button class="btn btn-secondary btn-block" disabled>
                        <i class="fas fa-hourglass-half"></i>
                        Bientôt disponible
                    </button>
                </div>

                <div class="service-detail-card coming-soon">
                    <div class="service-badge badge-soon">Bientôt</div>
                    <div class="service-icon-large">
                        <i class="fas fa-link"></i>
                    </div>
                    <h3>Campagne de Netlinking</h3>
                    <p class="service-description">Stratégie de liens entrants de qualité pour améliorer votre autorité de domaine.</p>
                    <button class="btn btn-secondary btn-block" disabled>
                        <i class="fas fa-hourglass-half"></i>
                        Bientôt disponible
                    </button>
                </div>

                <div class="service-detail-card coming-soon">
                    <div class="service-badge badge-soon">Bientôt</div>
                    <div class="service-icon-large">
                        <i class="fas fa-ad"></i>
                    </div>
                    <h3>Google Ads</h3>
                    <p class="service-description">Gestion complète de vos campagnes publicitaires Google Ads avec optimisation continue.</p>
                    <button class="btn btn-secondary btn-block" disabled>
                        <i class="fas fa-hourglass-half"></i>
                        Bientôt disponible
                    </button>
                </div>

                <div class="service-detail-card coming-soon">
                    <div class="service-badge badge-soon">Bientôt</div>
                    <div class="service-icon-large">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3>Social Media</h3>
                    <p class="service-description">Gestion de vos réseaux sociaux avec création de contenu et community management.</p>
                    <button class="btn btn-secondary btn-block" disabled>
                        <i class="fas fa-hourglass-half"></i>
                        Bientôt disponible
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <div class="section-header">
                <h2>Notre Processus</h2>
                <p>Comment nous travaillons pour garantir votre succès</p>
            </div>

            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>Analyse de vos besoins</h3>
                    <p>Nous étudions votre projet, vos objectifs et votre marché pour proposer la solution adaptée.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Plan d'action personnalisé</h3>
                    <p>Création d'une stratégie sur-mesure avec des recommandations concrètes et actionnables.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Mise en œuvre</h3>
                    <p>Nos experts exécutent le plan d'action avec suivi régulier et ajustements si nécessaire.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <div class="step-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Suivi et optimisation</h3>
                    <p>Analyse des résultats, reporting détaillé et optimisations continues pour maximiser votre ROI.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Questions Fréquentes</h2>
                <p>Tout ce que vous devez savoir sur nos services</p>
            </div>

            <div class="faq-grid">
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Quel service choisir pour débuter ?</h4>
                    <p>Nous recommandons de commencer par un Audit SEO Standard qui vous donnera une vision complète de votre situation actuelle et des actions prioritaires.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Combien de temps faut-il pour voir des résultats ?</h4>
                    <p>Les premiers résultats sont généralement visibles sous 2-3 mois. Le SEO est un investissement à moyen/long terme qui porte ses fruits durablement.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Puis-je modifier ma commande après validation ?</h4>
                    <p>Oui, vous pouvez contacter votre conseiller dédié pour ajuster votre commande tant qu'elle n'est pas en cours de traitement.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Offrez-vous des garanties ?</h4>
                    <p>Nous garantissons la qualité de nos livrables. Si vous n'êtes pas satisfait, nous travaillons avec vous pour corriger jusqu'à satisfaction.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Puis-je avoir un service personnalisé ?</h4>
                    <p>Absolument ! Contactez-nous pour discuter de vos besoins spécifiques et nous créerons une offre sur-mesure.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Proposez-vous un suivi après livraison ?</h4>
                    <p>Oui, tous nos packs incluent un support de 30 jours minimum après livraison pour vous accompagner dans la mise en œuvre.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <div class="cta-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h2>Prêt à commencer ?</h2>
                <p>Lancez votre premier projet dès aujourd'hui</p>
                <div class="cta-buttons">
                    <a href="tarifs.php" class="btn btn-white btn-lg">
                        <i class="fas fa-shopping-cart"></i> Voir les tarifs
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

    <style>
        .page-hero {
            background: var(--gradient);
            color: var(--white);
            padding: 4rem 0 3rem;
            text-align: center;
        }
        
        .page-hero-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 2rem;
        }
        
        .breadcrumb a {
            color: var(--white);
        }
        
        .services-section {
            padding: 5rem 0;
        }
        
        .services-intro {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .services-intro h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .service-detail-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            position: relative;
            transition: var(--transition);
        }
        
        .service-detail-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .service-detail-card.coming-soon {
            opacity: 0.7;
        }
        
        .service-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .service-icon-large {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .service-price {
            text-align: center;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: var(--gray-100);
            border-radius: var(--radius);
        }
        
        .price-amount {
            display: block;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .price-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .service-features-list h4 {
            margin-bottom: 1rem;
            color: var(--gray-900);
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            color: var(--gray-700);
        }
        
        .feature-item i {
            color: var(--success);
        }
        
        .service-delivery {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            margin: 1.5rem 0;
            background: var(--gray-50);
            border-radius: var(--radius);
            color: var(--gray-700);
        }
        
        .process-section {
            padding: 5rem 0;
            background: var(--gray-100);
        }
        
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .process-step {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
            position: relative;
        }
        
        .step-number {
            position: absolute;
            top: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: var(--gradient);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .step-icon {
            margin: 2rem 0 1rem;
            font-size: 3rem;
            color: var(--primary);
        }
        
        .faq-section {
            padding: 5rem 0;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .faq-item {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .faq-item h4 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }
        
        .faq-item i {
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .page-hero-content h1 {
                font-size: 2rem;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>