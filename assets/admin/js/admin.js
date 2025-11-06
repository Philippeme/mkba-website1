/*
 * Point d'entrée Admin/Backend
 */

// Import des styles CSS admin
import '../styles/admin.css';
import '../styles/document.css';

// Import de Bootstrap si nécessaire
import '../../bootstrap';

// Import de vos modules JavaScript admin
import './dashboard.js';

console.log('Admin app initialized');

// Code d'initialisation admin
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready - Admin');
});
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const content = document.getElementById('adminContent');
    const sidebarSwitchBtn = document.getElementById('sidebarSwitchBtn');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    let isAutoMode = false; // Mode automatique avec souris
    let hoverTimeout;

    // Switch Button functionality
    if (sidebarSwitchBtn) {
        sidebarSwitchBtn.addEventListener('click', function() {
            this.classList.toggle('active');
            
            if (this.classList.contains('active')) {
                // Mode fixe étendu
                isAutoMode = false;
                sidebar.classList.remove('collapsed');
                content.classList.remove('collapsed');
                
                // Réafficher les chevrons
                document.querySelectorAll('.nav-arrow, .bi-chevron-down').forEach(chevron => {
                    chevron.style.opacity = '1';
                });
            } else {
                // Mode auto avec souris
                isAutoMode = true;
                sidebar.classList.add('collapsed');
                content.classList.add('collapsed');
                
                // Masquer les chevrons
                document.querySelectorAll('.nav-arrow, .bi-chevron-down').forEach(chevron => {
                    chevron.style.opacity = '0';
                });
                
                // Fermer tous les menus déroulants en mode collapsed
                document.querySelectorAll('.nav-item.dropdown.open').forEach(function(openItem) {
                    const openMenu = openItem.querySelector('.dropdown-menu');
                    const openChevron = openItem.querySelector('.bi-chevron-down, .nav-arrow');
                    
                    openItem.classList.remove('open');
                    openMenu.classList.remove('show');
                    
                    if (openChevron) {
                        openChevron.style.transform = 'rotate(-90deg)';
                    }
                });
            }
        });
    }

    // Mouse hover behavior when in auto mode
    if (sidebar) {
        sidebar.addEventListener('mouseenter', function() {
            if (isAutoMode) {
                clearTimeout(hoverTimeout);
                this.classList.remove('collapsed');
                content.classList.remove('collapsed');
                
                // Réafficher les chevrons temporairement
                document.querySelectorAll('.nav-arrow, .bi-chevron-down').forEach(chevron => {
                    chevron.style.opacity = '1';
                });
            }
        });

        sidebar.addEventListener('mouseleave', function() {
            if (isAutoMode) {
                hoverTimeout = setTimeout(() => {
                    this.classList.add('collapsed');
                    content.classList.add('collapsed');
                    
                    // Masquer les chevrons
                    document.querySelectorAll('.nav-arrow, .bi-chevron-down').forEach(chevron => {
                        chevron.style.opacity = '0';
                    });
                }, 300); // Délai de 300ms
            }
        });
    }

    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Fullscreen functionality
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    this.innerHTML = '<i class="fas fa-compress"></i>';
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().then(() => {
                        this.innerHTML = '<i class="fas fa-expand"></i>';
                    });
                }
            }
        });
    }

    // Handle fullscreen change events
    document.addEventListener('fullscreenchange', function() {
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        if (fullscreenBtn) {
            if (document.fullscreenElement) {
                fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
            } else {
                fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
            }
        }
    });

    // ========== GESTION AMÉLIORÉE DES SOUS-MENUS AVEC CHEVRONS HORIZONTAUX ==========
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle-custom');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parentLi = this.closest('.nav-item.dropdown');
            const dropdownMenu = parentLi.querySelector('.dropdown-menu');
            const chevron = this.querySelector('.bi-chevron-down, .nav-arrow');
            const isCurrentlyOpen = parentLi.classList.contains('open');
            
            // Fermer tous les autres accordéons
            document.querySelectorAll('.nav-item.dropdown.open').forEach(function(openItem) {
                if (openItem !== parentLi) {
                    const openMenu = openItem.querySelector('.dropdown-menu');
                    const openChevron = openItem.querySelector('.bi-chevron-down, .nav-arrow');
                    
                    openItem.classList.remove('open');
                    openMenu.classList.remove('show');
                    
                    // Réinitialiser l'animation du chevron
                    if (openChevron) {
                        openChevron.style.transform = 'rotate(-90deg)';
                    }
                }
            });
            
            // Toggle l'accordéon actuel avec animation
            if (isCurrentlyOpen) {
                // Fermer
                parentLi.classList.remove('open');
                dropdownMenu.classList.remove('show');
                
                if (chevron) {
                    chevron.style.transform = 'rotate(-90deg)';
                }
            } else {
                // Ouvrir
                parentLi.classList.add('open');
                
                // Animation d'ouverture
                setTimeout(() => {
                    dropdownMenu.classList.add('show');
                }, 50);
                
                if (chevron) {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
        }
    });

    // Search functionality
    const searchInput = document.getElementById('headerSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    console.log('Searching for:', query);
                }
            }
        });
    }

    // Language selector
    const languageItems = document.querySelectorAll('[data-lang]');
    languageItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.dataset.lang;
            const langDisplay = document.querySelector('#languageDropdown span');
            
            if (lang === 'fr') {
                langDisplay.textContent = 'FR';
            } else if (lang === 'en') {
                langDisplay.textContent = 'EN';
            }
            
            console.log('Language changed to:', lang);
        });
    });

    // Notification functionality
    const notificationBtn = document.getElementById('notificationBtn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            console.log('Notifications clicked');
        });
    }

    // Auto-dismiss flash messages after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.flash-message');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Table row hover effects
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'all 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Card hover effects
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'all 0.3s ease';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
    });

    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Form validation helpers
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                const firstInvalid = this.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    });

    // Loading states for buttons
    const loadingButtons = document.querySelectorAll('.btn[data-loading]');
    loadingButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (this.dataset.loading === 'true') {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            }
        });
    });

    // Initialize all popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Theme persistence
    const theme = localStorage.getItem('mkba-theme') || 'light';
    document.body.setAttribute('data-theme', theme);

    // ========== FONCTION POUR METTRE À JOUR LES MENUS ACTIFS AVEC CHEVRONS ==========
    function updateActiveMenusWithHorizontalChevrons() {
        const currentRoute = typeof window !== 'undefined' && window.location 
            ? window.location.pathname 
            : '';
        
        // Supprimer toutes les classes has-active-child existantes
        document.querySelectorAll('.nav-item.dropdown.has-active-child').forEach(item => {
            item.classList.remove('has-active-child');
        });
        
        // Logique de détection de menu actif basée sur l'URL
        const routeMenuMap = {
            '/admin/document': 'documents',
            '/admin/request': 'requests', 
            '/admin/procedure': 'procedures',
            '/admin/family': 'families',
            '/admin/user': 'users',
            '/admin/role': 'roles',
            '/admin/config': 'system',
            '/admin/settings': 'system'
        };
        
        let activeMenuFound = false;
        
        for (const [routePattern, menuDataAttribute] of Object.entries(routeMenuMap)) {
            if (currentRoute.includes(routePattern)) {
                const menuDropdown = document.querySelector(`[data-dropdown="${menuDataAttribute}"]`);
                if (menuDropdown) {
                    const parentDropdown = menuDropdown.closest('.nav-item.dropdown');
                    if (parentDropdown) {
                        parentDropdown.classList.add('has-active-child');
                        
                        // Auto-ouvrir le menu et ajuster le chevron
                        const dropdownMenu = parentDropdown.querySelector('.dropdown-menu');
                        const chevron = menuDropdown.querySelector('.bi-chevron-down, .nav-arrow');
                        
                        if (dropdownMenu) {
                            parentDropdown.classList.add('open');
                            dropdownMenu.classList.add('show');
                            
                            if (chevron) {
                                chevron.style.transform = 'rotate(0deg)';
                            }
                        }
                        activeMenuFound = true;
                        break;
                    }
                }
            }
        }
    }

    // Exécuter au chargement
    updateActiveMenusWithHorizontalChevrons();

    console.log('%c🚀 MK BA Admin Dashboard', 'color: #6d5192; font-size: 16px; font-weight: bold;');
    console.log('✅ Chevrons horizontaux initialisés');
});

