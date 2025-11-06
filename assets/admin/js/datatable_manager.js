/**
 * Classe générique pour gérer un Datatable (tri, pagination, filtrage, sélection).
 */
class DatatableManager {
    /**
     * @param {string} tableId L'ID du <tbody> ('tableBody' par défaut)
     * @param {object} options Options spécifiques (URLs, tokens, sélecteurs)
     */
    constructor(tableId = 'tableBody', options = {}) {
        this.tbody = document.getElementById(tableId);
        this.originalData = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.itemsPerPage = options.itemsPerPage || 25;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.selectedRows = new Set();
        this.loadingSpinner = document.getElementById('loadingSpinner');
        this.csrfTokens = options.csrfTokens || {};
        this.entityUrls = options.entityUrls || {};
        this.selectors = {
            searchInput: options.selectors?.searchInput || 'searchInput',
            statusFilter: options.selectors?.statusFilter || 'statusFilter',
            dateStart: options.selectors?.dateStart || 'dateStart',
            dateEnd: options.selectors?.dateEnd || 'dateEnd',
            entriesSelect: options.selectors?.entriesSelect || 'entriesSelect',
            bulkActionsDropdown: options.selectors?.bulkActionsDropdown || 'bulkActionsDropdown',
            selectAll: options.selectors?.selectAll || 'selectAll',
            rowSelect: options.selectors?.rowSelect || '.mkba-row-select',
            sortable: options.selectors?.sortable || '.sortable',
            paginationControls: options.selectors?.paginationControls || 'paginationControls'
        };

        // Hooks pour les fonctions de l'entité spécifique
        this.onDeleteHook = options.onDeleteHook || function() {};
        this.onBulkActionHook = options.onBulkActionHook || function() {};

        this.init();
    }

    // --- Initialisation et extraction des données ---

    init() {
        this.extractDataFromTable();
        this.initializeEventListeners();
        this.setupSortingEventListeners();
        this.updateTable();
        this.updatePagination();
        this.updateStats();
        console.log(`✅ DatatableManager initialisé pour ${this.originalData.length} entrées.`);
    }

    extractDataFromTable() {
        if (!this.tbody) return;

        const rows = this.tbody.querySelectorAll('tr[data-project-id]');
        this.originalData = [];

        rows.forEach(row => {
            const id = parseInt(row.dataset.projectId);
            const cells = row.children;

            // ATTENTION: Adapter les index des cellules (cells[X]) pour chaque entité !
            const entityData = {
                id: id,
                code: cells[1]?.getAttribute('data-sort'),
                name: cells[2]?.getAttribute('data-sort'),
                category: cells[3]?.getAttribute('data-sort'),
                priority: cells[4]?.getAttribute('data-sort'),
                status: cells[5]?.getAttribute('data-sort'),
                responsible: cells[6]?.getAttribute('data-sort') || '',
                start_date: cells[7]?.getAttribute('data-sort') || '',
                created_at: cells[8]?.getAttribute('data-sort'),
                // Conserver l'élément DOM original pour le clonage
                element: row.cloneNode(true)
            };

            this.originalData.push(entityData);
        });

        this.filteredData = [...this.originalData];
    }

    // --- Gestion des événements ---

    initializeEventListeners() {
        // Filtres de base et recherche
        document.getElementById(this.selectors.statusFilter)?.addEventListener('change', () => this.applyFilters());
        document.getElementById(this.selectors.dateStart)?.addEventListener('change', () => this.applyFilters());
        document.getElementById(this.selectors.dateEnd)?.addEventListener('change', () => this.applyFilters());
        document.getElementById(this.selectors.searchInput)?.addEventListener('input', (e) => this.searchInTable(e.target.value));

        // Nombre d'entrées par page
        document.getElementById(this.selectors.entriesSelect)?.addEventListener('change', (e) => {
            this.itemsPerPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.updateTable();
            this.updatePagination();
            this.updateStats();
        });

        // Sélection globale
        document.getElementById(this.selectors.selectAll)?.addEventListener('change', (e) => this.toggleSelectAll(e.target.checked));
    }

    // Attache les écouteurs de tri
    setupSortingEventListeners() {
        document.querySelectorAll(this.selectors.sortable).forEach(header => {
            header.addEventListener('click', () => {
                this.sortTable(header.dataset.column);
            });
        });
    }

    // --- Tri ---

    sortTable(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }

        this.updateSortIcons(column);

