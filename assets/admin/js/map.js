/**
 * map.js - Gestion de la carte interactive du site OAG
 * Ce fichier contient toutes les fonctionnalités liées à la carte
 * et au système de filtrage associé - VERSION CORRIGÉE
 */

// Variable globale pour stocker l'instance de la carte
let worldMap;

// Données des pays selon la maquette (données mises à jour)
const countriesData = [
    {
        name: 'Cameroun',
        code: 'CM',
        coords: [7.3697, 12.3547],
        projects: 20,
        interveners: 70,
        highlighted: true,
        flag: 'images/flags/cameroon.png'
    },
    {
        name: 'Vietnam',
        code: 'VN',
        coords: [14.0583, 108.2772],
        projects: 18,
        interveners: 55,
        highlighted: true,
        flag: 'images/flags/vietnam.png'
    },
    {
        name: 'République Démocratique du Congo',
        code: 'CD',
        coords: [-4.0383, 21.7587],
        projects: 25,
        interveners: 80,
        highlighted: true,
        flag: 'images/flags/drc.png'
    },
    {
        name: 'Sénégal',
        code: 'SN',
        coords: [14.4974, -14.4524],
        projects: 12,
        interveners: 35,
        highlighted: true,
        flag: 'images/flags/senegal.png'
    },
    {
        name: 'Mali',
        code: 'ML',
        coords: [17.5707, -3.9962],
        projects: 8,
        interveners: 25,
        highlighted: true,
        flag: 'images/flags/mali.png'
    },
    {
        name: 'Bangladesh',
        code: 'BD',
        coords: [23.6850, 90.3563],
        projects: 15,
        interveners: 45,
        highlighted: true,
        flag: 'images/flags/bangladesh.png'
    },
    {
        name: 'Niger',
        code: 'NE',
        coords: [17.6078, 8.0817],
        projects: 6,
        interveners: 18,
        highlighted: true,
        flag: 'images/flags/niger.png'
    },
    {
        name: 'Tchad',
        code: 'TD',
        coords: [15.4542, 18.7322],
        projects: 10,
        interveners: 30,
        highlighted: true,
        flag: 'images/flags/tchad.png'
    }
];

// Données des projets mises à jour selon la maquette
const projectsData = [
    {
        name: 'Projet de santé communautaire',
        country: 'Cameroun',
        coords: [4.0511, 9.7679], // Douala, Cameroun
        type: 'Santé',
        year: 2024,
        donor: 'OMS',
        sector: 'Public',
        description: 'Programme d\'amélioration de l\'accès aux soins de santé primaires'
    },
    {
        name: 'Initiative d\'éducation rurale',
        country: 'Cameroun',
        coords: [9.3051, 12.3605], // Nord Cameroun
        type: 'Education',
        year: 2023,
        donor: 'UNICEF',
        sector: 'Public',
        description: 'Renforcement du système éducatif en zones rurales'
    },
    {
        name: 'Programme d\'accès à l\'eau potable',
        country: 'Cameroun',
        coords: [10.6418, 14.3954], // Extrême-Nord Cameroun
        type: 'Eau et assainissement',
        year: 2025,
        donor: 'UE',
        sector: 'Public',
        description: 'Construction de forages et systèmes d\'adduction d\'eau'
    },
    {
        name: 'Renforcement des capacités agricoles',
        country: 'Vietnam',
        coords: [10.8231, 106.6297], // Ho Chi Minh Ville
        type: 'Agriculture',
        year: 2024,
        donor: 'FAO',
        sector: 'Public',
        description: 'Formation des agriculteurs aux techniques modernes'
    },
    {
        name: 'Programme de microfinance',
        country: 'Vietnam',
        coords: [21.0285, 105.8542], // Hanoï
        type: 'Finance',
        year: 2023,
        donor: 'Banque Mondiale',
        sector: 'Privé',
        description: 'Accès au crédit pour les petites entreprises'
    },
    {
        name: 'Protection de la biodiversité',
        country: 'République Démocratique du Congo',
        coords: [-4.3217, 15.3139], // Kinshasa
        type: 'Environnement',
        year: 2025,
        donor: 'PNUD',
        sector: 'ONG',
        description: 'Conservation des écosystèmes forestiers'
    },
    {
        name: 'Projet anti-braconnage',
        country: 'République Démocratique du Congo',
        coords: [-1.2921, 29.2347], // Zone des Grands Lacs
        type: 'Environnement',
        year: 2024,
        donor: 'WWF',
        sector: 'ONG',
        description: 'Lutte contre le braconnage et protection de la faune'
    },
    {
        name: 'Droits des enfants et éducation',
        country: 'Sénégal',
        coords: [14.7645, -17.3660], // Dakar
        type: 'Droits humains',
        year: 2023,
        donor: 'UNICEF',
        sector: 'Public',
        description: 'Protection et éducation des enfants vulnérables'
    },
    {
        name: 'Développement rural intégré',
        country: 'Mali',
        coords: [12.6392, -8.0029], // Bamako
        type: 'Agriculture',
        year: 2024,
        donor: 'AFD',
        sector: 'Public',
        description: 'Amélioration des conditions de vie en milieu rural'
    },
    {
        name: 'Gestion des ressources en eau',
        country: 'Niger',
        coords: [13.5137, 2.1098], // Niamey
        type: 'Eau et assainissement',
        year: 2025,
        donor: 'UE',
        sector: 'Public',
        description: 'Gestion durable des ressources hydriques'
    },
    {
        name: 'Programme de nutrition',
        country: 'Tchad',
        coords: [12.1348, 15.0557], // N\'Djamena
        type: 'Santé',
        year: 2024,
        donor: 'PAM',
        sector: 'Public',
        description: 'Lutte contre la malnutrition infantile'
    },
    {
        name: 'Résilience climatique agricole',
        country: 'Bangladesh',
        coords: [23.8103, 90.4125], // Dhaka
        type: 'Agriculture',
        year: 2023,
        donor: 'FIDA',
        sector: 'Public',
        description: 'Adaptation de l\'agriculture au changement climatique'
    },
    {
        name: 'Autonomisation des femmes',
        country: 'Bangladesh',
        coords: [22.3569, 91.7832], // Chittagong
        type: 'Droits humains',
        year: 2024,
        donor: 'ONU Femmes',
        sector: 'ONG',
        description: 'Renforcement de l\'autonomie économique des femmes'
    }
];

