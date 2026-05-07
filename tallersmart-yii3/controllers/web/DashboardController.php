<?php

declare(strict_types=1);

namespace app\controllers\web;

use Yii;
use yii\web\Response;
use app\components\services\DashboardService;

/**
 * Controlador del Dashboard - Panel principal de control con KPIs y accesos rápidos
 */
class DashboardController extends BaseController
{
    /**
     * Acción principal: muestra el dashboard con todos los KPIs y widgets
     * 
     * @return string Vista del dashboard
     */
    public function actionIndex(): string
    {
        $service = new DashboardService();
        
        try {
            $kpis = $service->getKpis();
        } catch (\Throwable $e) {
            Yii::error('Error al cargar KPIs del dashboard: ' . $e->getMessage(), __METHOD__);
            $kpis = [
                'servicios_activos' => 0,
                'citas_hoy' => 0,
                'stock_bajo' => 0,
                'ingresos_mes' => 0,
                'trabajos_listos' => 0,
                'clientes_nuevos' => 0,
                'valor_inventario' => 0,
            ];
        }

        try {
            $citasHoy = $service->getCitasHoy();
        } catch (\Throwable $e) {
            Yii::error('Error al cargar citas de hoy: ' . $e->getMessage(), __METHOD__);
            $citasHoy = [];
        }

        try {
            $alertasStock = $service->getAlertasStock();
        } catch (\Throwable $e) {
            Yii::error('Error al cargar alertas de stock: ' . $e->getMessage(), __METHOD__);
            $alertasStock = [];
        }

        try {
            $ordenesActivas = $service->getOrdenesActivas();
        } catch (\Throwable $e) {
            Yii::error('Error al cargar órdenes activas: ' . $e->getMessage(), __METHOD__);
            $ordenesActivas = [];
        }

        try {
            $accesosRapidos = $service->getAccesosRapidos();
        } catch (\Throwable $e) {
            Yii::error('Error al cargar accesos rápidos: ' . $e->getMessage(), __METHOD__);
            $accesosRapidos = [];
        }

        return $this->render('index', [
            'kpis' => $kpis,
            'citasHoy' => $citasHoy,
            'alertasStock' => $alertasStock,
            'ordenesActivas' => $ordenesActivas,
            'accesosRapidos' => $accesosRapidos,
        ]);
    }

    /**
     * Endpoint AJAX para refresh individual de un KPI
     * 
     * @param string $kpi Nombre del KPI a refrescar
     * @return array Respuesta JSON
     */
    public function actionRefreshKpi(string $kpi): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $service = new DashboardService();
        
        try {
            // Invalidar caché específica del KPI
            $cacheKey = 'dashboard_kpi_' . $kpi . '_' . date('Y-m-d');
            Yii::$app->cache->delete($cacheKey);
            
            // Obtener valor actualizado según el KPI solicitado
            $data = match ($kpi) {
                'servicios_activos' => $service->getServiciosActivos(),
                'citas_hoy' => $service->getCitasHoyCount(),
                'stock_bajo' => $service->getStockBajoCount(),
                'ingresos_mes' => Yii::$app->formatter->asCurrency($service->getIngresosMes()),
                'trabajos_listos' => $service->getTrabajosListos(),
                'clientes_nuevos' => $service->getClientesNuevos(),
                'valor_inventario' => Yii::$app->formatter->asCurrency($service->getValorInventario()),
                default => throw new \InvalidArgumentException("KPI desconocido: {$kpi}"),
            };

            return [
                'success' => true,
                'kpi' => $kpi,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Yii::error('Error al refrescar KPI ' . $kpi . ': ' . $e->getMessage(), __METHOD__);
            
            return [
                'success' => false,
                'error' => 'No se pudo actualizar el indicador',
            ];
        }
    }

    /**
     * Endpoint AJAX para refresh completo de todos los datos del dashboard
     * 
     * @return array Respuesta JSON
     */
    public function actionRefreshAll(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $service = new DashboardService();
        
        try {
            // Invalidar toda la caché de KPIs
            $service->invalidateKpiCache();
            
            return [
                'success' => true,
                'data' => [
                    'kpis' => $service->getKpis(),
                    'citasHoy' => $service->getCitasHoy(),
                    'alertasStock' => $service->getAlertasStock(),
                    'ordenesActivas' => $service->getOrdenesActivas(),
                ],
            ];
        } catch (\Throwable $e) {
            Yii::error('Error al refrescar dashboard completo: ' . $e->getMessage(), __METHOD__);
            
            return [
                'success' => false,
                'error' => 'No se pudo actualizar el dashboard',
            ];
        }
    }
}
