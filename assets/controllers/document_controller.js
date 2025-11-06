import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller pour la gestion des documents
 * Usage: data-controller="document"
 */
export default class extends Controller {
    static targets = [
        'fileInput',
        'filePreview',
        'filePreviewContent',
        'previewImage',
        'fileInfo',
        'removeFileCheckbox',
        'statusBadge',
        'bulkActions',
        'selectedCount',
        'selectAllCheckbox'
    ];

    static values = {
        documentId: Number,
        previewUrl: String,
        downloadUrl: String,
        toggleStatusUrl: String,
        maxFileSize: Number
    };

    connect() {
        console.log('Document controller connected');
        this.initializeFileUpload();
        this.initializeBulkActions();
        this.initializeTooltips();
    }

    disconnect() {
        console.log('Document controller disconnected');
    }

    /**
     * Initialize file upload functionality
     */
    initializeFileUpload() {
        if (this.hasFileInputTarget) {
            this.fileInputTarget.addEventListener('change', this.handleFileSelect.bind(this));
            
            if (this.hasFilePreviewTarget) {
                this.setupDragAndDrop();
            }
        }
    }

    /**
     * Setup drag and drop functionality
     */
    setupDragAndDrop() {
        const preview = this.filePreviewTarget;
        
        preview.addEventListener('dragover', this.handleDragOver.bind(this));
        preview.addEventListener('dragleave', this.handleDragLeave.bind(this));
        preview.addEventListener('drop', this.handleFileDrop.bind(this));
        preview.addEventListener('click', () => {
            this.fileInputTarget.click();
        });
    }

