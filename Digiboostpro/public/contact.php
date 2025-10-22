<?php
// DigiboostPro v1 - Page Contact
require_once '../config/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
            $error = 'Veuillez remplir tous les champs obligatoires';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide';
        } else {
            // Envoyer l'email (simulé)
            send_email(
                SITE_EMAIL,
                "Nouveau message de contact : $sujet",
                "De: $nom ($email)\nTéléphone: $telephone\n\nMessage:\n$message"
            );
            
            // Confirmation au client
            send_email(
                $email,
                "Votre message a été reçu - " . SITE_NAME,
                "Bonjour $nom,\n\nNous avons bien reçu votre message et nous vous répondrons dans les plus brefs délais.\n\nCordialement,\nL'équipe " . SITE_NAME
            );
            
            $success = 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.';
            
            // Réinitialiser les champs
            $_POST = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="tarifs.php">Tarifs</a></li>
                    <li><a href="actualites.php">Actualités</a></li>
                    <li><a href="contact.php" class="active">Contact</a></li>
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
                <h1>Contactez-nous</h1>
                <p>Notre équipe est à votre écoute pour répondre à toutes vos questions</p>
                <div class="breadcrumb">
                    <a href="index.php">Accueil</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Contact</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-form-wrapper">
                    <h2>Envoyez-nous un message</h2>
                    <p>Remplissez le formulaire ci-dessous et nous vous répondrons rapidement</p>

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

                    <form method="POST" action="" class="contact-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="nom">
                                    <i class="fas fa-user"></i>
                                    Nom complet *
                                </label>
                                <input 
                                    type="text" 
                                    id="nom" 
                                    name="nom" 
                                    class="form-control" 
                                    placeholder="Jean Dupont"
                                    value="<?php echo escape($_POST['nom'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email *
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-control" 
                                    placeholder="jean.dupont@exemple.com"
                                    value="<?php echo escape($_POST['email'] ?? ''); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="telephone">
                                    <i class="fas fa-phone"></i>
                                    Téléphone
                                </label>
                                <input 
                                    type="tel" 
                                    id="telephone" 
                                    name="telephone" 
                                    class="form-control" 
                                    placeholder="06 12 34 56 78"
                                    value="<?php echo escape($_POST['telephone'] ?? ''); ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="sujet">
                                    <i class="fas fa-tag"></i>
                                    Sujet *
                                </label>
                                <select id="sujet" name="sujet" class="form-control" required>
                                    <option value="">Sélectionnez un sujet</option>
                                    <option value="Demande d'information" <?php echo (($_POST['sujet'] ?? '') === 'Demande d\'information') ? 'selected' : ''; ?>>Demande d'information</option>
                                    <option value="Demande de devis" <?php echo (($_POST['sujet'] ?? '') === 'Demande de devis') ? 'selected' : ''; ?>>Demande de devis</option>
                                    <option value="Support technique" <?php echo (($_POST['sujet'] ?? '') === 'Support technique') ? 'selected' : ''; ?>>Support technique</option>
                                    <option value="Réclamation" <?php echo (($_POST['sujet'] ?? '') === 'Réclamation') ? 'selected' : ''; ?>>Réclamation</option>
                                    <option value="Partenariat" <?php echo (($_POST['sujet'] ?? '') === 'Partenariat') ? 'selected' : ''; ?>>Partenariat</option>
                                    <option value="Autre" <?php echo (($_POST['sujet'] ?? '') === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">
                                <i class="fas fa-comment"></i>
                                Message *
                            </label>
                            <textarea 
                                id="message" 
                                name="message" 
                                class="form-control" 
                                rows="6" 
                                placeholder="Décrivez votre demande..."
                                required
                            ><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-paper-plane"></i>
                            Envoyer le message
                        </button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div class="contact-info-wrapper">
                    <div class="contact-info-card">
                        <h3>Informations de contact</h3>
                        <p>N'hésitez pas à nous contacter par téléphone ou email</p>

                        <div class="contact-info-list">
                            <div class="contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <strong>Adresse</strong>
                                    <p>123 Avenue des Champs-Élysées<br>75008 Paris, France</p>
                                </div>
                            </div>

                            <div class="contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <strong>Téléphone</strong>
                                    <p>+33 1 23 45 67 89</p>
                                </div>
                            </div>

                            <div class="contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <strong>Email</strong>
                                    <p>contact@digiboostpro.fr</p>
                                </div>
                            </div>

                            <div class="contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <strong>Horaires</strong>
                                    <p>Lun - Ven : 9h00 - 18h00<br>Sam - Dim : Fermé</p>
                                </div>
                            </div>
                        </div>

                        <div class="contact-social">
                            <h4>Suivez-nous</h4>
                            <div class="social-links-large">
                                <a href="#" class="social-link">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="quick-links-card">
                        <h4>Liens rapides</h4>
                        <div class="quick-links-list">
                            <a href="faq.php" class="quick-link-item">
                                <i class="fas fa-question-circle"></i>
                                <span>FAQ - Questions fréquentes</span>
                            </a>
                            <a href="tarifs.php" class="quick-link-item">
                                <i class="fas fa-euro-sign"></i>
                                <span>Voir nos tarifs</span>
                            </a>
                            <a href="services.php" class="quick-link-item">
                                <i class="fas fa-list"></i>
                                <span>Nos services</span>
                            </a>
                            <a href="<?php echo is_logged_in() ? '../client/dashboard.php' : 'register.php'; ?>" class="quick-link-item">
                                <i class="fas fa-user-plus"></i>
                                <span><?php echo is_logged_in() ? 'Mon compte' : 'Créer un compte'; ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section (Optional) -->
    <section class="map-section">
        <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <p>Carte interactive disponible prochainement</p>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content">
                <div class="newsletter-text">
                    <h2>Restez informé</h2>
                    <p>Inscrivez-vous à notre newsletter pour recevoir nos actualités, conseils et offres exclusives</p>
                </div>
                <form class="newsletter-form-inline" method="POST" action="newsletter.php">
                    <input type="email" name="email" placeholder="Votre adresse email" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        S'inscrire
                    </button>
                </form>
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
        .contact-section {
            padding: 5rem 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 3rem;
        }

        .contact-form-wrapper h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .contact-form-wrapper > p {
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .contact-form {
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .contact-info-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .contact-info-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .contact-info-card > p {
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .contact-info-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-info-item {
            display: flex;
            gap: 1rem;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .contact-info-item strong {
            display: block;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .contact-info-item p {
            color: var(--gray-600);
            margin: 0;
            line-height: 1.6;
        }

        .contact-social {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }

        .contact-social h4 {
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .social-links-large {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            width: 50px;
            height: 50px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-700);
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--gradient);
            color: var(--white);
            transform: translateY(-3px);
        }

        .quick-links-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .quick-links-card h4 {
            margin-bottom: 1.5rem;
            color: var(--gray-900);
        }

        .quick-links-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .quick-link-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            color: var(--gray-700);
            transition: var(--transition);
        }

        .quick-link-item:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateX(5px);
        }

        .quick-link-item i {
            font-size: 1.25rem;
        }

        .map-section {
            height: 400px;
            background: var(--gray-200);
        }

        .map-placeholder {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
        }

        .map-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .newsletter-section {
            padding: 4rem 0;
            background: var(--gradient);
            color: var(--white);
        }

        .newsletter-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 3rem;
        }

        .newsletter-text h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .newsletter-text p {
            opacity: 0.95;
        }

        .newsletter-form-inline {
            display: flex;
            gap: 1rem;
            flex: 0 0 400px;
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

        @media (max-width: 1024px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }

            .newsletter-content {
                flex-direction: column;
                text-align: center;
            }

            .newsletter-form-inline {
                width: 100%;
                max-width: 500px;
            }
        }

        @media (max-width: 768px) {
            .form-row-2 {
                grid-template-columns: 1fr;
            }

            .contact-form {
                padding: 1.5rem;
            }

            .newsletter-form-inline {
                flex-direction: column;
            }

            .newsletter-form-inline button {
                width: 100%;
            }
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>