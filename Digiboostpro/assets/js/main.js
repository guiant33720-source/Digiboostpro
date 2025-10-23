// DigiboostPro v1 - JavaScript principal pour les pages publiques

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== Mobile Menu Toggle =====
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navActions = document.querySelector('.nav-actions');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            if (navActions) {
                navActions.classList.toggle('active');
            }
            
            // Changer l'icône
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // Fermer le menu mobile en cliquant sur un lien
    if (navMenu) {
        const menuLinks = navMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navMenu.classList.remove('active');
                    if (navActions) navActions.classList.remove('active');
                    const icon = mobileToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });
    }
    
    // ===== Smooth Scroll =====
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // ===== Sticky Header =====
    const header = document.querySelector('.header');
    if (header) {
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    }
    
    // ===== Form Validation =====
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    showError(input, 'Ce champ est requis');
                } else {
                    input.classList.remove('error');
                    removeError(input);
                }
                
                // Validation email
                if (input.type === 'email' && input.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value)) {
                        isValid = false;
                        input.classList.add('error');
                        showError(input, 'Email invalide');
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // ===== Animations au scroll =====
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const animateElements = document.querySelectorAll('.service-card, .testimonial-card, .news-card, .pricing-card');
    animateElements.forEach(el => {
        el.classList.add('animate-element');
        observer.observe(el);
    });
    
    // ===== Counter Animation =====
    const counters = document.querySelectorAll('.stat strong');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        if (!isNaN(target) && target < 10000) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, 30);
        }
    });
    
    // ===== Newsletter Form =====
    const newsletterForms = document.querySelectorAll('.newsletter-form, .newsletter-form-inline');
    newsletterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput && !emailInput.value) {
                e.preventDefault();
                alert('Veuillez entrer votre email');
            }
        });
    });
    
    // ===== Tooltips =====
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            if (title) {
                this.setAttribute('data-original-title', title);
                this.removeAttribute('title');
                
                const tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.textContent = title;
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.position = 'absolute';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
                tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                
                this._customTooltip = tooltip;
            }
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._customTooltip) {
                this._customTooltip.remove();
                this._customTooltip = null;
            }
            const originalTitle = this.getAttribute('data-original-title');
            if (originalTitle) {
                this.setAttribute('title', originalTitle);
                this.removeAttribute('data-original-title');
            }
        });
    });
    
    // ===== Lazy Loading Images =====
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
    
    // ===== Back to Top Button =====
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.setAttribute('aria-label', 'Retour en haut');
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // ===== Cookie Notice (RGPD) =====
    if (!localStorage.getItem('cookieAccepted')) {
        const cookieNotice = document.createElement('div');
        cookieNotice.className = 'cookie-notice';
        cookieNotice.innerHTML = `
            <div class="cookie-content">
                <p>
                    <i class="fas fa-cookie-bite"></i>
                    Nous utilisons des cookies pour améliorer votre expérience.
                </p>
                <button class="btn btn-primary btn-sm" id="acceptCookies">
                    J'accepte
                </button>
            </div>
        `;
        document.body.appendChild(cookieNotice);
        
        setTimeout(() => cookieNotice.classList.add('show'), 1000);
        
        document.getElementById('acceptCookies').addEventListener('click', function() {
            localStorage.setItem('cookieAccepted', 'true');
            cookieNotice.classList.remove('show');
            setTimeout(() => cookieNotice.remove(), 300);
        });
    }
    
});

// ===== Helper Functions =====
function showError(input, message) {
    let errorElement = input.parentElement.querySelector('.error-message');
    if (!errorElement) {
        errorElement = document.createElement('span');
        errorElement.className = 'error-message';
        input.parentElement.appendChild(errorElement);
    }
    errorElement.textContent = message;
}

function removeError(input) {
    const errorElement = input.parentElement.querySelector('.error-message');
    if (errorElement) {
        errorElement.remove();
    }
}

// ===== Styles additionnels pour les fonctionnalités JS =====
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    /* Mobile Menu */
    @media (max-width: 768px) {
        .nav-menu {
            position: fixed;
            top: 70px;
            left: -100%;
            width: 100%;
            background: var(--white);
            flex-direction: column;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            transition: left 0.3s ease;
            z-index: 999;
        }
        
        .nav-menu.active {
            left: 0;
        }
        
        .nav-actions {
            display: none;
            flex-direction: column;
            gap: 1rem;
            padding: 1rem 2rem;
        }
        
        .nav-actions.active {
            display: flex;
        }
    }
    
    /* Animations */
    .animate-element {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    
    .animate-element.animate-in {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Back to Top */
    .back-to-top {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: var(--shadow-lg);
    }
    
    .back-to-top.visible {
        opacity: 1;
        visibility: visible;
    }
    
    .back-to-top:hover {
        background: var(--primary-dark);
        transform: translateY(-5px);
    }
    
    /* Cookie Notice */
    .cookie-notice {
        position: fixed;
        bottom: -100px;
        left: 0;
        right: 0;
        background: var(--gray-900);
        color: var(--white);
        padding: 1.5rem;
        z-index: 9999;
        transition: bottom 0.3s ease;
        box-shadow: 0 -4px 6px rgba(0,0,0,0.1);
    }
    
    .cookie-notice.show {
        bottom: 0;
    }
    
    .cookie-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }
    
    .cookie-content p {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .cookie-content i {
        font-size: 1.5rem;
    }
    
    /* Custom Tooltip */
    .custom-tooltip {
        background: var(--gray-900);
        color: var(--white);
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius);
        font-size: 0.85rem;
        z-index: 9999;
        pointer-events: none;
        white-space: nowrap;
    }
    
    /* Error Messages */
    .error-message {
        display: block;
        color: var(--danger);
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }
    
    input.error,
    textarea.error,
    select.error {
        border-color: var(--danger) !important;
    }
    
    /* Sticky Header */
    .header.scrolled {
        box-shadow: var(--shadow-lg);
    }
`;
document.head.appendChild(additionalStyles);