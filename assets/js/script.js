/**
 * Modern Healthcare Pharmacy - Enhanced UI Script
 * Provides micro-interactions, accessibility features, and UX enhancements
 */

$(document).ready(function() {
    // Initialize DataTables with modern styling
    $('.table[data-table]').DataTable({
        responsive: true,
        dom: '<"row"<"col-12"f>><"row"<"col-12"t>><"row"<"col-md-6"i><"col-md-6"p>>',
        language: {
            search: '<i class="bi bi-search me-2"></i>',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries to show',
            infoFiltered: '(filtered from _MAX_ total entries)'
        }
    });
    
    // Initialize Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    $('[data-bs-toggle="popover"]').popover();
    
    // Enhanced delete confirmation with modal
    $('.confirm-delete').on('click', function(e) {
        e.preventDefault();
        const deleteUrl = $(this).attr('href') || $(this).data('url');
        const itemName = $(this).data('item') || 'this item';
        
        showDeleteConfirmation(itemName, deleteUrl);
    });
    
    // Modern form validation
    initializeFormValidation();
    
    // Add smooth transitions to page elements
    animatePageElements();
    
    // Initialize sidebar toggle for mobile
    initializeSidebarToggle();
    
    // Add focus management for accessibility
    initializeFocusManagement();
    
    // Add loading state to form submissions
    initializeFormSubmissions();
    
    // Add table row interactions
    initializeTableInteractions();
});

/**
 * Show delete confirmation modal
 */
function showDeleteConfirmation(itemName, deleteUrl) {
    const confirmModal = `
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title" id="deleteModalLabel">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i>Confirm Deletion
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to delete <strong>${itemName}</strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-top-0 gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="${deleteUrl}" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove old modal if exists
    $('#deleteModal').remove();
    $('body').append(confirmModal);
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
    
    // Clean up modal from DOM after hiding
    document.getElementById('deleteModal').addEventListener('hidden.bs.modal', function() {
        $(this).remove();
    });
}

/**
 * Initialize form validation with visual feedback
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form:not(.skip-validation)');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Add shake animation to form
                form.style.animation = 'shake 0.5s ease-in-out';
                setTimeout(() => {
                    form.style.animation = '';
                }, 500);
            }
            form.classList.add('was-validated');
        });
        
        // Clear validation on input
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            });
        });
    });
}

/**
 * Animate page elements on load
 */
function animatePageElements() {
    // Animate cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animation = `fadeInUp ${0.3 + index * 0.05}s ease-out`;
    });
    
    // Animate tables
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.animation = `fadeInUp ${0.4 + index * 0.02}s ease-out`;
        });
    });
}

/**
 * Initialize sidebar toggle for mobile
 */
function initializeSidebarToggle() {
    const sidebarToggle = document.querySelector('.navbar-toggler');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : 'auto';
        });
        
        // Close sidebar when clicking on a link
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.remove('show');
                document.body.style.overflow = 'auto';
            });
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
    }
}

/**
 * Initialize focus management for accessibility
 */
function initializeFocusManagement() {
    // Skip to main content link
    const skipLink = document.querySelector('.skip-to-main');
    if (skipLink) {
        skipLink.addEventListener('click', function(e) {
            e.preventDefault();
            const mainContent = document.querySelector('main');
            if (mainContent) {
                mainContent.focus();
                mainContent.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
    
    // Keyboard navigation for tab key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('using-keyboard');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('using-keyboard');
    });
}

/**
 * Initialize form submission loading states
 */
function initializeFormSubmissions() {
    const forms = document.querySelectorAll('form:not(.skip-loading)');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.setAttribute('aria-busy', 'true');
                submitBtn.setAttribute('disabled', 'disabled');
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin me-2"></i>Processing...';
                
                // Re-enable after timeout (in case response is slow)
                setTimeout(() => {
                    if (submitBtn.hasAttribute('aria-busy')) {
                        submitBtn.removeAttribute('aria-busy');
                        submitBtn.removeAttribute('disabled');
                        submitBtn.innerHTML = originalHTML;
                    }
                }, 30000);
            }
        });
    });
}

/**
 * Initialize table row interactions
 */
function initializeTableInteractions() {
    const tables = document.querySelectorAll('table tbody');
    
    tables.forEach(tbody => {
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            // Add hover effects
            row.addEventListener('mouseenter', function() {
                this.classList.add('table-active');
            });
            
            row.addEventListener('mouseleave', function() {
                this.classList.remove('table-active');
            });
        });
    });
}

/**
 * Print invoice function with modern dialog
 */
function printInvoice(invoiceId) {
    const printWindow = window.open(BASE_URL + '/staff/print-invoice.php?id=' + invoiceId, '_blank');
    
    if (printWindow) {
        // Show success notification
        showNotification('Preparing invoice for printing...', 'info', 3000);
    } else {
        showNotification('Unable to open print dialog. Please check popup blocker.', 'warning', 5000);
    }
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info', duration = 5000) {
    const toastHTML = `
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast animate-fade-in" role="alert" aria-live="polite" aria-atomic="true">
                <div class="toast-header bg-${type} text-white border-0">
                    <i class="bi bi-${getIconForType(type)} me-2"></i>
                    <strong class="me-auto">${capitalizeFirstLetter(type)}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        </div>
    `;
    
    $('body').append(toastHTML);
    
    const toastElement = document.querySelector('.toast-container .toast');
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    if (duration > 0) {
        setTimeout(() => {
            toast.hide();
            toastElement.closest('.toast-container').remove();
        }, duration);
    }
}

/**
 * Get appropriate icon for notification type
 */
function getIconForType(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || icons['info'];
}

/**
 * Capitalize first letter
 */
function capitalizeFirstLetter(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Export data to CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    let rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let rowData = [];
        row.querySelectorAll('td, th').forEach(cell => {
            rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
    showNotification('Table exported successfully!', 'success');
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    link.download = filename;
    link.click();
}

/**
 * Throttle function calls
 */
function throttle(func, wait) {
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
 * Add CSRF token to all AJAX requests (if applicable)
 */
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            xhr.setRequestHeader('X-CSRF-Token', token.getAttribute('content'));
        }
    }
});

