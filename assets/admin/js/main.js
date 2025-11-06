/**
 * main.js - Script principal du site OAG
 * Gère les fonctionnalités globales du site, les évènements et animations
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function () {
    // Initialisation du site
    initSite();

    // Gestion des événements
    setupEventListeners();

    // Initialiser AOS (Animate On Scroll)
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 50
    });
});

/**
 * Initialisation globale du site
 */
function initSite() {
    // Initialiser les sliders (défini dans sliders.js)
    if (typeof initSliders === 'function') {
        initSliders();
    }

    // Initialiser la carte (défini dans map.js)
    if (typeof initMap === 'function') {
        initMap();
    }

    // Animation des statistiques
    initCounters();

    // Préchargement des images
    preloadImages();
}

/**
 * Mise en place des écouteurs d'événements
 */
function setupEventListeners() {
    // Gestion de l'en-tête lors du défilement
    window.addEventListener('scroll', handleScroll);

    // Gestion du menu mobile
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const closeMenu = document.querySelector('.close-menu');

    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function () {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêcher le défilement du corps
        });
    }

    if (closeMenu && mobileMenu) {
        closeMenu.addEventListener('click', function () {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = ''; // Rétablir le défilement du corps
        });
    }

    // Gestion des sous-menus mobiles
    const mobileSubmenus = document.querySelectorAll('.mobile-menu .has-submenu');

    mobileSubmenus.forEach(item => {
        item.addEventListener('click', function (e) {
            // Si on clique sur le lien parent et non sur un sous-menu
            if (e.target === this || e.target === this.querySelector('a')) {
                e.preventDefault();
                this.classList.toggle('active');
                const submenu = this.querySelector('.submenu');

                if (submenu) {
                    if (submenu.style.display === 'block') {
                        submenu.style.display = 'none';
                    } else {
                        submenu.style.display = 'block';
                    }
                }
            }
        });
    });

    // Gestion des menus déroulants desktop
    const dropdownItems = document.querySelectorAll('.has-dropdown');

    dropdownItems.forEach(item => {
        item.addEventListener('mouseenter', function () {
            this.classList.add('active');
        });

        item.addEventListener('mouseleave', function () {
            this.classList.remove('active');
        });
    });

    // Ajout de smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');

            if (targetId !== '#') {
                e.preventDefault();

                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Gestion du formulaire de filtre
    const filterForm = document.querySelector('.filter-panel');
    const resetButton = document.querySelector('.filter-panel .btn-block');

    if (filterForm && resetButton) {
        // Événement de réinitialisation
        resetButton.addEventListener('click', function (e) {
            e.preventDefault();

            // Réinitialiser tous les champs du formulaire
            const inputs = filterForm.querySelectorAll('input, select');

            inputs.forEach(input => {
                if (input.type === 'text') {
                    input.value = '';
                } else if (input.type === 'select-one') {
                    input.selectedIndex = 0;
                }
            });

            // Mettre à jour la carte (simulation)
            updateMapDisplay();
        });
    }
}

/**
 * Gestion du comportement de l'en-tête lors du défilement
 */
function handleScroll() {
    const header = document.querySelector('header');

    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }

    // Animation des éléments au défilement (fallback pour les navigateurs sans support AOS)
    animateOnScroll();
}

/**
 * Animation des compteurs dans les statistiques
 */
function initCounters() {
    const stats = document.querySelectorAll('.stat-number');
    const statsSection = document.querySelector('.stats-section');

    if (!stats.length || !statsSection) return;

    let animated = false;

    // Observer l'intersection avec la section de statistiques
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Ne déclencher l'animation qu'une seule fois
            if (entry.isIntersecting && !animated) {
                animated = true;

                stats.forEach(stat => {
                    const finalValue = parseInt(stat.textContent, 10);
                    animateCounter(stat, 0, finalValue, 2000);
                    stat.classList.add('animated');
                });

                // Arrêter d'observer après l'animation
                observer.unobserve(statsSection);
            }
        });
    }, { threshold: 0.5 });

    observer.observe(statsSection);
}

/**
 * Animation d'un compteur de statistique
 * @param {HTMLElement} element - Élément HTML contenant le nombre
 * @param {number} start - Valeur de départ
 * @param {number} end - Valeur finale
 * @param {number} duration - Durée de l'animation en millisecondes
 */
function animateCounter(element, start, end, duration) {
    const range = end - start;
    const increment = end > start ? 1 : -1;
    const stepTime = Math.abs(Math.floor(duration / range));
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        element.textContent = current;

        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            element.textContent = end;
            clearInterval(timer);
        }
    }, stepTime);
}

/**
 * Animation des éléments au défilement (fallback pour les navigateurs sans support AOS)
 */
function animateOnScroll() {
    // Ne s'exécute que si AOS n'est pas disponible
    if (typeof AOS !== 'undefined') return;

    const elements = document.querySelectorAll('.animated-item');

    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const windowHeight = window.innerHeight;

        if (elementTop < windowHeight - 50) {
            element.classList.add('visible');
        }
    });
}

/**
 * Préchargement des images pour de meilleures performances
 */
function preloadImages() {
    // Images principales à précharger
    const imagesToPreload = [
        'assets/images/slider/slide-1.png',
        'assets/images/slider/slide-2.jpg',
        'assets/images/slider/slide-3.jpg',
        'assets/images/logo/oag-logo.png'
    ];

    imagesToPreload.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

/**
 * Mise à jour de l'affichage de la carte (simulation)
 */
function updateMapDisplay() {
    // Simuler un chargement
    const worldMap = document.getElementById('world-map');

    if (worldMap) {
        worldMap.style.opacity = '0.5';

        setTimeout(() => {
            worldMap.style.opacity = '1';

            // Afficher un message de confirmation
            showNotification('Filtres réinitialisés avec succès');
        }, 500);
    }
}

/**
 * Affiche une notification à l'utilisateur
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de notification (success, error, info)
 */
function showNotification(message, type = 'success') {
    // Vérifier si une notification existe déjà
    let notification = document.querySelector('.notification');

    // Créer l'élément de notification s'il n'existe pas
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }

    // Ajouter la classe de type
    notification.className = 'notification ' + type;

    // Définir le message
    notification.textContent = message;

    // Afficher la notification
    notification.style.opacity = '1';
    notification.style.transform = 'translateY(0)';

    // Masquer la notification après un délai
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';

        // Supprimer l'élément après la transition
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

/**
 * Détection du support tactile
 * @returns {boolean} True si l'appareil est tactile
 */
function isTouchDevice() {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
}

// Ajouter une classe au body si l'appareil est tactile
if (isTouchDevice()) {
    document.body.classList.add('touch-device');
}

// CSS pour les notifications
const notificationStyles = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    background-color: #1a4b8f;
    color: white;
    border-radius: 4px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    opacity: 0;
    transform: translateY(-20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.notification.success {
    background-color: #28a745;
}

.notification.error {
    background-color: #dc3545;
}

.notification.info {
    background-color: #17a2b8;
}
`;

// Ajouter les styles de notification au document
const styleEl = document.createElement('style');
styleEl.textContent = notificationStyles;
document.head.appendChild(styleEl);