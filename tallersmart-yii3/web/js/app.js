/**
 * TallerSmart - JavaScript Personalizado
 * Funcionalidades comunes para el frontend
 */

// Esperar a que el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar tooltips de Bootstrap (si se usan)
    initTooltips();
    
    // Inicializar confirmaciones de eliminación
    initDeleteConfirmations();
    
    // Inicializar auto-dismiss de alertas
    initAlerts();
    
    // Inicializar búsqueda en tablas
    initTableSearch();
    
    // Inicializar select2 (si está disponible)
    initSelect2();
    
    // Inicializar máscaras de input
    initInputMasks();
    
    // Inicializar modales
    initModals();
});

/**
 * Inicializar tooltips
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        // Implementación personalizada de tooltip si es necesaria
    });
}

/**
 * Inicializar confirmaciones de eliminación
 */
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || '¿Está seguro de eliminar este elemento?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Inicializar auto-dismiss de alertas
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const dismissButton = alert.querySelector('[data-dismiss="alert"]');
        if (dismissButton) {
            dismissButton.addEventListener('click', function() {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }
        
        // Auto-dismiss después de 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });
}

/**
 * Inicializar búsqueda en tablas
 */
function initTableSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const tableId = this.getAttribute('data-table-search');
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
}

/**
 * Inicializar Select2
 */
function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            language: 'es',
            placeholder: 'Seleccione una opción',
            allowClear: true
        });
    }
}

/**
 * Inicializar máscaras de input
 */
function initInputMasks() {
    // Máscara para teléfono
    const phoneInputs = document.querySelectorAll('[data-mask-phone]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            if (value.length > 6) {
                this.value = `(${value.substring(0, 2)}) ${value.substring(2, 6)}-${value.substring(6)}`;
            } else if (value.length > 2) {
                this.value = `(${value.substring(0, 2)}) ${value.substring(2)}`;
            } else if (value.length > 0) {
                this.value = `(${value}`;
            }
        });
    });
    
    // Máscara para moneda
    const currencyInputs = document.querySelectorAll('[data-mask-currency]');
    currencyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            value = (Number(value) / 100).toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN'
            });
            this.value = value;
        });
    });
}

/**
 * Inicializar modales
 */
function initModals() {
    // Manejar apertura de modales con AJAX
    const modalTriggers = document.querySelectorAll('[data-modal-ajax]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href') || this.getAttribute('data-url');
            const modalId = this.getAttribute('data-modal-ajax');
            const modal = document.getElementById(modalId);
            
            if (modal && url) {
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        modal.querySelector('.modal-content').innerHTML = html;
                        // Mostrar modal (implementación dependiente del framework CSS)
                    })
                    .catch(error => console.error('Error loading modal content:', error));
            }
        });
    });
}

/**
 * Mostrar notificación toast
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-body">
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-dismiss después de 4 segundos
    setTimeout(() => {
        toast.classList.add('toast-hide');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Crear contenedor de toasts si no existe
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    `;
    document.body.appendChild(container);
    return container;
}

/**
 * Obtener ícono según tipo de toast
 */
function getToastIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || icons.info;
}

/**
 * Formatear moneda
 */
function formatCurrency(amount, currency = 'MXN') {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Formatear fecha
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

/**
 * Prevenir envío múltiple de formularios
 */
function preventDoubleSubmit(formSelector) {
    const forms = document.querySelectorAll(formSelector);
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(button => {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            });
        });
    });
}

// Exportar funciones globales
window.TallerSmart = {
    showToast,
    formatCurrency,
    formatDate,
    preventDoubleSubmit
};