        this.filteredData.sort((a, b) => {
            let valueA = a[column] || '';
            let valueB = b[column] || '';

            // Gestion du tri par date
            if (column === 'start_date' || column === 'created_at') {
                valueA = new Date(valueA || '1900-01-01');
                valueB = new Date(valueB || '1900-01-01');
            } else if (typeof valueA === 'string') {
                valueA = valueA.toLowerCase();
                valueB = valueB.toLowerCase();
            }

            if (valueA < valueB) {
                return this.sortDirection === 'asc' ? -1 : 1;
            }
            if (valueA > valueB) {
                return this.sortDirection === 'asc' ? 1 : -1;
            }
            return 0;
        });

        this.currentPage = 1;
        this.updateTable();
        this.updatePagination();
        this.updateStats();
    }

    updateSortIcons(activeColumn) {
        document.querySelectorAll(this.selectors.sortable).forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
        });

        const activeHeader = document.querySelector(`[data-column="${activeColumn}"]`);
        if (activeHeader) {
            activeHeader.classList.add(this.sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    }

    // --- Affichage et rendu (cœur du Datatable) ---

    updateTable() {
        this.showLoading(true);

        setTimeout(() => {
            const startIndex = (this.currentPage - 1) * this.itemsPerPage;
            const endIndex = startIndex + this.itemsPerPage;
            const pageData = this.filteredData.slice(startIndex, endIndex);

            this.tbody.innerHTML = '';

            if (pageData.length === 0) {
                this.tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-folder-open text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">Aucune donnée trouvée</p>
                        </td>
                    </tr>
                `;
            } else {
                pageData.forEach(item => {
                    const row = item.element.cloneNode(true);

                    // Réattacher la logique de sélection
                    const checkbox = row.querySelector(this.selectors.rowSelect);
                    if (checkbox) {
                        checkbox.checked = this.selectedRows.has(item.id);
                        checkbox.addEventListener('change', () => this.handleRowCheckboxChange(checkbox));
                        if (this.selectedRows.has(item.id)) {
                            row.classList.add('selected');
                        } else {
                            row.classList.remove('selected');
                        }
                    }

                    // Réattacher la logique de suppression spécifique (via le hook)
                    const deleteBtn = row.querySelector('[onclick*="showDeleteModal"]');
                    if (deleteBtn && this.onDeleteHook) {
                        // Remplace l'ancien onclick par une version générique
                        deleteBtn.removeAttribute('onclick');
                        deleteBtn.addEventListener('click', () => this.onDeleteHook(item.id, item.code, item.name));
                    }

                    this.tbody.appendChild(row);
                });
            }

            this.showLoading(false);
            this.updateBulkActions();
            this.updateSelectAllCheckbox();
        }, 200);
    }

    // --- Pagination ---

    updatePagination() {
        const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        const paginationControls = document.getElementById(this.selectors.paginationControls);

        if (!paginationControls || totalPages <= 1) {
            if (paginationControls) paginationControls.innerHTML = '';
            return;
        }

        // ... La logique de génération du HTML de pagination (identique) ...
        let paginationHTML = '';

        // Bouton Précédent
        paginationHTML += `
            <button class="btn btn-outline-secondary me-1 ${this.currentPage === 1 ? 'disabled' : ''}"
                    onclick="datatableManager.changePage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;

        // Pages (logique des 5 pages, centrée sur la page courante)
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<button class="btn btn-outline-secondary me-1" onclick="datatableManager.changePage(1)">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="btn btn-outline-secondary me-1 disabled">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button class="btn me-1 ${i === this.currentPage ? 'btn-primary active' : 'btn-outline-secondary'}"
                        onclick="datatableManager.changePage(${i})">${i}</button>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="btn btn-outline-secondary me-1 disabled">...</span>`;
            }
            paginationHTML += `<button class="btn btn-outline-secondary me-1" onclick="datatableManager.changePage(${totalPages})">${totalPages}</button>`;
        }

        // Bouton Suivant
        paginationHTML += `
            <button class="btn btn-outline-secondary ${this.currentPage === totalPages ? 'disabled' : ''}"
                    onclick="datatableManager.changePage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;

        paginationControls.innerHTML = paginationHTML;
    }

    changePage(page) {
        const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        if (page >= 1 && page <= totalPages) {
            this.currentPage = page;
            this.updateTable();
            this.updatePagination();
            this.updateStats();
        }
    }

    // --- Statistiques et chargement ---

    updateStats() {
        const startIndex = (this.currentPage - 1) * this.itemsPerPage + 1;
        const endIndex = Math.min(this.currentPage * this.itemsPerPage, this.filteredData.length);

        document.getElementById('showingFrom').textContent = this.filteredData.length === 0 ? 0 : startIndex;
        document.getElementById('showingTo').textContent = endIndex;
        document.getElementById('totalEntries').textContent = this.filteredData.length;
        document.getElementById('selectedCount').textContent = this.selectedRows.size;

        const filteredInfo = document.getElementById('filteredInfo');
        const totalUnfiltered = document.getElementById('totalUnfiltered');

        if (filteredInfo && totalUnfiltered) {
            if (this.filteredData.length !== this.originalData.length) {
                filteredInfo.style.display = 'inline';
                totalUnfiltered.textContent = this.originalData.length;
            } else {
                filteredInfo.style.display = 'none';
            }
        }
    }

    showLoading(show) {
        if (this.loadingSpinner) {
            this.loadingSpinner.style.display = show ? 'block' : 'none';
        }
    }

    // --- Filtrage et Recherche ---

    /**
     * Applique les filtres de base (recherche et filtres rapides)
     * Peut être étendu par la logique de filtres avancés
     */
    applyFilters(advancedFilters = {}) {
        const searchTerm = document.getElementById(this.selectors.searchInput)?.value.toLowerCase().trim() || '';
        const statusFilter = document.getElementById(this.selectors.statusFilter)?.value || '';
        const startDate = document.getElementById(this.selectors.dateStart)?.value || '';
        const endDate = document.getElementById(this.selectors.dateEnd)?.value || '';

        this.filteredData = this.originalData.filter(item => {
            let visible = true;

            // 1. Recherche par terme
            if (searchTerm) {
                const searchMatch = Object.values(item).some(value => {
                    if (value && typeof value === 'string') {
                        return value.toLowerCase().includes(searchTerm);
                    }
                    return false;
                });
                if (!searchMatch) visible = false;
            }

            // 2. Filtre par statut
            if (visible && statusFilter && item.status !== statusFilter) {
                visible = false;
            }

            // 3. Filtre par date
            if (visible && (startDate || endDate) && item.start_date) {
                const itemDate = new Date(item.start_date);
                if (startDate && itemDate < new Date(startDate)) visible = false;
                if (endDate && itemDate > new Date(endDate)) visible = false;
            }

            // 4. Filtres avancés (implémentation spécifique pour chaque entité)
            if (visible && Object.keys(advancedFilters).length > 0) {
                // Cette partie est laissée pour l'implémentation spécifique dans l'entité
                // Voir la section "Implémentation spécifique" ci-dessous
                if (advancedFilters.code && !item.code.toLowerCase().includes(advancedFilters.code)) visible = false;
                if (advancedFilters.category && item.category !== advancedFilters.category) visible = false;
                // ... etc.
            }

            return visible;
        });

        this.currentPage = 1;
        this.updateTable();
        this.updatePagination();
        this.updateStats();
    }

    // Raccourci pour la recherche par input (applique les autres filtres aussi)
    searchInTable(searchTerm) {
        this.applyFilters();
    }

    // --- Sélection de masse ---

    handleRowCheckboxChange(checkbox) {
        const projectId = parseInt(checkbox.value);
        const row = checkbox.closest('tr');

        if (checkbox.checked) {
            this.selectedRows.add(projectId);
            row.classList.add('selected');
        } else {
            this.selectedRows.delete(projectId);
            row.classList.remove('selected');
        }

        this.updateBulkActions();
        this.updateStats();
        this.updateSelectAllCheckbox();
    }

    toggleSelectAll(selectAll) {
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pageData = this.filteredData.slice(startIndex, endIndex);
        const isCurrentlySelected = Array.from(this.selectedRows).filter(id => pageData.some(item => item.id === id)).length === pageData.length;

        // Si tout est déjà sélectionné, désélectionner, sinon sélectionner tout
        const shouldSelect = selectAll || !isCurrentlySelected;

        pageData.forEach(item => {
            if (shouldSelect) {
                this.selectedRows.add(item.id);
            } else {
                this.selectedRows.delete(item.id);
            }
        });

        this.updateTable(); // Mettre à jour les cases à cocher et styles
        this.updateBulkActions();
        this.updateStats();
    }

    updateBulkActions() {
        const bulkDropdown = document.getElementById(this.selectors.bulkActionsDropdown);
        if (bulkDropdown) {
            bulkDropdown.style.display = this.selectedRows.size > 0 ? 'inline-block' : 'none';
        }
    }

    updateSelectAllCheckbox() {
        // Logique pour gérer la case 'selectAll' et son état 'indeterminate'
        const allCheckboxes = document.querySelectorAll(this.selectors.rowSelect);
        const checkedCheckboxes = document.querySelectorAll(`${this.selectors.rowSelect}:checked`);
        const selectAllCheckbox = document.getElementById(this.selectors.selectAll);

        if (!selectAllCheckbox || allCheckboxes.length === 0) return;

        if (checkedCheckboxes.length === allCheckboxes.length && allCheckboxes.length > 0) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCheckboxes.length > 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }
}