/**
 * Initialisation de la carte
 */
function initMap() {
    const mapContainer = document.getElementById('world-map');

    if (!mapContainer) return;

    // Créer la carte Leaflet avec un centrage optimisé pour l'Afrique
    // CORRECTION: Désactiver le contrôle de zoom par défaut
    worldMap = L.map(mapContainer, {
        center: [15, 15], // Centré sur l'Afrique
        zoom: 3,
        minZoom: 2,
        maxZoom: 8,
        scrollWheelZoom: true,
        zoomControl: false // IMPORTANT: Désactiver le contrôle par défaut
    });

    // CORRECTION: Ajouter UN SEUL contrôle de zoom en haut à droite
    L.control.zoom({
        position: 'topright'
    }).addTo(worldMap);

    // Utiliser une couche de tuiles claire et moderne
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '', // Attribution supprimée comme demandé
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(worldMap);

    // Supprimer le contrôle d'attribution par défaut
    worldMap.attributionControl.setPrefix('');

    // Ajouter les marqueurs des pays
    addCountryMarkers();

    // Ajouter les marqueurs des projets
    addProjectMarkers();

    // Configurer les filtres
    setupFilters();

    // Ajuster la taille de la carte lors du redimensionnement de la fenêtre
    window.addEventListener('resize', function () {
        setTimeout(() => {
            worldMap.invalidateSize();
        }, 100);
    });

    // Cacher le popup au clic sur la carte
    worldMap.on('click', function () {
        hideCountryPopup();
    });
}

/**
 * Ajouter les marqueurs des pays sur la carte selon la maquette
 */
function addCountryMarkers() {
    countriesData.forEach(country => {
        if (country.highlighted) {
            // Créer un cercle avec la couleur orange de la maquette
            const circle = L.circle(country.coords, {
                color: '#f18221',
                fillColor: '#f18221',
                fillOpacity: 0.7,
                radius: 400000, // Ajusté pour une meilleure visibilité
                weight: 2
            }).addTo(worldMap);

            // MODIFICATION: Utiliser le popup amélioré pour les pays
            circle.on('click', function (e) {
                e.originalEvent.stopPropagation();
                showCountryInfoInMap(country, e.latlng);
            });

            circle.on('mouseover', function () {
                this.setStyle({
                    fillOpacity: 0.9,
                    radius: 450000
                });
            });

            circle.on('mouseout', function () {
                this.setStyle({
                    fillOpacity: 0.7,
                    radius: 400000
                });
            });

            // Stocker une référence au cercle dans les données du pays
            country.marker = circle;
        }
    });
}

/**
 * NOUVELLE FONCTION: Afficher les informations d'un pays dans le popup de la carte
 * @param {Object} country - Données du pays
 * @param {Object} latlng - Coordonnées du clic
 */
