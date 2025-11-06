/**
 * MK Gov Dashboard JavaScript - Enhanced with fixes and Guinea-Bissau map
 * Corrections: Auto-refresh issues, exports, interactive map
 */

class MKGovDashboard {
    constructor() {
        this.charts = {};
        this.map = null;
        this.mapMarkers = [];
        this.autoRefreshInterval = null;
        this.autoRefreshEnabled = false;
        this.currentFilters = {};
        this.lastStatsSnapshot = null; // To prevent negative decrements
        
        this.init();
    }

    init() {
        this.initCharts();
        this.initMap();
        this.initEventListeners();
        this.loadInitialData();
        console.log('MK Gov Dashboard initialized successfully');
    }

    /**
     * Initialize all charts
     */
    initCharts() {
        this.initDonutChart();
        this.initBarChart();
        this.initLineChart();
    }

    /**
     * Initialize donut chart for requests by family
     */
    initDonutChart() {
        const ctx = document.getElementById('donutChart');
        if (!ctx) return;

        const data = window.dashboardData?.requestsByFamily || [];
        
        this.charts.donut = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.family_name),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: [
                        '#1a4b8f', '#f18221', '#28a745', '#dc3545', 
                        '#ffc107', '#17a2b8', '#e83e8c', '#6f42c1'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = data[context.dataIndex];
                                return `${context.label}: ${context.parsed} requests (${item.percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize bar chart for requests by gender
     */
    initBarChart() {
        const ctx = document.getElementById('barChart');
        if (!ctx) return;

        const data = window.dashboardData?.requestsByGender || {};
        
        this.charts.bar = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || ['Male', 'Female'],
                datasets: [{
                    label: 'Requests',
                    data: data.data || [0, 0],
                    backgroundColor: ['#1a4b8f', '#f18221'],
                    borderColor: ['#1a4b8f', '#f18221'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const percentages = data.percentages || {};
                                const gender = context.label.toLowerCase();
                                const percentage = percentages[gender] || 0;
                                return `${context.label}: ${context.parsed.y} requests (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize line chart for monthly requests
     */
    initLineChart() {
        const ctx = document.getElementById('lineChart');
        if (!ctx) return;

        const data = window.dashboardData?.monthlyRequests || {};
        
        this.charts.line = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Requests',
                    data: data.data || [],
                    borderColor: '#1a4b8f',
                    backgroundColor: 'rgba(26, 75, 143, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1a4b8f',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    /**
     * Initialize interactive Guinea-Bissau map
     */
    initMap() {
        const mapContainer = document.getElementById('guineaBissauMap');
        if (!mapContainer) return;

        // Initialize Leaflet map centered on Guinea-Bissau
        this.map = L.map('guineaBissauMap').setView([11.8636, -15.5986], 7);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18,
            minZoom: 6
        }).addTo(this.map);

        // Set bounds to limit panning to Guinea-Bissau area
        const bounds = L.latLngBounds(
            L.latLng(10.5, -17.0), // Southwest coordinates
            L.latLng(12.7, -13.0)  // Northeast coordinates
        );
        this.map.setMaxBounds(bounds);

        // Add region markers
        this.updateMapMarkers();

        // Add map controls
        this.addMapControls();
    }

    /**
     * Update map markers with current data - CORRIGÉ avec popups au survol
     */
    updateMapMarkers() {
        // Clear existing markers
        this.mapMarkers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.mapMarkers = [];

        const locationData = window.dashboardData?.requestsByLocation || [];

        locationData.forEach(location => {
            // Create custom icon based on request count
            const iconSize = Math.max(20, Math.min(50, location.count * 0.5 + 20));
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `
                    <div style="
                        background: ${location.color || '#1a4b8f'};
                        width: ${iconSize}px;
                        height: ${iconSize}px;
                        border-radius: 50%;
                        border: 3px solid white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-weight: bold;
                        font-size: ${iconSize < 30 ? '10px' : '12px'};
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        cursor: pointer;
                        transition: all 0.3s ease;
                    ">
                        ${location.count}
                    </div>
                `,
                iconSize: [iconSize, iconSize],
                iconAnchor: [iconSize/2, iconSize/2]
            });

            const marker = L.marker([location.lat, location.lng], { icon })
                .bindPopup(`
                    <div style="text-align: center; min-width: 150px;">
                        <strong style="color: #1a4b8f; font-size: 1.1em;">${location.region}</strong><br>
                        <span style="font-size: 1.2em; font-weight: bold; color: ${location.color || '#1a4b8f'};">
                            ${location.count}
                        </span> requests<br>
                        <small class="text-muted">Click to filter by region</small>
                    </div>
                `, {
                    closeButton: true,
                    autoClose: false
                })
                // CORRECTION: Popup au survol + clic pour filtrer
                .on('mouseover', function() {
                    this.openPopup();
                })
                .on('mouseout', function() {
                    this.closePopup();
                })
                .on('click', () => {
                    this.filterByRegion(location.region);
                });

            marker.addTo(this.map);
            this.mapMarkers.push(marker);
        });
    }

    /**
     * Add map controls and legend
     */
    addMapControls() {
        // Add scale control
        L.control.scale({
            position: 'bottomleft',
            imperial: false
        }).addTo(this.map);

        // Add custom legend
        const legend = L.control({ position: 'topright' });
        legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'map-legend');
            div.style.cssText = `
                background: rgba(255,255,255,0.95);
                padding: 10px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                font-size: 0.85rem;
                max-width: 200px;
            `;
            div.innerHTML = `
                <div><strong>Guinea-Bissau Regions</strong></div>
                <div class="small text-muted mt-1">Marker size = request volume</div>
                <div class="small text-muted">Click markers to filter</div>
            `;
            return div;
        };
        legend.addTo(this.map);
    }

    /**
     * Filter by region when marker is clicked
     */
    filterByRegion(region) {
        document.getElementById('regionFilter').value = region;
        this.applyFilters();
        
        // Show notification
        this.showNotification(`Filtered by region: ${region}`, 'info');
    }

    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Auto-refresh toggle
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                this.toggleAutoRefresh(e.target.checked);
            });
        }

        // Filter change listeners
        ['regionFilter', 'familyFilter', 'requestFilter', 'yearFilter'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => {
                    this.updateActiveFilters();
                });
            }
        });
    }

    /**
     * Toggle auto-refresh functionality - FIXED to prevent negative decrements
     */
    toggleAutoRefresh(enabled) {
        this.autoRefreshEnabled = enabled;
        const statusElement = document.getElementById('autoRefreshStatus');
        
        if (enabled) {
            // Take snapshot of current stats to prevent decrements
            this.lastStatsSnapshot = { ...window.dashboardData.stats };
            
            statusElement.textContent = 'ON';
            statusElement.className = 'text-success fw-bold';
            
            this.autoRefreshInterval = setInterval(() => {
                this.refreshDashboard(true); // true = preserve stats consistency
            }, 30000); // 30 seconds
            
            this.showNotification('Auto-refresh enabled (30s)', 'success');
        } else {
            statusElement.textContent = 'OFF';
            statusElement.className = 'text-muted';
            
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
            
            this.showNotification('Auto-refresh disabled', 'info');
        }
    }

    /**
     * Refresh dashboard data - FIXED to prevent stats decrements
     */
    async refreshDashboard(preserveStats = false) {
        try {
            const response = await fetch('/admin/dashboard/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.currentFilters)
            });

            if (!response.ok) {
                throw new Error('Refresh failed');
            }

            const result = await response.json();
            
            if (result.success) {
                // Update data but preserve consistent stats if auto-refreshing
                if (preserveStats && this.lastStatsSnapshot) {
                    // Only update dynamic stats, preserve institution count
                    result.data.stats.institutions = this.lastStatsSnapshot.institutions;
                    result.data.stats.procedures = this.lastStatsSnapshot.procedures;
                    result.data.stats.families = this.lastStatsSnapshot.families;
                }
                
                this.updateDashboardWithData(result.data);
                
                if (!preserveStats) {
                    this.showNotification('Dashboard refreshed', 'success');
                }
            }
        } catch (error) {
            console.error('Refresh error:', error);
            this.showNotification('Refresh failed', 'danger');
        }
    }

    /**
     * Update dashboard with new data
     */
    updateDashboardWithData(data) {
        // Update global data
        window.dashboardData = data;

        // Update statistics cards
        this.updateStatsCards(data.stats);

        // Update charts
        this.updateCharts(data);

        // Update map
        this.updateMapMarkers();
    }

    /**
     * Update statistics cards
     */
    updateStatsCards(stats) {
        const statElements = {
            'stat-institutions': stats.institutions,
            'stat-procedures': stats.procedures,
            'stat-families': stats.families,
            'stat-requests': stats.requests,
            'stat-pending-admin': stats.pending_admin,
            'stat-pending-requestor': stats.pending_requestor
        };

        Object.entries(statElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                // Animate number change
                this.animateNumber(element, parseInt(element.textContent) || 0, value);
            }
        });
    }

    /**
     * Animate number changes
     */
    animateNumber(element, from, to, duration = 1000) {
        const start = Date.now();
        const difference = to - from;

        const step = () => {
            const elapsed = Date.now() - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.round(from + (difference * progress));
            element.textContent = current;

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        step();
    }

    /**
     * Update all charts with new data
     */
    updateCharts(data) {
        // Update donut chart
        if (this.charts.donut && data.requestsByFamily) {
            this.charts.donut.data.labels = data.requestsByFamily.map(item => item.family_name);
            this.charts.donut.data.datasets[0].data = data.requestsByFamily.map(item => item.count);
            this.charts.donut.update('none');
        }

        // Update bar chart
        if (this.charts.bar && data.requestsByGender) {
            this.charts.bar.data.labels = data.requestsByGender.labels;
            this.charts.bar.data.datasets[0].data = data.requestsByGender.data;
            this.charts.bar.update('none');
        }

        // Update line chart
        if (this.charts.line && data.monthlyRequests) {
            this.charts.line.data.labels = data.monthlyRequests.labels;
            this.charts.line.data.datasets[0].data = data.monthlyRequests.data;
            this.charts.line.update('none');
        }
    }

    /**
     * Apply filters - CORRECTION: Seule la carte est filtrée, pas les diagrammes
     */
    async applyFilters() {
        this.currentFilters = {
            region: document.getElementById('regionFilter').value,
            family: document.getElementById('familyFilter').value,
            request: document.getElementById('requestFilter').value,
            year: document.getElementById('yearFilter').value
        };

        try {
            // CORRECTION: Utiliser la nouvelle route qui ne filtre QUE la carte
            const params = new URLSearchParams(this.currentFilters);
            const response = await fetch(`/admin/dashboard/map-data?${params}`);
            
            if (!response.ok) {
                throw new Error('Filter request failed');
            }

            const result = await response.json();
            
            if (result.success) {
                // CORRECTION: Ne mettre à jour QUE la carte, pas les diagrammes
                if (result.data.requestsByLocation) {
                    window.dashboardData.requestsByLocation = result.data.requestsByLocation;
                    this.updateMapMarkers(); // SEULE la carte est mise à jour
                }
                
                this.updateActiveFilters();
                this.showNotification('Map filtered successfully', 'success');
            }
        } catch (error) {
            console.error('Filter error:', error);
            this.showNotification('Filter failed', 'danger');
        }
    }

    /**
     * Clear all filters - CORRECTION: Recharger QUE la carte, pas les diagrammes
     */
    async clearFilters() {
        ['regionFilter', 'familyFilter', 'requestFilter', 'yearFilter'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
            }
        });

        this.currentFilters = {};
        this.updateActiveFilters();
        
        // CORRECTION: Recharger SEULEMENT la carte avec toutes les régions
        try {
            const response = await fetch('/admin/dashboard/map-data');
            const result = await response.json();
            
            if (result.success && result.data.requestsByLocation) {
                window.dashboardData.requestsByLocation = result.data.requestsByLocation;
                this.updateMapMarkers(); // SEULE la carte est rechargée
            }
        } catch (error) {
            console.error('Clear filters error:', error);
        }
        
        this.showNotification('Map filters cleared', 'info');
    }

    /**
     * Update active filters display
     */
    updateActiveFilters() {
        const activeFiltersDiv = document.getElementById('activeFilters');
        const filterBadgesDiv = document.getElementById('filterBadges');
        
        if (!activeFiltersDiv || !filterBadgesDiv) return;

        const filters = {
            region: document.getElementById('regionFilter').value,
            family: document.getElementById('familyFilter').value,
            request: document.getElementById('requestFilter').value,
            year: document.getElementById('yearFilter').value
        };

        const activeFilters = Object.entries(filters).filter(([key, value]) => value);

        if (activeFilters.length > 0) {
            activeFiltersDiv.style.display = 'block';
            filterBadgesDiv.innerHTML = activeFilters.map(([key, value]) => `
                <span class="filter-badge">
                    ${key}: ${value}
                    <span class="remove" onclick="window.mkgovDashboard.removeFilter('${key}')">&times;</span>
                </span>
            `).join('');
        } else {
            activeFiltersDiv.style.display = 'none';
        }
    }

    /**
     * Remove individual filter
     */
    removeFilter(filterKey) {
        const element = document.getElementById(filterKey + 'Filter');
        if (element) {
            element.value = '';
            this.applyFilters();
        }
    }

    /**
     * Load initial data
     */
    loadInitialData() {
        // Data is already loaded from the server via window.dashboardData
        this.updateActiveFilters();
    }

    /**
     * Export data
     */
    async exportData(format) {
        try {
            const params = new URLSearchParams(this.currentFilters);
            params.set('format', format);
            
            this.showNotification(`Preparing ${format.toUpperCase()} export...`, 'info');
            
            // Use window.open for better browser compatibility
            window.open(`/admin/dashboard/export?${params}`, '_blank');
            
            setTimeout(() => {
                this.showNotification(`${format.toUpperCase()} export initiated`, 'success');
            }, 1000);
            
        } catch (error) {
            console.error('Export error:', error);
            this.showNotification('Export failed', 'danger');
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.dashboard-notification').forEach(el => el.remove());

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show dashboard-notification`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.mkgovDashboard = new MKGovDashboard();
});

// Handle page unload
window.addEventListener('beforeunload', function() {
    if (window.mkgovDashboard) {
        window.mkgovDashboard.destroy();
    }
});

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .custom-marker {
        cursor: pointer !important;
    }
    
    .map-legend {
        pointer-events: auto !important;
    }
`;
document.head.appendChild(style);