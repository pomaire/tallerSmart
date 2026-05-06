/**
 * TallerSmart - Aplicación Frontend
 * JavaScript para manejo de UI y interacciones
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initSidebar();
    initTooltips();
    initPopovers();
    initModals();
    initForms();
    initTables();
    initNotifications();
    initSearchDebounce(); // HU-018
});

/**
 * Inicializar Sidebar (toggle en móvil)
 */
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                // En desktop, colapsar/expandir
                const mainContent = document.getElementById('main-content');
                if (sidebar.style.transform === 'translateX(-100%)') {
                    sidebar.style.transform = 'translateX(0)';
                    mainContent.style.marginLeft = '260px';
                } else {
                    sidebar.style.transform = 'translateX(-100%)';
                    mainContent.style.marginLeft = '0';
                }
            }
        });
        
        // Cerrar sidebar al hacer click fuera en móvil
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    }
}

/**
 * Inicializar Tooltips de Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Inicializar Popovers de Bootstrap
 */
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Inicializar Modales
 */
function initModals() {
    // Confirmación de eliminación
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href') || this.getAttribute('data-url');
            const message = this.getAttribute('data-message') || '¿Está seguro de que desea eliminar este elemento?';
            
            if (confirm(message)) {
                window.location.href = url;
            }
        });
    });
    
    // Modal dinámico para formularios
    const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"][data-form-url]');
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const url = this.getAttribute('data-form-url');
            const modal = document.querySelector(modalId);
            
            if (modal && url) {
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        modal.querySelector('.modal-body').innerHTML = html;
                        initForms();
                    })
                    .catch(error => {
                        console.error('Error loading form:', error);
                        modal.querySelector('.modal-body').innerHTML = 
                            '<div class="alert alert-danger">Error cargando el formulario</div>';
                    });
            }
        });
    });
}

/**
 * Inicializar Formularios
 */
function initForms() {
    // Validación de formularios de Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Auto-cierre de alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirmación de formularios importantes
    const confirmForms = document.querySelectorAll('.form-confirm-submit');
    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = form.getAttribute('data-confirm-message') || '¿Está seguro de continuar?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Inicializar Tablas
 */
function initTables() {
    // Búsqueda en tablas
    const tableSearchInputs = document.querySelectorAll('.table-search-input');
    tableSearchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });
    
    // Ordenamiento de columnas
    const sortableHeaders = document.querySelectorAll('[data-sortable]');
    sortableHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            const direction = this.getAttribute('data-sort-direction') === 'asc' ? 'desc' : 'asc';
            
            sortTable(table, columnIndex, direction);
            
            // Actualizar indicador de orden
            table.querySelectorAll('th').forEach(th => th.removeAttribute('data-sort-direction'));
            this.setAttribute('data-sort-direction', direction);
        });
    });
    
    // Paginación con AJAX
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') !== '#') {
                e.preventDefault();
                loadPage(this.getAttribute('href'));
            }
        });
    });
}

/**
 * Ordenar tabla por columna
 */
function sortTable(table, columnIndex, direction) {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isNumeric = rows.some(row => {
        const cell = row.cells[columnIndex];
        return cell && !isNaN(parseFloat(cell.textContent));
    });
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex]?.textContent.trim() || '';
        const bText = b.cells[columnIndex]?.textContent.trim() || '';
        
        if (isNumeric) {
            return direction === 'asc' 
                ? parseFloat(aText) - parseFloat(bText)
                : parseFloat(bText) - parseFloat(aText);
        }
        
        return direction === 'asc'
            ? aText.localeCompare(bText)
            : bText.localeCompare(aText);
    });
    
    rows.forEach(row => table.querySelector('tbody').appendChild(row));
}

/**
 * Cargar página con AJAX
 */
function loadPage(url) {
    showLoading();
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.querySelector('main').innerHTML = html;
            initComponents();
            hideLoading();
            history.pushState(null, '', url);
        })
        .catch(error => {
            console.error('Error loading page:', error);
            hideLoading();
            showError('Error cargando la página');
        });
}

/**
 * Inicializar Notificaciones
 */
function initNotifications() {
    // Verificar notificaciones periódicamente
    setInterval(function() {
        checkNotifications();
    }, 30000); // Cada 30 segundos
}

/**
 * Verificar nuevas notificaciones
 */
function checkNotifications() {
    fetch('/api/notificaciones/no-leidas')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

/**
 * Actualizar badge de notificaciones
 */
function updateNotificationBadge(count) {
    let badge = document.querySelector('.notification-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'badge bg-danger notification-badge';
        document.querySelector('.nav-item-notificaciones')?.appendChild(badge);
    }
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';
}

/**
 * Mostrar overlay de carga
 */
function showLoading() {
    let overlay = document.querySelector('.spinner-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'spinner-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

/**
 * Ocultar overlay de carga
 */
function hideLoading() {
    const overlay = document.querySelector('.spinner-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Mostrar mensaje de error
 */
function showError(message) {
    showAlert(message, 'danger');
}

/**
 * Mostrar mensaje de éxito
 */
function showSuccess(message) {
    showAlert(message, 'success');
}

/**
 * Mostrar alerta genérica
 */
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const main = document.querySelector('main');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = alertHtml;
    main.insertBefore(tempDiv.firstElementChild, main.firstChild);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        const alert = tempDiv.firstElementChild;
        if (alert && alert.parentNode) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

/**
 * Reinicializar componentes después de cargar contenido dinámico
 */
function initComponents() {
    initSidebar();
    initTooltips();
    initPopovers();
    initModals();
    initForms();
    initTables();
}

/**
 * Formatear moneda
 */
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('es-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Formatear fecha
 */
function formatDate(dateString, options = {}) {
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    const date = new Date(dateString);
    return date.toLocaleDateString('es-US', { ...defaultOptions, ...options });
}

/**
 * Exportar tabla a CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Descargar CSV
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Hacer funciones disponibles globalmente
window.TallerSmart = {
    showLoading,
    hideLoading,
    showError,
    showSuccess,
    formatCurrency,
    formatDate,
    exportTableToCSV
};

/**
 * Búsqueda con debounce (HU-018)
 * Retrasa la ejecución de búsqueda 300ms después de dejar de escribir
 */
function initSearchDebounce() {
    const searchInputs = document.querySelectorAll('.search-input-debounce');
    
    searchInputs.forEach(function(input) {
        let debounceTimer;
        
        input.addEventListener('input', function() {
            const searchTerm = this.value;
            const targetTable = this.getAttribute('data-target');
            const callbackUrl = this.getAttribute('data-callback');
            
            // Limpiar timer anterior
            clearTimeout(debounceTimer);
            
            // Configurar nuevo timer de 300ms
            debounceTimer = setTimeout(function() {
                if (callbackUrl) {
                    // Búsqueda AJAX con callback
                    performSearch(searchTerm, callbackUrl, targetTable);
                } else if (targetTable) {
                    // Búsqueda local en tabla
                    filterTable(targetTable, searchTerm);
                }
            }, 300);
        });
    });
}

/**
 * Realizar búsqueda AJAX
 */
function performSearch(searchTerm, url, targetElement) {
    fetch(url + '?search=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(data => {
            updateSearchResults(data, targetElement);
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
        });
}

/**
 * Filtrar tabla localmente
 */
function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
    });
}

/**
 * Actualizar resultados de búsqueda
 */
function updateSearchResults(data, targetElement) {
    const container = document.querySelector(targetElement);
    if (!container) return;
    
    // Implementación específica según el tipo de resultado
    console.log('Resultados actualizados:', data);
}