function showCountryInfoInMap(country, latlng) {
    const popup = document.getElementById('map-popup');

    if (!popup) return;

    // Calculer le nombre total de projets et d'intervenants pour ce pays
    const countryProjects = projectsData.filter(project => project.country === country.name);
    const totalProjects = countryProjects.length || country.projects;

    // Calculer le nombre total d'intervenants (estimation basée sur les projets)
    const totalInterveners = country.interveners || (totalProjects * 3.5); // Estimation

    // Mettre à jour le contenu du popup avec le format de la maquette
    popup.innerHTML = `
        <div class="country-flag">
            <img src="${country.flag}" alt="${country.name}" onerror="this.style.display='none'">
            <span>${country.name}</span>
        </div>
        <div class="country-stats">
            <div class="stat">Projets : <span>${totalProjects}</span></div>
            <div class="stat">Intervenants : <span>${Math.round(totalInterveners)}</span></div>
        </div>
    `;

    // Positionner le popup en fonction des coordonnées du clic
    const point = worldMap.latLngToContainerPoint(latlng);
    const mapRect = document.getElementById('world-map').getBoundingClientRect();

    popup.style.display = 'block';
    popup.style.top = (point.y - 40) + 'px';
    popup.style.left = (point.x + 20) + 'px';
    popup.classList.add('active');

    // Ajuster la position si le popup sort de la carte
    setTimeout(() => {
        const popupRect = popup.getBoundingClientRect();

        if (popupRect.right > mapRect.right) {
            popup.style.left = (point.x - popupRect.width - 20) + 'px';
        }

        if (popupRect.bottom > mapRect.bottom) {
            popup.style.top = (point.y - popupRect.height - 20) + 'px';
        }

        if (popupRect.left < mapRect.left) {
            popup.style.left = '10px';
        }

        if (popupRect.top < mapRect.top) {
            popup.style.top = '10px';
        }
    }, 10);
}

/**
 * Ajouter les marqueurs des projets sur la carte
 */
function addProjectMarkers() {
    // Créer une icône personnalisée pour les projets
    const projectIcon = L.divIcon({
        className: 'project-marker',
        html: '<div class="marker-icon"><i class="fas fa-map-marker-alt"></i></div>',
        iconSize: [25, 25],
        iconAnchor: [12, 25]
    });

    // Parcourir les données des projets
    projectsData.forEach(project => {
        // Créer un marqueur pour le projet
        const marker = L.marker(project.coords, {
            icon: projectIcon
        }).addTo(worldMap);

        // MODIFICATION: Améliorer le contenu de la popup pour inclure toutes les informations
        const popupContent = `
            <div class="project-popup">
                <h3>${project.name}</h3>
                <div class="project-details">
                    <p><strong>Pays:</strong> ${project.country}</p>
                    <p><strong>Secteur:</strong> ${project.type}</p>
                    <p><strong>Année:</strong> ${project.year}</p>
                    <p><strong>Bailleur:</strong> ${project.donor}</p>
                    <p><strong>Type:</strong> ${project.sector}</p>
                    <p class="project-desc">${project.description}</p>
                </div>
            </div>
        `;

        marker.bindPopup(popupContent, {
            maxWidth: 300,
            closeButton: true,
            className: 'custom-popup'
        });

        // Stocker une référence au marqueur dans les données du projet
        project.marker = marker;
    });

    // Ajouter du CSS personnalisé pour les marqueurs
    addProjectMarkerStyles();
}

/**
 * Ajouter les styles pour les marqueurs de projets et les popups améliorés
 */
function addProjectMarkerStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .project-marker {
            background: transparent;
        }
        
        .marker-icon {
            color: #1a4b8f;
            font-size: 22px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease, color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 25px;
            height: 25px;
        }
        
        .marker-icon:hover {
            color: #f18221;
            transform: scale(1.3);
        }
        
        .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            font-family: 'Roboto', sans-serif;
        }
        
        .custom-popup .leaflet-popup-tip {
            background: white;
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.15);
        }
        
        .project-popup {
            font-family: 'Roboto', sans-serif;
        }
        
        .project-popup h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #1a4b8f;
            font-weight: 600;
            line-height: 1.3;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
        }
        
        .project-details p {
            margin: 8px 0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .project-details strong {
            color: #333;
            font-weight: 600;
            display: inline-block;
            min-width: 70px;
        }
        
        .project-desc {
            margin-top: 12px !important;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-style: italic;
            color: #666;
            font-size: 12px !important;
        }
        
        .leaflet-popup-content {
            margin: 15px 18px;
        }
        
        .leaflet-popup-content-wrapper {
            border-radius: 10px;
        }
        
        /* Amélioration du style du contrôle de zoom */
        .leaflet-control-zoom {
            border: none !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2) !important;
            border-radius: 8px !important;
            overflow: hidden;
        }
        
        .leaflet-control-zoom a {
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
            color: #1a4b8f !important;
            background: white !important;
            transition: all 0.3s ease !important;
        }
        
        .leaflet-control-zoom a:hover {
            background: #f18221 !important;
            color: white !important;
        }
        
        .leaflet-control-zoom-in {
            border-bottom: 1px solid #ddd !important;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Masquer la popup d'information du pays
 */
function hideCountryPopup() {
    const popup = document.getElementById('map-popup');
    if (popup) {
        popup.style.display = 'none';
        popup.classList.remove('active');
    }
}

/**
 * Configuration du système de filtrage amélioré
 */
function setupFilters() {
    // Obtenir les éléments de filtre
    const countryFilter = document.getElementById('pays');
    const yearFilter = document.getElementById('annee');
    const domainFilter = document.getElementById('domaine');
    const donorFilter = document.getElementById('bailleur');
    const sectorFilter = document.getElementById('secteur');
    const resetButton = document.querySelector('.filter-panel .btn-block');

    // Remplir automatiquement les options des filtres
    populateFilterOptions();

    // Ajouter des événements pour les filtres
    const applyFilters = debounce(() => {
        const filters = {
            country: countryFilter && countryFilter.value ? countryFilter.value.toLowerCase() : null,
            year: yearFilter && yearFilter.value ? parseInt(yearFilter.value) : null,
            domain: domainFilter && domainFilter.value ? domainFilter.value.toLowerCase() : null,
            donor: donorFilter && donorFilter.value ? donorFilter.value.toLowerCase() : null,
            sector: sectorFilter && sectorFilter.value ? sectorFilter.value.toLowerCase() : null
        };

        filterProjects(filters);
    }, 300);

    // Ajouter les événements de changement
    if (countryFilter) countryFilter.addEventListener('input', applyFilters);
    if (yearFilter) yearFilter.addEventListener('change', applyFilters);
    if (domainFilter) domainFilter.addEventListener('change', applyFilters);
    if (donorFilter) donorFilter.addEventListener('input', applyFilters);
    if (sectorFilter) sectorFilter.addEventListener('change', applyFilters);

    // Événement pour la réinitialisation
    if (resetButton) {
        resetButton.addEventListener('click', function (e) {
            e.preventDefault();
            resetAllFilters();
        });
    }
}

/**
 * Remplir automatiquement les options des filtres
 */
function populateFilterOptions() {
    const yearFilter = document.getElementById('annee');
    const domainFilter = document.getElementById('domaine');
    const sectorFilter = document.getElementById('secteur');

    // Années uniques
    if (yearFilter) {
        const years = [...new Set(projectsData.map(project => project.year))].sort((a, b) => b - a);
        years.forEach(year => {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearFilter.appendChild(option);
        });
    }

    // Domaines uniques
    if (domainFilter) {
        const domains = [...new Set(projectsData.map(project => project.type))].sort();
        domains.forEach(domain => {
            const option = document.createElement('option');
            option.value = domain.toLowerCase().replace(/\s+/g, '-');
            option.textContent = domain;
            domainFilter.appendChild(option);
        });
    }

    // Secteurs uniques
    if (sectorFilter) {
        const sectors = [...new Set(projectsData.map(project => project.sector))].sort();
        sectors.forEach(sector => {
            const option = document.createElement('option');
            option.value = sector.toLowerCase();
            option.textContent = sector;
            sectorFilter.appendChild(option);
        });
    }
}

/**
 * Filtrer les projets en fonction des critères
 * @param {Object} filters - Critères de filtrage
 */
function filterProjects(filters) {
    hideCountryPopup();

    let visibleProjectsCount = 0;
    const countryProjectCounts = {};

    // Filtrer les projets
    projectsData.forEach(project => {
        let visible = true;

        // Appliquer les filtres
        if (filters.country && !project.country.toLowerCase().includes(filters.country)) {
            visible = false;
        }

        if (filters.year && project.year !== filters.year) {
            visible = false;
        }

        if (filters.domain && !project.type.toLowerCase().replace(/\s+/g, '-').includes(filters.domain)) {
            visible = false;
        }

        if (filters.donor && !project.donor.toLowerCase().includes(filters.donor)) {
            visible = false;
        }

        if (filters.sector && !project.sector.toLowerCase().includes(filters.sector)) {
            visible = false;
        }

        // Appliquer la visibilité au marqueur
        if (project.marker) {
            if (visible) {
                if (!worldMap.hasLayer(project.marker)) {
                    worldMap.addLayer(project.marker);
                }
                visibleProjectsCount++;

                // Compter les projets par pays
                if (!countryProjectCounts[project.country]) {
                    countryProjectCounts[project.country] = 0;
                }
                countryProjectCounts[project.country]++;
            } else {
                if (worldMap.hasLayer(project.marker)) {
                    worldMap.removeLayer(project.marker);
                }
            }
        }
    });

    // Filtrer les pays
    countriesData.forEach(country => {
        let visible = true;

        // Un pays est visible s'il correspond au filtre du pays
        if (filters.country && !country.name.toLowerCase().includes(filters.country)) {
            visible = false;
        }

        // Et s'il a des projets visibles
        const hasVisibleProjects = countryProjectCounts[country.name] > 0;

        visible = visible && (hasVisibleProjects || !Object.values(filters).some(f => f !== null));

        // Appliquer la visibilité au marqueur du pays
        if (country.marker) {
            if (visible) {
                if (!worldMap.hasLayer(country.marker)) {
                    worldMap.addLayer(country.marker);
                }
                // Ajuster l'opacité en fonction du nombre de projets
                const projectCount = countryProjectCounts[country.name] || 0;
                const opacity = Math.min(0.9, 0.5 + (projectCount * 0.1));
                country.marker.setStyle({
                    fillOpacity: opacity
                });
            } else {
                if (worldMap.hasLayer(country.marker)) {
                    worldMap.removeLayer(country.marker);
                }
            }
        }
    });

    // Afficher le nombre de résultats avec animation
    updateResultsCounter(visibleProjectsCount);
}

/**
 * Réinitialiser tous les filtres
 */
function resetAllFilters() {
    // Réinitialiser les champs
    const countryFilter = document.getElementById('pays');
    const yearFilter = document.getElementById('annee');
    const domainFilter = document.getElementById('domaine');
    const donorFilter = document.getElementById('bailleur');
    const sectorFilter = document.getElementById('secteur');

    if (countryFilter) countryFilter.value = '';
    if (yearFilter) yearFilter.selectedIndex = 0;
    if (domainFilter) domainFilter.selectedIndex = 0;
    if (donorFilter) donorFilter.value = '';
    if (sectorFilter) sectorFilter.selectedIndex = 0;

    hideCountryPopup();

    // Réinitialiser la visibilité des projets
    projectsData.forEach(project => {
        if (project.marker && !worldMap.hasLayer(project.marker)) {
            worldMap.addLayer(project.marker);
        }
    });

    // Réinitialiser la visibilité des pays
    countriesData.forEach(country => {
        if (country.marker) {
            if (!worldMap.hasLayer(country.marker)) {
                worldMap.addLayer(country.marker);
            }
            country.marker.setStyle({
                fillOpacity: 0.7
            });
        }
    });

    updateResultsCounter(projectsData.length);

    // Afficher une notification de réinitialisation
    if (typeof showNotification === 'function') {
        showNotification('Filtres réinitialisés avec succès');
    }
}

/**
 * Mettre à jour le compteur de résultats avec animation
 * @param {number} count - Nombre de projets visibles
 */
function updateResultsCounter(count) {
    // Création d'un élément de notification temporaire
    const notification = document.createElement('div');
    notification.className = 'map-results-notification';
    notification.textContent = `${count} projet${count > 1 ? 's' : ''} affiché${count > 1 ? 's' : ''} sur la carte`;

    const mapContainer = document.getElementById('world-map');
    if (mapContainer) {
        mapContainer.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Suppression après 3 secondes
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    console.log(`${count} projet(s) affiché(s) sur la carte`);
}

/**
 * Fonction de debounce pour limiter les appels de filtrage
 * @param {Function} func - Fonction à débouncer
 * @param {number} wait - Délai d'attente en millisecondes
 * @returns {Function} - Fonction débouncée
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Mettre à jour la carte en fonction des données actuelles
 * Fonction utilitaire pour les mises à jour externes
 */
function updateMap() {
    if (worldMap) {
        worldMap.invalidateSize();
    }
}

/**
 * Ajouter des styles CSS pour la notification de résultats
 */
(function addResultsNotificationStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .map-results-notification {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: rgba(26, 75, 143, 0.95);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .map-results-notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    `;
    document.head.appendChild(style);
})();

// Exposer les fonctions publiquement
window.mapUtils = {
    update: updateMap,
    resetFilters: resetAllFilters,
    hidePopup: hideCountryPopup
};