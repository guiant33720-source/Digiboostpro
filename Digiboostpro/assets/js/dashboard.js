// DigiboostPro v1 - JavaScript Dashboard

document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Fermer le menu en cliquant sur un lien (mobile)
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('show');
                }
            });
        });
        
        // Fermer le menu en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
    
    // Marquer les notifications comme lues au clic
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            // Ici on pourrait faire un appel AJAX pour marquer comme lu
            this.style.opacity = '0.6';
        });
    });
    
    // Confirmation de suppression
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-refresh pour les commandes en cours (toutes les 30 secondes)
    const commandesEnCours = document.querySelector('.commandes-en-cours');
    if (commandesEnCours) {
        setInterval(function() {
            // Ici on pourrait faire un appel AJAX pour rafraîchir les données
            console.log('Refresh des commandes en cours');
        }, 30000);
    }
    
    // Filtres de tableau
    const tableFilters = document.querySelectorAll('.table-filter');
    tableFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            const table = document.querySelector(this.getAttribute('data-table'));
            const filterValue = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let match = false;
                
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filterValue)) {
                        match = true;
                    }
                });
                
                row.style.display = match || filterValue === '' ? '' : 'none';
            });
        });
    });
    
    // Recherche dans les tableaux
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.querySelector(this.getAttribute('data-table'));
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    });
    
    // Copier dans le presse-papiers
    const copyButtons = document.querySelectorAll('[data-copy]');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const text = this.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(() => {
                // Afficher un feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copié !';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });
    
    // Animation des progress bars
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
    
    // Tooltips (simple implementation)
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.position = 'absolute';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
    
    // Drag & Drop pour upload de fichiers
    const dropZones = document.querySelectorAll('.drop-zone');
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        zone.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            const input = this.querySelector('input[type="file"]');
            if (input && files.length > 0) {
                input.files = files;
                // Déclencher l'événement change
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
    });
    
    // Preview d'image avant upload
    const imageInputs = document.querySelectorAll('input[type="file"][data-preview]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Accordion
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isActive = this.classList.contains('active');
            
            // Fermer tous les autres accordions dans le même groupe
            const parent = this.closest('.accordion');
            if (parent) {
                parent.querySelectorAll('.accordion-header').forEach(h => {
                    h.classList.remove('active');
                    h.nextElementSibling.style.maxHeight = null;
                });
            }
            
            // Toggle l'accordion actuel
            if (!isActive) {
                this.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
    });
    
    // Tabs
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabGroup = this.closest('.tabs');
            const tabId = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabGroup.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            tabGroup.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activer l'onglet sélectionné
            this.classList.add('active');
            const content = document.getElementById(tabId);
            if (content) {
                content.classList.add('active');
            }
        });
    });
    
    // Auto-save pour les formulaires
    const autoSaveForms = document.querySelectorAll('[data-autosave]');
    autoSaveForms.forEach(form => {
        let timeout;
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    // Ici on pourrait faire un appel AJAX pour sauvegarder
                    console.log('Auto-save triggered');
                    showNotification('Modifications enregistrées', 'success');
                }, 2000);
            });
        });
    });
    
    // Character counter pour les textareas
    const textareas = document.querySelectorAll('textarea[data-maxlength]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.getAttribute('data-maxlength'));
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${remaining} caractères restants`;
            counter.style.color = remaining < 50 ? 'var(--danger)' : 'var(--gray-500)';
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
    
});

// Fonction pour afficher des notifications toast
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `toast-notification toast-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Fonction pour formater les dates
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('fr-FR', options);
}

// Fonction pour formater les prix
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price);
}

// Style pour les notifications toast
const style = document.createElement('style');
style.textContent = `
    .toast-notification {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: var(--white);
        padding: 1rem 1.5rem;
        border-radius: var(--radius);
        box-shadow: var(--shadow-xl);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 9999;
        max-width: 400px;
    }
    
    .toast-notification.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .toast-success { border-left: 4px solid var(--success); }
    .toast-error { border-left: 4px solid var(--danger); }
    .toast-info { border-left: 4px solid var(--primary); }
    
    .toast-success i { color: var(--success); }
    .toast-error i { color: var(--danger); }
    .toast-info i { color: var(--primary); }
    
    .char-counter {
        font-size: 0.85rem;
        margin-top: 0.25rem;
        text-align: right;
    }
    
    .tooltip {
        background: var(--gray-900);
        color: var(--white);
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius);
        font-size: 0.85rem;
        z-index: 9999;
        pointer-events: none;
    }
    
    .drop-zone.drag-over {
        border-color: var(--primary);
        background: var(--gray-50);
    }
`;
document.head.appendChild(style);