    /**
     * Handle file selection
     */
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            if (this.validateFile(file)) {
                this.displayFilePreview(file);
            }
        }
    }

    /**
     * Handle drag over
     */
    handleDragOver(event) {
        event.preventDefault();
        this.filePreviewTarget.classList.add('dragover');
    }

    /**
     * Handle drag leave
     */
    handleDragLeave(event) {
        event.preventDefault();
        this.filePreviewTarget.classList.remove('dragover');
    }

    /**
     * Handle file drop
     */
    handleFileDrop(event) {
        event.preventDefault();
        this.filePreviewTarget.classList.remove('dragover');
        
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            
            if (this.validateFile(file)) {
                // Update file input
                const dt = new DataTransfer();
                dt.items.add(file);
                this.fileInputTarget.files = dt.files;
                
                this.displayFilePreview(file);
            }
        }
    }

    /**
     * Validate file
     */
    validateFile(file) {
        // Check file size
        const maxSize = this.maxFileSizeValue || 10 * 1024 * 1024; // 10MB default
        if (file.size > maxSize) {
            this.showNotification(
                `Le fichier est trop volumineux. Taille maximum: ${this.formatBytes(maxSize)}`,
                'error'
            );
            return false;
        }

        // Check file type
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'text/plain',
            'text/csv'
        ];

        if (!allowedTypes.includes(file.type)) {
            this.showNotification('Type de fichier non autorisé', 'error');
            return false;
        }

        return true;
    }

    /**
     * Display file preview
     */
    displayFilePreview(file) {
        if (!this.hasFileInfoTarget || !this.hasPreviewImageTarget) return;

        const fileSize = this.formatBytes(file.size);
        const fileType = file.type || 'Type inconnu';

        // Update file info
        this.fileInfoTarget.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi-file-earmark text-primary me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <div class="fw-bold">${file.name}</div>
                    <small class="text-muted">${fileSize} • ${fileType}</small>
                </div>
            </div>
        `;

        // Clear previous preview
        this.previewImageTarget.innerHTML = '';

        // Show preview based on file type
        if (file.type.startsWith('image/')) {
            this.showImagePreview(file);
        } else {
            this.showFileIcon(file);
        }

        // Show preview content
        if (this.hasFilePreviewContentTarget) {
            this.filePreviewContentTarget.style.display = 'block';
        }
    }

    /**
     * Show image preview
     */
    showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            this.previewImageTarget.innerHTML = `
                <img src="${e.target.result}" class="preview-image" alt="Prévisualisation">
            `;
        };
        reader.readAsDataURL(file);
    }

    /**
     * Show file icon
     */
    showFileIcon(file) {
        let iconClass = 'bi-file-earmark';
        
        if (file.type === 'application/pdf') {
            iconClass = 'bi-file-earmark-pdf';
        } else if (file.type.includes('word')) {
            iconClass = 'bi-file-earmark-word';
        } else if (file.type.includes('excel')) {
            iconClass = 'bi-file-earmark-excel';
        } else if (file.type.includes('powerpoint')) {
            iconClass = 'bi-file-earmark-ppt';
        }

        this.previewImageTarget.innerHTML = `
            <i class="${iconClass} text-primary" style="font-size: 4rem;"></i>
        `;
    }

    /**
     * Initialize bulk actions
     */
    initializeBulkActions() {
        // Select all checkbox
        if (this.hasSelectAllCheckboxTarget) {
            this.selectAllCheckboxTarget.addEventListener('change', this.handleSelectAll.bind(this));
        }

        // Individual checkboxes
        document.addEventListener('change', (event) => {
            if (event.target.classList.contains('row-select')) {
                this.updateBulkActions();
            }
        });
    }

    /**
     * Handle select all
     */
    handleSelectAll(event) {
        const checkboxes = document.querySelectorAll('.row-select');
        checkboxes.forEach(checkbox => {
            checkbox.checked = event.target.checked;
        });
        this.updateBulkActions();
    }

    /**
     * Update bulk actions visibility
     */
    updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.row-select:checked');
        const count = selectedCheckboxes.length;

        if (this.hasSelectedCountTarget) {
            this.selectedCountTarget.textContent = count;
        }

        if (this.hasBulkActionsTarget) {
            if (count > 0) {
                this.bulkActionsTarget.classList.add('show');
            } else {
                this.bulkActionsTarget.classList.remove('show');
            }
        }

        // Update select all checkbox state
        if (this.hasSelectAllCheckboxTarget) {
            const totalCheckboxes = document.querySelectorAll('.row-select').length;
            this.selectAllCheckboxTarget.checked = count === totalCheckboxes;
        }
    }

    /**
     * Toggle document status
     */
    async toggleStatus() {
        if (!this.documentIdValue || !this.toggleStatusUrlValue) return;

        try {
            const response = await fetch(this.toggleStatusUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(data.message, 'success');
                this.updateStatusDisplay(data.status);
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error toggling status:', error);
            this.showNotification('Erreur lors de la mise à jour du statut', 'error');
        }
    }

    /**
     * Update status display
     */
    updateStatusDisplay(isActive) {
        if (this.hasStatusBadgeTarget) {
            const badge = this.statusBadgeTarget;
            if (isActive) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Actif';
            } else {
                badge.className = 'badge bg-danger';
                badge.textContent = 'Inactif';
            }
        }
    }

    /**
     * Download document
     */
    downloadDocument() {
        if (this.downloadUrlValue) {
            window.open(this.downloadUrlValue, '_blank');
        }
    }

    /**
     * Preview document
     */
    previewDocument() {
        if (this.previewUrlValue) {
            window.open(this.previewUrlValue, '_blank');
        }
    }

    /**
     * Remove file checkbox change handler
     */
    handleRemoveFileChange(event) {
        if (event.target.checked) {
            const confirmed = confirm(
                'Êtes-vous sûr de vouloir supprimer le fichier actuel ? Cette action est irréversible.'
            );
            if (!confirmed) {
                event.target.checked = false;
            }
        }
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltipTriggerList = this.element.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Format bytes to human readable format
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        if (window.MKBAAdmin && typeof window.MKBAAdmin.showNotification === 'function') {
            window.MKBAAdmin.showNotification(message, type);
        } else {
            // Fallback to alert if MKBAAdmin is not available
            alert(message);
        }
    }

    /**
     * Refresh DataTable
     */
    refreshDataTable() {
        if (window.documentsTable && typeof window.documentsTable.ajax === 'object') {
            window.documentsTable.ajax.reload();
        }
    }

    /**
     * Perform bulk action
     */
    async performBulkAction(action) {
        const selectedIds = Array.from(document.querySelectorAll('.row-select:checked'))
            .map(checkbox => checkbox.value);

        if (selectedIds.length === 0) {
            this.showNotification('Aucun élément sélectionné', 'warning');
            return;
        }

        const actionNames = {
            'activate': 'activer',
            'deactivate': 'désactiver',
            'delete': 'supprimer'
        };

        const confirmed = confirm(
            `Êtes-vous sûr de vouloir ${actionNames[action]} ${selectedIds.length} document(s) ?`
        );

        if (!confirmed) return;

        try {
            const response = await fetch('/admin/document/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: action,
                    ids: selectedIds
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(data.message, 'success');
                this.refreshDataTable();
                this.updateBulkActions();
                if (this.hasSelectAllCheckboxTarget) {
                    this.selectAllCheckboxTarget.checked = false;
                }
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            this.showNotification('Erreur lors de l\'action groupée', 'error');
        }
    }
}