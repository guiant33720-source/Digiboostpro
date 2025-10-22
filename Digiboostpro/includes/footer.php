<?php
// DigiboostPro v1 - Footer réutilisable
// À inclure dans toutes les pages publiques avec: include '../includes/footer.php';
?>
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
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
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