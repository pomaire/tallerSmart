<?php

return [
    'adminEmail' => 'admin@tallersmart.com',
    'supportEmail' => 'soporte@tallersmart.com',
    'appName' => 'TallerSmart',
    'appVersion' => '1.0.0',
    
    // Configuración del menú lateral estructurado por módulos
    'menuItems' => [
        // 1. Seguridad y Accesos (RBAC)
        [
            'label' => 'Seguridad',
            'icon' => 'fa-shield-alt',
            'items' => [
                ['label' => 'Roles y Permisos', 'url' => ['/rbac/rol/index'], 'icon' => 'fa-user-tag'],
                ['label' => 'Usuarios', 'url' => ['/usuario/index'], 'icon' => 'fa-users'],
            ]
        ],
        // 2. Categorías
        [
            'label' => 'Categorías',
            'url' => ['/categoria/index'],
            'icon' => 'fa-tags'
        ],
        // 3. Servicios
        [
            'label' => 'Servicios',
            'url' => ['/servicio/index'],
            'icon' => 'fa-concierge-bell'
        ],
        // 4. Clientes
        [
            'label' => 'Clientes',
            'url' => ['/cliente/index'],
            'icon' => 'fa-id-card'
        ],
        // 5. Vehículos
        [
            'label' => 'Vehículos',
            'url' => ['/vehiculo/index'],
            'icon' => 'fa-truck-pickup'
        ],
        // 6. Inventario
        [
            'label' => 'Inventario',
            'url' => ['/inventario/index'],
            'icon' => 'fa-boxes'
        ],
        // 7. Técnicos
        [
            'label' => 'Técnicos',
            'url' => ['/tecnico/index'],
            'icon' => 'fa-tools'
        ],
        // 8. Citas
        [
            'label' => 'Citas',
            'url' => ['/cita/index'],
            'icon' => 'fa-calendar-check'
        ],
        // 9. Órdenes de Servicio
        [
            'label' => 'Órdenes de Servicio',
            'url' => ['/orden-servicio/index'],
            'icon' => 'fa-clipboard-list'
        ],
        // 10. Pagos
        [
            'label' => 'Pagos',
            'url' => ['/pago/index'],
            'icon' => 'fa-money-bill-wave'
        ],
        // 11. Notificaciones
        [
            'label' => 'Notificaciones',
            'url' => ['/notificacion/index'],
            'icon' => 'fa-bell'
        ],
        // 12. AuditLog
        [
            'label' => 'Auditoría',
            'url' => ['/audit-log/index'],
            'icon' => 'fa-history'
        ],
        // 13. Dashboard
        [
            'label' => 'Dashboard',
            'url' => ['/dashboard/index'],
            'icon' => 'fa-chart-line'
        ],
        // 14. Manual de Usuario
        [
            'label' => 'Manual de Usuario',
            'url' => ['/site/manual'],
            'icon' => 'fa-book-open',
            'target' => '_blank'
        ],
    ],
    
    // Configuración de paginación
    'pageSize' => 10,
    'pageSizes' => [10, 25, 50, 100],
    
    // Configuración de fechas
    'dateFormat' => 'dd/MM/yyyy',
    'datetimeFormat' => 'dd/MM/yyyy HH:mm:ss',
    
    // Estados del sistema
    'statusOptions' => [
        1 => 'Activo',
        0 => 'Inactivo',
    ],
    
    // Estados de citas
    'citaStatusOptions' => [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'en_proceso' => 'En Proceso',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
    ],
    
    // Estados de órdenes de servicio
    'ordenStatusOptions' => [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En Proceso',
        'esperando_repuesto' => 'Esperando Repuesto',
        'completada' => 'Completada',
        'entregada' => 'Entregada',
        'cancelada' => 'Cancelada',
    ],
    
    // Tipos de movimiento de inventario
    'inventoryMovementTypes' => [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
        'ajuste' => 'Ajuste',
        'devolucion' => 'Devolución',
    ],
];
