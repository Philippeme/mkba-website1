/**
 * sliders.js - Gestion des sliders du site OAG
 * Ce fichier contient la configuration et les contrôles pour tous les sliders du site
 */

/**
 * Initialisation de tous les sliders du site
 */
function initSliders() {
    // Initialiser chaque slider
    initMainBanner();
    initEchoSlider();
    initTopicsSlider();
    initExpertsSlider();
}

/**
 * Initialisation du slider de la bannière principale
 */
function initMainBanner() {
    const mainSlider = document.querySelector('.main-banner .slider');

    if (!mainSlider) return;

    // Configuration du slider avec Slick
    $(mainSlider).slick({
        dots: false,
        arrows: false,
        infinite: true,
        speed: 1000,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 5000,
        fade: true,
        cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
        pauseOnHover: false
    });

    // Mettre à jour les indicateurs après l'initialisation
    updateSliderDots(mainSlider, '.main-banner .slider-dots .dot');

    // Ajouter les contrôles personnalisés
    setupSliderControls(mainSlider, '.main-banner .prev', '.main-banner .next', '.main-banner .slider-dots .dot');
}

/**
 * Initialisation du slider Echo du terrain
 */
function initEchoSlider() {
    const echoSlider = document.querySelector('.echo-slider');

    if (!echoSlider) return;

    // Configuration du slider avec Slick
    $(echoSlider).slick({
        dots: false,
        arrows: false,
        infinite: true,
        speed: 800,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 6000,
        fade: true,
        cssEase: 'ease-in-out'
    });

    // Mettre à jour les indicateurs après l'initialisation
    updateSliderDots(echoSlider, '.echo-section .slider-dots .dot');

    // Ajouter les contrôles personnalisés
    setupSliderControls(echoSlider, '.echo-section .prev', '.echo-section .next', '.echo-section .slider-dots .dot');
}

/**
 * Initialisation du slider Pour aller plus loin
 */
function initTopicsSlider() {
    const topicsSlider = document.querySelector('.topics-slider');

    if (!topicsSlider) return;

    // Configuration du slider avec Slick
    $(topicsSlider).slick({
        dots: false,
        arrows: false,
        infinite: true,
        speed: 800,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 7000,
        fade: false,
        cssEase: 'ease'
    });

    // Mettre à jour les indicateurs après l'initialisation
    updateSliderDots(topicsSlider, '.more-section .slider-dots .dot');

    // Ajouter les contrôles personnalisés
    setupSliderControls(topicsSlider, '.more-section .prev', '.more-section .next', '.more-section .slider-dots .dot');
}

/**
 * Initialisation du slider Experts
 */
function initExpertsSlider() {
    const expertsSlider = document.querySelector('.experts-slider');

    if (!expertsSlider) return;

    // Configuration du slider avec Slick
    $(expertsSlider).slick({
        dots: false,
        arrows: false,
        infinite: true,
        speed: 800,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 8000,
        fade: false,
        cssEase: 'ease'
    });

    // Mettre à jour les indicateurs après l'initialisation
    updateSliderDots(expertsSlider, '.experts-section .slider-dots .dot');

    // Ajouter les contrôles personnalisés
    setupSliderControls(expertsSlider, '.experts-section .prev', '.experts-section .next', '.experts-section .slider-dots .dot');
}

/**
 * Configuration des contrôles personnalisés pour un slider
 * @param {HTMLElement} slider - L'élément du slider
 * @param {string} prevSelector - Sélecteur CSS pour le bouton précédent
 * @param {string} nextSelector - Sélecteur CSS pour le bouton suivant
 * @param {string} dotsSelector - Sélecteur CSS pour les points indicateurs
 */
