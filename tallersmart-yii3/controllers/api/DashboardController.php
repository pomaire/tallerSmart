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

        // Órdenes en progreso
        $ordenesProgreso = \app\models\OrdenServicio::find()
            ->where(['estado' => 'en_progreso'])
            ->count();

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
                'ordenes_en_progreso' => (int)$ordenesProgreso,
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
}