// ========== UTILITY FUNCTIONS ==========
window.MKBAAdmin = {
    // Show notification
    showNotification: function(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show flash-message" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const flashContainer = document.querySelector('.flash-messages');
        if (flashContainer) {
            flashContainer.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const newAlert = flashContainer.lastElementChild;
                if (newAlert && newAlert.classList.contains('flash-message')) {
                    const bsAlert = new bootstrap.Alert(newAlert);
                    bsAlert.close();
                }
            }, 5000);
        } else {
            // Fallback: créer le container s'il n'existe pas
            const fallbackContainer = document.createElement('div');
            fallbackContainer.className = 'flash-messages';
            fallbackContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            fallbackContainer.innerHTML = alertHtml;
            document.body.appendChild(fallbackContainer);
            
            setTimeout(() => {
                fallbackContainer.remove();
            }, 5000);
        }
    },
    
    // Confirm dialog
    confirm: function(message, callback) {
        if (window.confirm(message)) {
            if (typeof callback === 'function') {
                callback();
            }
        }
    },
    
    // AJAX request helper
    request: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Request failed:', error);
                throw error;
            });
    },

    // Fonction pour basculer les chevrons
    toggleChevron: function(element, isOpen) {
        const chevron = element.querySelector('.bi-chevron-down, .nav-arrow');
        if (chevron) {
            chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
        }
    }
};