function setupSliderControls(slider, prevSelector, nextSelector, dotsSelector) {
    const prevButton = document.querySelector(prevSelector);
    const nextButton = document.querySelector(nextSelector);
    const dots = document.querySelectorAll(dotsSelector);

    // Événement pour le bouton précédent
    if (prevButton) {
        prevButton.addEventListener('click', function () {
            $(slider).slick('slickPrev');
        });
    }

    // Événement pour le bouton suivant
    if (nextButton) {
        nextButton.addEventListener('click', function () {
            $(slider).slick('slickNext');
        });
    }

    // Événements pour les points indicateurs
    if (dots.length > 0) {
        dots.forEach((dot, index) => {
            dot.addEventListener('click', function () {
                $(slider).slick('slickGoTo', index);
            });
        });
    }

    // Mettre à jour les indicateurs lors du changement de slide
    $(slider).on('beforeChange', function (event, slick, currentSlide, nextSlide) {
        updateDotsActive(dots, nextSlide);
    });

    // Faire une pause au survol (optionnel)
    $(slider).on('mouseenter', function () {
        $(this).slick('slickPause');
    });

    $(slider).on('mouseleave', function () {
        $(this).slick('slickPlay');
    });
}

/**
 * Mettre à jour l'indicateur actif du slider
 * @param {NodeList} dots - Liste des points indicateurs
 * @param {number} activeIndex - Index du slide actif
 */
function updateDotsActive(dots, activeIndex) {
    dots.forEach((dot, index) => {
        if (index === activeIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

/**
 * Initialiser les points indicateurs en fonction du nombre de slides
 * @param {HTMLElement} slider - L'élément du slider
 * @param {string} dotsSelector - Sélecteur CSS pour les points indicateurs
 */
function updateSliderDots(slider, dotsSelector) {
    const dots = document.querySelectorAll(dotsSelector);
    const slideCount = $(slider).slick('getSlick').slideCount;

    // Ajuster le nombre de points pour correspondre au nombre de slides
    if (dots.length !== slideCount) {
        const dotsContainer = dots[0].parentNode;

        // Vider le conteneur
        dotsContainer.innerHTML = '';

        // Créer le bon nombre de points
        for (let i = 0; i < slideCount; i++) {
            const dot = document.createElement('span');
            dot.className = 'dot' + (i === 0 ? ' active' : '');

            // Ajouter l'événement de clic
            dot.addEventListener('click', function () {
                $(slider).slick('slickGoTo', i);
            });

            dotsContainer.appendChild(dot);
        }
    }
}

/**
 * Ajuster la hauteur du slider pour qu'elle corresponde au contenu
 * @param {HTMLElement} slider - L'élément du slider
 */
function adjustSliderHeight(slider) {
    // Obtenir la hauteur du slide actif
    const activeSlide = slider.querySelector('.slick-active');

    if (activeSlide) {
        const slideHeight = activeSlide.offsetHeight;
        slider.style.height = slideHeight + 'px';
    }
}

/**
 * Fonction utilitaire pour passer au slide suivant
 * @param {string} sliderSelector - Sélecteur CSS du slider
 */
function nextSlide(sliderSelector) {
    $(sliderSelector).slick('slickNext');
}

/**
 * Fonction utilitaire pour passer au slide précédent
 * @param {string} sliderSelector - Sélecteur CSS du slider
 */
function prevSlide(sliderSelector) {
    $(sliderSelector).slick('slickPrev');
}

/**
 * Fonction utilitaire pour aller à un slide spécifique
 * @param {string} sliderSelector - Sélecteur CSS du slider
 * @param {number} slideIndex - Index du slide cible
 */
function goToSlide(sliderSelector, slideIndex) {
    $(sliderSelector).slick('slickGoTo', slideIndex);
}

/**
 * Fonction utilitaire pour mettre en pause un slider
 * @param {string} sliderSelector - Sélecteur CSS du slider
 */
function pauseSlider(sliderSelector) {
    $(sliderSelector).slick('slickPause');
}

/**
 * Fonction utilitaire pour reprendre la lecture automatique d'un slider
 * @param {string} sliderSelector - Sélecteur CSS du slider
 */
function playSlider(sliderSelector) {
    $(sliderSelector).slick('slickPlay');
}

// Exposer les fonctions utilitaires publiquement
window.sliderUtils = {
    next: nextSlide,
    prev: prevSlide,
    goTo: goToSlide,
    pause: pauseSlider,
    play: playSlider
};

// Ajuster les hauteurs des sliders lors du redimensionnement de la fenêtre
window.addEventListener('resize', function () {
    const sliders = document.querySelectorAll('.slick-initialized');

    sliders.forEach(slider => {
        adjustSliderHeight(slider);
    });
});