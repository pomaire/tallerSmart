<?php

namespace app\controllers\web;

use Yii;

/**
 * Controlador para el Manual de Usuario
 * Muestra la documentación interactiva del sistema TallerSmart
 */
class ManualUsuarioController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // El manual debe ser accesible solo para usuarios autenticados
        $behaviors['access']['rules'] = [
            [
                'actions' => ['index'],
                'allow' => true,
                'roles' => ['@'], // Solo usuarios autenticados
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Página principal del manual de usuario
     * Muestra todos los módulos del sistema con su documentación
     * 
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'main';
        
        return $this->render('index', [
            'modulos' => $this->obtenerModulosManual()
        ]);
    }

    /**
     * Obtiene la estructura completa del manual de usuario
     * Cada módulo incluye: título, descripción, características, pasos y consejos
     * 
     * @return array
     */
    private function obtenerModulosManual(): array
    {
        return [
            [
                'titulo' => 'RBAC - Roles y Permisos',
                'descripcion' => 'Gestión de usuarios, roles y permisos del sistema. Controla quién puede acceder a cada funcionalidad.',
                'icono' => 'fa-users-gear',
                'color' => 'bg-blue-600',
                'caracteristicas' => [
                    'Creación y administración de roles personalizados',
                    'Asignación granular de permisos por módulo',
                    'Gestión de usuarios activos/inactivos',
                    'Auditoría de accesos y cambios',
                    'Jerarquía de roles con niveles de privilegio'
                ],
                'pasos' => [
                    'Navega al menú "RBAC" en la barra lateral',
                    'Selecciona la pestaña "Roles" para crear o editar roles',
                    'Marca los permisos que tendrá cada rol',
                    'En la pestaña "Usuarios", asigna roles a cada usuario',
                    'Los cambios se aplican inmediatamente al iniciar sesión'
                ],
                'consejos' => [
                    'Crea roles específicos para cada área del taller',
                    'Revisa periódicamente los permisos asignados',
                    'Desactiva usuarios que ya no trabajen en el taller',
                    'Utiliza la jerarquía para organizar los niveles de acceso'
                ]
            ],
            [
                'titulo' => 'Categorías',
                'descripcion' => 'Organiza los servicios del taller en categorías para una mejor gestión y búsqueda.',
                'icono' => 'fa-tags',
                'color' => 'bg-green-600',
                'caracteristicas' => [
                    'Clasificación jerárquica de servicios',
                    'Códigos únicos por categoría',
                    'Estado activo/inactivo',
                    'Descripción detallada',
                    'Iconos y colores personalizables'
                ],
                'pasos' => [
                    'Ve al menú "Categorías"',
                    'Haz clic en "Nueva Categoría"',
                    'Ingresa código, nombre y descripción',
                    'Selecciona un icono y color opcional',
                    'Guarda los cambios'
                ],
                'consejos' => [
                    'Usa códigos cortos y memorables',
                    'Agrupa servicios similares en la misma categoría',
                    'Mantén las categorías actualizadas según nuevos servicios'
                ]
            ],
            [
                'titulo' => 'Servicios',
                'descripcion' => 'Catálogo completo de servicios que ofrece el taller con precios y tiempos estimados.',
                'icono' => 'fa-wrench',
                'color' => 'bg-orange-600',
                'caracteristicas' => [
                    'Precios en pesos chilenos (CLP)',
                    'Duración estimada en minutos',
                    'Relación con categorías',
                    'Códigos de servicio únicos',
                    'Control de estado (activo/inactivo)'
                ],
                'pasos' => [
                    'Accede al menú "Servicios"',
                    'Presiona "Nuevo Servicio"',
                    'Completa código, nombre, categoría, precio y duración',
                    'Opcionalmente agrega descripción detallada',
                    'Guarda el servicio'
                ],
                'consejos' => [
                    'Actualiza precios regularmente según costos',
                    'Incluye todos los servicios estándar del taller',
                    'Usa descripciones claras para evitar confusiones'
                ]
            ],
            [
                'titulo' => 'Clientes',
                'descripcion' => 'Administración de la base de datos de clientes con validación de RUT chileno.',
                'icono' => 'fa-users',
                'color' => 'bg-purple-600',
                'caracteristicas' => [
                    'Validación automática de RUT chileno',
                    'Formateo automático de teléfono (+56)',
                    'Historial de vehículos por cliente',
                    'Búsqueda rápida por nombre, email o teléfono',
                    'Notas y comentarios personalizados'
                ],
                'pasos' => [
                    'Ve al menú "Clientes"',
                    'Click en "Nuevo Cliente"',
                    'Ingresa RUT (se valida automáticamente)',
                    'Completa nombre, email y teléfono',
                    'Agrega dirección y notas opcionales',
                    'Guarda el cliente'
                ],
                'consejos' => [
                    'Verifica siempre el RUT antes de guardar',
                    'Mantén actualizados los datos de contacto',
                    'Usa las notas para información relevante del cliente'
                ]
            ],
            [
                'titulo' => 'Vehículos',
                'descripcion' => 'Gestión de la flota de vehículos asociados a cada cliente.',
                'icono' => 'fa-car',
                'color' => 'bg-red-600',
                'caracteristicas' => [
                    'Registro de marca, modelo y año',
                    'Número de placa (patente) único',
                    'VIN (número de chasis)',
                    'Kilometraje actual',
                    'Tipo de combustible',
                    'Historial de servicios por vehículo'
                ],
                'pasos' => [
                    'Selecciona un cliente en el menú "Clientes"',
                    'En la sección "Vehículos", click en "Agregar Vehículo"',
                    'Completa todos los datos del vehículo',
                    'Verifica que la placa sea correcta',
                    'Guarda el vehículo'
                ],
                'consejos' => [
                    'Registra todos los vehículos de cada cliente',
                    'Actualiza el kilometraje en cada servicio',
                    'Usa el VIN para identificación única'
                ]
            ],
            [
                'titulo' => 'Citas',
                'descripcion' => 'Sistema de agendamiento de citas para optimizar la planificación del taller.',
                'icono' => 'fa-calendar-check',
                'color' => 'bg-indigo-600',
                'caracteristicas' => [
                    'Calendario interactivo visual',
                    'Estados: pendiente, confirmada, en progreso, completada, cancelada',
                    'Asignación de servicios múltiples',
                    'Notificaciones automáticas',
                    'Detección de conflictos de horario'
                ],
                'pasos' => [
                    'Ve al menú "Citas"',
                    'Click en "Nueva Cita"',
                    'Selecciona cliente y vehículo',
                    'Elige fecha y hora disponibles',
                    'Agrega los servicios a realizar',
                    'Confirma la cita'
                ],
                'consejos' => [
                    'Confirma las citas con anticipación',
                    'Deja margen entre citas para imprevistos',
                    'Actualiza el estado según avance el trabajo'
                ]
            ],
            [
                'titulo' => 'Técnicos',
                'descripcion' => 'Gestión del personal técnico y sus especialidades.',
                'icono' => 'fa-user-gear',
                'color' => 'bg-teal-600',
                'caracteristicas' => [
                    'Perfiles por técnico',
                    'Especialidades múltiples',
                    'Niveles: junior, semi-senior, senior, master',
                    'Horarios y disponibilidad',
                    'Estadísticas de productividad'
                ],
                'pasos' => [
                    'Accede al menú "Técnicos"',
                    'Click en "Nuevo Técnico"',
                    'Asigna un usuario del sistema',
                    'Define especialidad y nivel',
                    'Configura horario de trabajo',
                    'Guarda el perfil'
                ],
                'consejos' => [
                    'Asigna trabajos según especialidad',
                    'Considera el nivel para tareas complejas',
                    'Monitorea la carga de trabajo por técnico'
                ]
            ],
            [
                'titulo' => 'Órdenes de Servicio',
                'descripcion' => 'Gestión completa de órdenes de servicio desde creación hasta entrega.',
                'icono' => 'fa-file-invoice',
                'color' => 'bg-yellow-600',
                'caracteristicas' => [
                    'Folio único automático',
                    'Estados personalizables',
                    'Prioridades: baja, media, alta, urgente',
                    'Desglose de servicios y repuestos',
                    'Historial de cambios de estado',
                    'Cálculo automático de totales'
                ],
                'pasos' => [
                    'Ve al menú "Órdenes de Servicio"',
                    'Click en "Nueva Orden"',
                    'Selecciona cliente y vehículo',
                    'Agrega servicios y/o repuestos',
                    'Asigna técnico responsable',
                    'Define prioridad y fecha estimada',
                    'Genera la orden'
                ],
                'consejos' => [
                    'Documenta bien las notas del cliente',
                    'Actualiza el estado según avance el trabajo',
                    'Usa prioridades para organizar el flujo'
                ]
            ],
            [
                'titulo' => 'Inventario',
                'descripcion' => 'Control de stock de repuestos y productos del taller.',
                'icono' => 'fa-boxes-stacked',
                'color' => 'bg-emerald-600',
                'caracteristicas' => [
                    'SKU único por producto',
                    'Control de stock mínimo',
                    'Movimientos de entrada/salida',
                    'Alertas de stock bajo',
                    'Costos y precios de venta',
                    'Ubicación física en bodega'
                ],
                'pasos' => [
                    'Accede al menú "Inventario"',
                    'Para nuevo item, click en "Agregar Producto"',
                    'Ingresa SKU, nombre y categoría',
                    'Define stock mínimo y ubicación',
                    'Registra costo y precio de venta',
                    'Guarda el producto'
                ],
                'consejos' => [
                    'Realiza inventarios físicos periódicos',
                    'Configura alertas de stock mínimo',
                    'Registra todos los movimientos'
                ]
            ],
            [
                'titulo' => 'Pagos',
                'descripcion' => 'Gestión de pagos y cobranzas de órdenes de servicio.',
                'icono' => 'fa-cash-register',
                'color' => 'bg-cyan-600',
                'caracteristicas' => [
                    'Múltiples métodos de pago',
                    'Pagos parciales y abonos',
                    'Integración con documentos tributarios',
                    'Historial de transacciones',
                    'Conciliación de caja'
                ],
                'pasos' => [
                    'Ve al menú "Pagos"',
                    'Selecciona la orden a pagar',
                    'Elige método de pago',
                    'Ingresa monto (puede ser parcial)',
                    'Procesa el pago',
                    'Entrega comprobante al cliente'
                ],
                'consejos' => [
                    'Verifica montos antes de procesar',
                    'Emite boleta o factura según corresponda',
                    'Cierra caja diariamente'
                ]
            ],
            [
                'titulo' => 'Documentos Tributarios',
                'descripcion' => 'Emisión de boletas, facturas y notas de crédito integradas con SII.',
                'icono' => 'fa-file-invoice-dollar',
                'color' => 'bg-rose-600',
                'caracteristicas' => [
                    'Timbraje automático SII',
                    'Boletas electrónicas',
                    'Notas de crédito',
                    'Facturas (empresas)',
                    'Cola de procesamiento',
                    'Reintentos automáticos'
                ],
                'pasos' => [
                    'Desde una orden, click en "Emitir Documento"',
                    'Selecciona tipo de documento',
                    'Verifica datos del cliente',
                    'Confirma montos',
                    'Envía a timbrar',
                    'Descarga PDF para el cliente'
                ],
                'consejos' => [
                    'Verifica RUT antes de emitir',
                    'Guarda copia digital de cada documento',
                    'Revisa cola de documentos pendientes'
                ]
            ],
            [
                'titulo' => 'Dashboard',
                'descripcion' => 'Panel principal con indicadores clave y resumen de actividad.',
                'icono' => 'fa-chart-line',
                'color' => 'bg-slate-600',
                'caracteristicas' => [
                    'Resumen de órdenes del día',
                    'Ingresos mensuales',
                    'Citas próximas',
                    'Alertas y notificaciones',
                    'Estadísticas de técnicos',
                    'Gráficos de rendimiento'
                ],
                'pasos' => [
                    'El dashboard se muestra al iniciar sesión',
                    'Filtra por fecha para ver períodos específicos',
                    'Click en elementos para ver detalles',
                    'Exporta reportes si es necesario'
                ],
                'consejos' => [
                    'Revisa el dashboard diariamente',
                    'Configura tus notificaciones preferidas',
                    'Usa los filtros para análisis específicos'
                ]
            ],
            [
                'titulo' => 'Auditoría',
                'descripcion' => 'Registro detallado de todas las acciones realizadas en el sistema.',
                'icono' => 'fa-shield-halved',
                'color' => 'bg-zinc-600',
                'caracteristicas' => [
                    'Log de todas las operaciones CRUD',
                    'Registro de login/logout',
                    'IP y user agent',
                    'Datos antes y después de cambios',
                    'Búsqueda y filtrado avanzado',
                    'Archivado automático'
                ],
                'pasos' => [
                    'Accede al menú "Auditoría"',
                    'Filtra por fecha, usuario o módulo',
                    'Selecciona un registro para ver detalles',
                    'Compara datos antiguos y nuevos',
                    'Exporta logs si es necesario'
                ],
                'consejos' => [
                    'Revisa logs ante inconsistencias',
                    'Exporta logs periódicamente para respaldo',
                    'Configura alertas para acciones sensibles'
                ]
            ]
        ];
    }
}
