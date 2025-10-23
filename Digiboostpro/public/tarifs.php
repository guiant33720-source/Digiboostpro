<?php
// DigiboostPro v1 - Page Tarifs
require_once '../config/config.php';

// Récupérer les packs
$stmt = $pdo->query("
    SELECT * FROM packs 
    WHERE actif = TRUE 
    ORDER BY 
        CASE type
            WHEN 'Starter' THEN 1
            WHEN 'Standard' THEN 2
            WHEN 'Premium' THEN 3
            ELSE 4
        END
");
$packs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifs - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="services.php">Services</a></li>
                    <li><a href="tarifs.php" class="active">Tarifs</a></li>
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
                <h1>Nos Tarifs</h1>
                <p>Des prix transparents et compétitifs pour tous vos besoins</p>
                <div class="breadcrumb">
                    <a href="index.php">Accueil</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Tarifs</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section">
        <div class="container">
            <div class="pricing-intro">
                <h2>Choisissez votre pack</h2>
                <p>Tous nos packs incluent un support de qualité et une garantie satisfaction</p>
            </div>

            <div class="pricing-grid">
                <?php foreach ($packs as $index => $pack): ?>
                    <div class="pricing-card <?php echo $pack['type'] === 'Standard' ? 'featured' : ''; ?>">
                        <?php if ($pack['type'] === 'Standard'): ?>
                            <div class="popular-badge">
                                <i class="fas fa-star"></i> Plus populaire
                            </div>
                        <?php endif; ?>
                        
                        <div class="pricing-header">
                            <h3><?php echo escape($pack['nom']); ?></h3>
                            <div class="pricing-type"><?php echo escape($pack['type']); ?></div>
                        </div>

                        <div class="pricing-price">
                            <span class="price-currency">€</span>
                            <span class="price-amount"><?php echo number_format($pack['prix'], 0); ?></span>
                            <span class="price-period">/ projet</span>
                        </div>

                        <p class="pricing-description"><?php echo escape($pack['description']); ?></p>

                        <ul class="pricing-features">
                            <?php 
                            $features = explode("\n", $pack['features']);
                            foreach ($features as $feature): 
                                if (trim($feature)):
                            ?>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <span><?php echo escape($feature); ?></span>
                                </li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <li>
                                <i class="fas fa-check"></i>
                                <span>Livraison sous <?php echo $pack['delai_livraison']; ?> jours</span>
                            </li>
                            <li>
                                <i class="fas fa-check"></i>
                                <span>Support 30 jours inclus</span>
                            </li>
                        </ul>

                        <a href="<?php echo is_logged_in() ? '../client/nouvelle-commande.php?pack=' . $pack['id'] : 'register.php'; ?>" 
                           class="btn <?php echo $pack['type'] === 'Standard' ? 'btn-primary' : 'btn-secondary'; ?> btn-block btn-lg">
                            <i class="fas fa-shopping-cart"></i>
                            Commander maintenant
                        </a>
                    </div>
                <?php endforeach; ?>

                <!-- Pack Personnalisé -->
                <div class="pricing-card custom-pack">
                    <div class="pricing-header">
                        <h3>Pack Personnalisé</h3>
                        <div class="pricing-type">Sur mesure</div>
                    </div>

                    <div class="pricing-price">
                        <span class="price-currency">Sur</span>
                        <span class="price-amount">Devis</span>
                    </div>

                    <p class="pricing-description">Un projet spécifique ? Nous créons une offre adaptée à vos besoins</p>

                    <ul class="pricing-features">
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Analyse détaillée de vos besoins</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Proposition sur-mesure</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Services combinés</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Tarifs négociables</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Conseiller dédié</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Support prioritaire</span>
                        </li>
                    </ul>

                    <a href="contact.php" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-envelope"></i>
                        Demander un devis
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Comparison Table -->
    <section class="comparison-section">
        <div class="container">
            <div class="section-header">
                <h2>Comparaison des packs</h2>
                <p>Tous les détails en un coup d'œil</p>
            </div>

            <div class="comparison-table-wrapper">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Fonctionnalités</th>
                            <th>Starter</th>
                            <th>Standard</th>
                            <th>Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Analyse technique du site</strong></td>
                            <td><i class="fas fa-check text-success"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Recherche de mots-clés</strong></td>
                            <td>Basique (10 mots-clés)</td>
                            <td>Approfondie (30 mots-clés)</td>
                            <td>Complète (50+ mots-clés)</td>
                        </tr>
                        <tr>
                            <td><strong>Analyse concurrentielle</strong></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Plan d'action détaillé</strong></td>
                            <td><i class="fas fa-check text-success"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Stratégie de contenu</strong></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Analyse des backlinks</strong></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Appel stratégique</strong></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Délai de livraison</strong></td>
                            <td>3 jours</td>
                            <td>7 jours</td>
                            <td>14 jours</td>
                        </tr>
                        <tr>
                            <td><strong>Support après livraison</strong></td>
                            <td>15 jours</td>
                            <td>30 jours</td>
                            <td>90 jours</td>
                        </tr>
                        <tr>
                            <td><strong>Révisions incluses</strong></td>
                            <td>1</td>
                            <td>2</td>
                            <td>Illimitées</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- FAQ Pricing -->
    <section class="faq-pricing">
        <div class="container">
            <div class="section-header">
                <h2>Questions sur les tarifs</h2>
                <p>Tout ce que vous devez savoir</p>
            </div>

            <div class="faq-accordion">
                <div class="faq-item-accordion">
                    <button class="accordion-header">
                        <span>Comment fonctionne le paiement ?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Le paiement s'effectue en ligne de manière sécurisée lors de votre commande. Nous acceptons les cartes bancaires et PayPal. Vous pouvez également payer par virement bancaire pour les montants supérieurs à 500€.</p>
                    </div>
                </div>

                <div class="faq-item-accordion">
                    <button class="accordion-header">
                        <span>Puis-je obtenir un remboursement ?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Nous garantissons votre satisfaction. Si le livrable ne correspond pas à vos attentes, nous le révisons jusqu'à satisfaction. En cas d'impossibilité, un remboursement partiel ou total peut être accordé selon les circonstances.</p>
                    </div>
                </div>

                <div class="faq-item-accordion">
                    <button class="accordion-header">
                        <span>Y a-t-il des frais cachés ?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Non, nos tarifs sont tout compris. Le prix affiché inclut le service, le support et les révisions mentionnées. Aucun frais supplémentaire ne sera facturé sauf demande de votre part pour des services additionnels.</p>
                    </div>
                </div>

                <div class="faq-item-accordion">
                    <button class="accordion-header">
                        <span>Proposez-vous des réductions ?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Oui ! Nous offrons des réductions pour les commandes multiples et les clients réguliers. Inscrivez-vous à notre newsletter pour recevoir des codes promo exclusifs.</p>
                    </div>
                </div>

                <div class="faq-item-accordion">
                    <button class="accordion-header">
                        <span>Puis-je changer de pack après avoir commandé ?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Oui, tant que votre commande n'est pas en cours de traitement. Contactez votre conseiller pour modifier votre pack. Un ajustement de prix sera effectué si nécessaire.</p>
                    </div>
                </div>

                <div class="faq-item-accordion">
                    <button class="accordion-header">
                        <span>Le pack personnalisé est-il plus cher ?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Pas nécessairement. Le pack personnalisé permet d'optimiser le budget en ne payant que pour les services dont vous avez réellement besoin. Demandez un devis gratuit pour comparer.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Guarantee Section -->
    <section class="guarantee-section">
        <div class="container">
            <div class="guarantee-grid">
                <div class="guarantee-item">
                    <div class="guarantee-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Garantie Satisfaction</h3>
                    <p>Nous ne sommes satisfaits que si vous l'êtes. Révisions illimitées jusqu'à validation finale.</p>
                </div>

                <div class="guarantee-item">
                    <div class="guarantee-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Paiement Sécurisé</h3>
                    <p>Transactions 100% sécurisées avec les standards bancaires les plus élevés.</p>
                </div>

                <div class="guarantee-item">
                    <div class="guarantee-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Support Réactif</h3>
                    <p>Notre équipe répond à vos questions dans les 24h ouvrées maximum.</p>
                </div>

                <div class="guarantee-item">
                    <div class="guarantee-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h3>Qualité Professionnelle</h3>
                    <p>Livrables de qualité professionnelle réalisés par des experts certifiés.</p>
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
                <h2>Prêt à démarrer votre projet ?</h2>
                <p>Choisissez votre pack et lancez-vous dès maintenant</p>
                <div class="cta-buttons">
                    <a href="<?php echo is_logged_in() ? '../client/nouvelle-commande.php' : 'register.php'; ?>" class="btn btn-white btn-lg">
                        <i class="fas fa-shopping-cart"></i> Commander maintenant
                    </a>
                    <a href="contact.php" class="btn btn-outline-white btn-lg">
                        <i class="fas fa-comments"></i> Obtenir un devis
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
        .pricing-section {
            padding: 5rem 0;
            background: var(--gray-50);
        }

        .pricing-intro {
            text-align: center;
            margin-bottom: 3rem;
        }

        .pricing-intro h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .pricing-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
            position: relative;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .pricing-card.featured {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .pricing-card.featured:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gradient);
            color: var(--white);
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .pricing-header h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .pricing-type {
            display: inline-block;
            padding: 0.25rem 1rem;
            background: var(--gray-100);
            border-radius: 1rem;
            font-size: 0.85rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .pricing-price {
            text-align: center;
            margin: 2rem 0;
            padding: 2rem;
            background: var(--gray-50);
            border-radius: var(--radius);
        }

        .price-currency {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-600);
            vertical-align: super;
        }

        .price-amount {
            font-size: 4rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }

        .price-period {
            display: block;
            color: var(--gray-600);
            margin-top: 0.5rem;
        }

        .pricing-description {
            text-align: center;
            color: var(--gray-600);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .pricing-features {
            list-style: none;
            margin: 2rem 0;
        }

        .pricing-features li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0;
            color: var(--gray-700);
        }

        .pricing-features i {
            color: var(--success);
            margin-top: 0.25rem;
        }

        .custom-pack {
            background: var(--gradient);
            color: var(--white);
        }

        .custom-pack .pricing-type,
        .custom-pack .pricing-price,
        .custom-pack .pricing-description,
        .custom-pack .pricing-features li {
            color: var(--white);
        }

        .custom-pack .pricing-price {
            background: rgba(255,255,255,0.1);
        }

        .custom-pack .pricing-features i {
            color: var(--white);
        }

        .comparison-section {
            padding: 5rem 0;
        }

        .comparison-table-wrapper {
            overflow-x: auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }

        .comparison-table thead {
            background: var(--gradient);
            color: var(--white);
        }

        .comparison-table th {
            padding: 1.5rem;
            text-align: left;
            font-weight: 600;
        }

        .comparison-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .comparison-table tbody tr:hover {
            background: var(--gray-50);
        }

        .text-success {
            color: var(--success);
            font-size: 1.25rem;
        }

        .text-danger {
            color: var(--danger);
            font-size: 1.25rem;
        }

        .faq-pricing {
            padding: 5rem 0;
            background: var(--gray-50);
        }

        .faq-accordion {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item-accordion {
            background: var(--white);
            margin-bottom: 1rem;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .accordion-header {
            width: 100%;
            padding: 1.5rem;
            background: none;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--gray-900);
            transition: var(--transition);
            text-align: left;
        }

        .accordion-header:hover {
            background: var(--gray-50);
        }

        .accordion-header.active i {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion-content p {
            padding: 0 1.5rem 1.5rem;
            color: var(--gray-700);
            line-height: 1.7;
        }

        .guarantee-section {
            padding: 5rem 0;
        }

        .guarantee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .guarantee-item {
            text-align: center;
            padding: 2rem;
        }

        .guarantee-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: var(--white);
        }

        .guarantee-item h3 {
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .guarantee-item p {
            color: var(--gray-600);
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .pricing-card.featured {
                transform: scale(1);
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .comparison-table {
                font-size: 0.85rem;
            }

            .comparison-table th,
            .comparison-table td {
                padding: 0.75rem;
            }
        }
    </style>

    <script>
        // Accordion functionality
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isActive = this.classList.contains('active');
                
                // Close all accordions
                document.querySelectorAll('.accordion-header').forEach(h => {
                    h.classList.remove('active');
                    h.nextElementSibling.style.maxHeight = null;
                });
                
                // Open clicked accordion if it wasn't active
                if (!isActive) {
                    this.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>