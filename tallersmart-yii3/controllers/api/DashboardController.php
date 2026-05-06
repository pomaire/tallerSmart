<?php

declare(strict_types=1);

namespace app\controllers\api;

use yii\web\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use yii\filters\Cors;
use Yii;

/**
 * Controlador de Dashboard - Estadísticas y reportes
 */
class DashboardController extends BaseController
{
    public $modelClass = null;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        // Permitir acceso sin autenticación para este controller (se valida manualmente)
        unset($behaviors['authenticator']);
        return $behaviors;
    }

    /**
     * Obtener estadísticas generales del dashboard
     * GET /api/dashboard/stats
     */
    public function actionStats(): array
    {
        // Total clientes
        $totalClientes = \app\models\Cliente::find()->where(['activo' => true])->count();

        // Total vehículos
        $totalVehiculos = \app\models\Vehiculo::find()->where(['activo' => true])->count();

        // Citas hoy
        $hoy = date('Y-m-d');
        $citasHoy = \app\models\Cita::find()
            ->where(['>=', 'fecha_hora', $hoy . ' 00:00:00'])
            ->andWhere(['<=', 'fecha_hora', $hoy . ' 23:59:59'])
            ->count();

        // Citas pendientes
        $citasPendientes = \app\models\Cita::find()
            ->where(['estado' => 'pendiente'])
            ->count();

        // Órdenes en progreso (HU-010: open + in_progress + waiting_parts)
        $trabajosActivos = \app\models\OrdenServicio::getTrabajosActivosCount();

        // Ingresos del mes
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');
        
        $ingresosMes = \app\models\Pago::find()
            ->where(['>=', 'fecha_pago', $inicioMes])
            ->andWhere(['<=', 'fecha_pago', $finMes])
            ->sum('monto');

        // Items con stock bajo
        $stockBajo = \app\models\InventoryItem::find()
            ->where(['<=', 'stock_actual', 'stock_minimo'])
            ->andWhere(['activo' => true])
            ->count();

        return [
            'success' => true,
            'data' => [
                'total_clientes' => (int)$totalClientes,
                'total_vehiculos' => (int)$totalVehiculos,
                'citas_hoy' => (int)$citasHoy,
                'citas_pendientes' => (int)$citasPendientes,
                'trabajos_activos' => (int)$trabajosActivos,
                'ingresos_mes' => (float)$ingresosMes,
                'items_stock_bajo' => (int)$stockBajo,
            ],
        ];
    }

    /**
     * Obtener citas próximas
     * GET /api/dashboard/proximas-citas
     */
    public function actionProximasCitas(): array
    {
        $citas = \app\models\Cita::find()
            ->joinWith(['cliente', 'vehiculo'])
            ->where(['>=', 'cita.fecha_hora', date('Y-m-d H:i:s')])
            ->andWhere(['cita.estado' => 'pendiente'])
            ->orderBy(['cita.fecha_hora' => SORT_ASC])
            ->limit(10)
            ->all();

        $data = [];
        foreach ($citas as $cita) {
            $data[] = [
                'id' => $cita->id,
                'fecha_hora' => $cita->fecha_hora,
                'cliente' => $cita->cliente->nombre ?? 'N/A',
                'vehiculo' => $cita->vehiculo ? $cita->vehiculo->marca . ' ' . $cita->vehiculo->modelo : 'N/A',
                'placa' => $cita->vehiculo->placa ?? 'N/A',
                'estado' => $cita->estado,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Obtener órdenes recientes
     * GET /api/dashboard/ordenes-recientes
     */
    public function actionOrdenesRecientes(): array
    {
        $ordenes = \app\models\OrdenServicio::find()
            ->joinWith(['cliente', 'vehiculo'])
            ->orderBy(['orden_servicio.created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        $data = [];
        foreach ($ordenes as $orden) {
            $data[] = [
                'id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
                'created_at' => $orden->created_at,
                'cliente' => $orden->cliente->nombre ?? 'N/A',
                'vehiculo' => $orden->vehiculo ? $orden->vehiculo->marca . ' ' . $orden->vehiculo->modelo : 'N/A',
                'estado' => $orden->estado,
                'total' => (float)$orden->total,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Obtener items con stock bajo
     * GET /api/dashboard/stock-bajo
     */
    public function actionStockBajo(): array
    {
        $items = \app\models\InventoryItem::find()
            ->where(['<=', 'stock_actual', 'stock_minimo'])
            ->andWhere(['activo' => true])
            ->orderBy(['stock_actual' => SORT_ASC])
            ->limit(10)
            ->all();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id' => $item->id,
                'codigo' => $item->codigo,
                'nombre' => $item->nombre,
                'stock_actual' => (int)$item->stock_actual,
                'stock_minimo' => (int)$item->stock_minimo,
                'categoria' => $item->categoria,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Obtener resumen de ingresos por mes
     * GET /api/dashboard/ingresos-meses
     */
    public function actionIngresosMeses(): array
    {
        $meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $inicioMes = date('Y-m-01', strtotime("-{$i} months"));
            $finMes = date('Y-m-t', strtotime("-{$i} months"));
            
            $ingresos = \app\models\Pago::find()
                ->where(['>=', 'fecha_pago', $inicioMes])
                ->andWhere(['<=', 'fecha_pago', $finMes])
                ->sum('monto');

            $meses[] = [
                'mes' => date('Y-m', strtotime("-{$i} months")),
                'nombre' => date('F Y', strtotime("-{$i} months")),
                'ingresos' => (float)($ingresos ?? 0),
            ];
        }

        return [
            'success' => true,
            'data' => $meses,
        ];
    }

    /**
     * Obtener servicios más solicitados
     * GET /api/dashboard/servicios-mas-solicitados
     * @param string|null $fecha_desde Fecha inicial del rango (opcional)
     * @param string|null $fecha_hasta Fecha final del rango (opcional)
     * @param int|null $limite Cantidad máxima de resultados (default: 10)
     */
    public function actionServiciosMasSolicitados(): array
    {
        $request = Yii::$app->request;
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $limite = (int)$request->get('limite', 10);

        // Construir query para contar servicios por orden
        $query = (new \yii\db\Query())
            ->select([
                'servicio.id',
                'servicio.nombre',
                'servicio.precio',
                'categoria.nombre as categoria_nombre',
                'COUNT(orden_servicio_detalle.id) as cantidad_ordenes',
                'SUM(orden_servicio_detalle.cantidad) as cantidad_total',
            ])
            ->from('orden_servicio_detalle')
            ->innerJoin('servicio', 'orden_servicio_detalle.servicio_id = servicio.id')
            ->innerJoin('orden_servicio', 'orden_servicio_detalle.orden_servicio_id = orden_servicio.id')
            ->leftJoin('categoria', 'servicio.categoria_id = categoria.id')
            ->where(['orden_servicio_detalle.tipo' => 'servicio'])
            ->groupBy(['servicio.id', 'servicio.nombre', 'servicio.precio', 'categoria.nombre'])
            ->orderBy(['cantidad_ordenes' => SORT_DESC])
            ->limit($limite);

        // Aplicar filtros de fecha si existen
        if ($fechaDesde) {
            $query->andWhere(['>=', 'orden_servicio.created_at', $fechaDesde]);
        }
        if ($fechaHasta) {
            $query->andWhere(['<=', 'orden_servicio.created_at', $fechaHasta]);
        }

        $resultados = $query->all();

        // Formatear datos para respuesta
        $data = [];
        foreach ($resultados as $row) {
            $data[] = [
                'servicio_id' => (int)$row['id'],
                'nombre' => $row['nombre'],
                'categoria' => $row['categoria_nombre'] ?? 'Sin categoría',
                'precio_base' => (float)$row['precio'],
                'cantidad_ordenes' => (int)$row['cantidad_ordenes'],
                'cantidad_total' => (int)$row['cantidad_total'],
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'metadata' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'total_resultados' => count($data),
            ],
        ];
    }

    /**
     * Obtener valor total del inventario (HU-017)
     * GET /api/dashboard/inventario-valor
     */
    public function actionInventarioValor(): array
    {
        $valorTotal = \app\models\InventoryItem::find()
            ->where(['activo' => true])
            ->sum('precio_costo * stock_actual');

        return [
            'success' => true,
            'data' => [
                'valor_total' => (float)($valorTotal ?? 0),
                'valor_formateado' => '$' . number_format($valorTotal ?? 0, 2),
            ],
        ];
    }

    /**
     * Obtener categorías con stock bajo (HU-030)
     * GET /api/dashboard/categorias-stock-bajo
     */
    public function actionCategoriasStockBajo(): array
    {
        $categoriasAfectadas = (new \yii\db\Query())
            ->select(['categoria', 'COUNT(*) as cantidad_items'])
            ->from('inventory_item')
            ->where(['<=', 'stock_actual', new \yii\db\Expression('stock_minimo')])
            ->andWhere(['activo' => true])
            ->groupBy(['categoria'])
            ->all();

        $totalCategorias = count($categoriasAfectadas);

        $data = [];
        foreach ($categoriasAfectadas as $cat) {
            $data[] = [
                'categoria' => $cat['categoria'],
                'cantidad_items' => (int)$cat['cantidad_items'],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'total_categorias_afectadas' => $totalCategorias,
                'categorias' => $data,
            ],
        ];
    }

    /**
     * Obtener notificaciones de stock bajo (HU-022)
     * GET /api/dashboard/notificaciones-stock
     */
    public function actionNotificacionesStock(): array
    {
        $itemsBajoStock = \app\models\InventoryItem::find()
            ->where(['<=', 'stock_actual', 'stock_minimo'])
            ->andWhere(['activo' => true])
            ->all();

        $notificaciones = [];
        foreach ($itemsBajoStock as $item) {
            $notificaciones[] = [
                'id' => $item->id,
                'tipo' => 'stock_bajo',
                'titulo' => 'Stock Bajo: ' . $item->nombre,
                'mensaje' => $item->stock_actual == 0 
                    ? 'Sin stock disponible' 
                    : 'Quedan ' . $item->stock_actual . ' unidades (mínimo: ' . $item->stock_minimo . ')',
                'prioridad' => $item->stock_actual == 0 ? 'alta' : 'media',
                'leido' => false,
                'fecha' => date('Y-m-d H:i:s'),
                'icono_animado' => true, // HU-016
            ];
        }

        return [
            'success' => true,
            'data' => $notificaciones,
            'metadata' => [
                'total_notificaciones' => count($notificaciones),
                'no_leidas' => count($notificaciones),
            ],
        ];
    }

    /**
     * Reporte de servicios por técnico (HU-027)
     * GET /api/dashboard/reporte-tecnicos
     */
    public function actionReporteTecnicos(): array
    {
        $request = Yii::$app->request;
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        // Query para obtener estadísticas por técnico
        $query = (new \yii\db\Query())
            ->select([
                'tecnico.id as tecnico_id',
                'tecnico.nombre as tecnico_nombre',
                'tecnico.apellido as tecnico_apellido',
                'COUNT(DISTINCT orden_servicio.id) as ordenes_atendidas',
                'SUM(CASE WHEN orden_servicio.estado = "entregada" THEN 1 ELSE 0 END) as ordenes_completadas',
                'SUM(CASE WHEN orden_servicio.estado IN ("abierto", "en_progreso", "esperando_repuestos", "listo_para_entrega") THEN 1 ELSE 0 END) as ordenes_activas',
            ])
            ->from('tecnico')
            ->leftJoin('orden_servicio', 'orden_servicio.tecnico_id = tecnico.id')
            ->groupBy(['tecnico.id', 'tecnico.nombre', 'tecnico.apellido'])
            ->orderBy(['ordenes_atendidas' => SORT_DESC]);

        // Aplicar filtros de fecha si existen
        if ($fechaDesde) {
            $query->andWhere(['>=', 'orden_servicio.created_at', $fechaDesde]);
        }
        if ($fechaHasta) {
            $query->andWhere(['<=', 'orden_servicio.created_at', $fechaHasta]);
        }

        $resultados = $query->all();

        // Formatear datos para respuesta
        $data = [];
        foreach ($resultados as $row) {
            // Calcular horas estimadas basadas en los servicios de las órdenes
            $horasEstimadas = 0;
            if ($row['ordenes_atendidas'] > 0) {
                $ordenesIds = \app\models\OrdenServicio::find()
                    ->select(['id'])
                    ->where(['tecnico_id' => $row['tecnico_id']])
                    ->column();
                
                if (!empty($ordenesIds)) {
                    $duracionTotal = \app\models\OrdenServicioDetalle::find()
                        ->joinWith(['servicio'])
                        ->where(['in', 'orden_servicio_detalle.orden_servicio_id', $ordenesIds])
                        ->sum('servicio.duracion_estimada');
                    
                    $horasEstimadas = $duracionTotal ? round($duracionTotal / 60, 2) : 0;
                }
            }

            $data[] = [
                'tecnico_id' => (int)$row['tecnico_id'],
                'nombre_completo' => trim($row['tecnico_nombre'] . ' ' . $row['tecnico_apellido']),
                'ordenes_atendidas' => (int)$row['ordenes_atendidas'],
                'ordenes_completadas' => (int)$row['ordenes_completadas'],
                'ordenes_activas' => (int)$row['ordenes_activas'],
                'horas_estimadas' => $horasEstimadas,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'metadata' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'total_tecnicos' => count($data),
            ],
        ];
    }
}