// ========== EVENT LISTENERS GLOBAUX ==========
// Gestionnaire global pour les touches de raccourci
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K pour ouvrir la recherche
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('headerSearch');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape pour fermer les dropdowns
    if (e.key === 'Escape') {
        // Fermer les dropdowns ouverts
        const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
        openDropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
            const parentItem = dropdown.closest('.nav-item.dropdown');
            if (parentItem) {
                parentItem.classList.remove('open');
                const chevron = parentItem.querySelector('.bi-chevron-down, .nav-arrow');
                if (chevron) {
                    chevron.style.transform = 'rotate(-90deg)';
                }
            }
        });
        
        // Vider les champs de recherche
        const searchInput = document.getElementById('headerSearch');
        if (searchInput && searchInput === document.activeElement) {
            searchInput.blur();
        }
    }
});

// Gestionnaire pour les liens de navigation avec animation
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[href]');
    if (link && link.href && !link.href.startsWith('#') && !link.target) {
        // Ajouter une classe de chargement au lien cliqué
        link.style.opacity = '0.7';
        link.style.transform = 'scale(0.98)';
        
        // Restaurer l'état après un court délai
        setTimeout(() => {
            link.style.opacity = '';
            link.style.transform = '';
        }, 200);
    }
});

// Performance monitoring (optionnel)
if (typeof performance !== 'undefined' && performance.mark) {
    performance.mark('mkba-admin-script-end');
    
    // Mesurer le temps de chargement du script
    if (performance.getEntriesByName('mkba-admin-script-start').length > 0) {
        performance.measure('mkba-admin-script-load', 'mkba-admin-script-start', 'mkba-admin-script-end');
        const measure = performance.getEntriesByName('mkba-admin-script-load')[0];
        console.log(`⚡ MK BA Admin script loaded in ${measure.duration.toFixed(2)}ms`);
    }
}