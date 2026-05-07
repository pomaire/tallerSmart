<?php

declare(strict_types=1);

namespace app\components\services;

use Yii;
use yii\base\Component;
use app\models\OrdenServicio;
use app\models\Cita;
use app\models\InventoryItem;
use app\models\Pago;
use app\models\Cliente;

/**
 * Servicio de Dashboard - Proporciona KPIs y datos agregados para el panel principal
 */
class DashboardService extends Component
{
    /**
     * TTL por defecto para caché de KPIs (en segundos)
     */
    private const DEFAULT_CACHE_TTL = 60;

    /**
     * Prefijo para claves de caché
     */
    private const CACHE_PREFIX = 'dashboard_kpi_';

    /**
     * Obtiene todos los KPIs principales del dashboard
     * 
     * @return array Array con todos los KPIs
     */
    public function getKpis(): array
    {
        return [
            'servicios_activos' => $this->getServiciosActivos(),
            'citas_hoy' => $this->getCitasHoyCount(),
            'stock_bajo' => $this->getStockBajoCount(),
            'ingresos_mes' => $this->getIngresosMes(),
            'trabajos_listos' => $this->getTrabajosListos(),
            'clientes_nuevos' => $this->getClientesNuevos(),
            'valor_inventario' => $this->getValorInventario(),
        ];
    }

    /**
     * Obtiene un KPI específico usando caché
     * 
     * @param string $nombre Nombre del KPI
     * @param callable $calculator Función que calcula el valor del KPI
     * @param int $ttl Tiempo de vida de la caché en segundos
     * @return mixed Valor del KPI
     */
    public function getKpiConCache(string $nombre, callable $calculator, int $ttl = self::DEFAULT_CACHE_TTL): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $nombre . '_' . date('Y-m-d');
        
        $cachedValue = Yii::$app->cache->get($cacheKey);
        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $value = $calculator();
        Yii::$app->cache->set($cacheKey, $value, $ttl);

        return $value;
    }

    /**
     * Invalida la caché de todos los KPIs
     */
    public function invalidateKpiCache(): void
    {
        foreach ([
            'servicios_activos',
            'citas_hoy',
            'stock_bajo',
            'ingresos_mes',
            'trabajos_listos',
            'clientes_nuevos',
            'valor_inventario',
        ] as $kpi) {
            $cacheKey = self::CACHE_PREFIX . $kpi . '_' . date('Y-m-d');
            Yii::$app->cache->delete($cacheKey);
        }
    }

    /**
     * Calcula el número de servicios activos
     * COUNT WHERE estado IN ('abierto', 'en_progreso', 'esperando_repuestos')
     * 
     * @return int Número de servicios activos
     */
    public function getServiciosActivos(): int
    {
        return $this->getKpiConCache('servicios_activos', function() {
            return OrdenServicio::find()
                ->where(['in', 'estado', ['abierto', 'en_progreso', 'esperando_repuestos']])
                ->count();
        });
    }

    /**
     * Calcula el número de citas programadas para hoy
     * COUNT WHERE fecha = hoy AND estado != 'cancelada'
     * 
     * @return int Número de citas hoy
     */
    public function getCitasHoyCount(): int
    {
        return $this->getKpiConCache('citas_hoy', function() {
            $hoy = date('Y-m-d');
            return Cita::find()
                ->where(['>=', 'fecha_hora', $hoy . ' 00:00:00'])
                ->andWhere(['<=', 'fecha_hora', $hoy . ' 23:59:59'])
                ->andWhere(['!=', 'estado', 'cancelada'])
                ->count();
        });
    }

    /**
     * Calcula el número de items con stock bajo
     * COUNT WHERE cantidad <= stock_minimo
     * 
     * @return int Número de items con stock bajo
     */
    public function getStockBajoCount(): int
    {
        return $this->getKpiConCache('stock_bajo', function() {
            return InventoryItem::find()
                ->where('stock_actual <= stock_minimo')
                ->andWhere(['activo' => true])
                ->count();
        });
    }

    /**
     * Calcula los ingresos del mes actual
     * SUM(monto) WHERE MONTH(created_at) = mes actual AND estado = 'completado'
     * 
     * @return float Monto total de ingresos del mes
     */
    public function getIngresosMes(): float
    {
        return $this->getKpiConCache('ingresos_mes', function() {
            $inicioMes = date('Y-m-01');
            $finMes = date('Y-m-t');
            
            $monto = Pago::find()
                ->where(['>=', 'fecha_pago', $inicioMes])
                ->andWhere(['<=', 'fecha_pago', $finMes])
                ->andWhere(['estado' => 'completado'])
                ->sum('monto');
            
            return (float)($monto ?? 0);
        });
    }

    /**
     * Calcula el número de trabajos listos para entrega
     * COUNT WHERE estado = 'listo_para_entrega'
     * 
     * @return int Número de trabajos listos
     */
    public function getTrabajosListos(): int
    {
        return $this->getKpiConCache('trabajos_listos', function() {
            return OrdenServicio::find()
                ->where(['estado' => 'listo_para_entrega'])
                ->count();
        });
    }

    /**
     * Calcula el número de clientes nuevos en el mes actual
     * COUNT WHERE MONTH(created_at) = mes actual
     * 
     * @return int Número de clientes nuevos
     */
    public function getClientesNuevos(): int
    {
        return $this->getKpiConCache('clientes_nuevos', function() {
            $inicioMes = date('Y-m-01');
            return Cliente::find()
                ->where(['>=', 'created_at', $inicioMes])
                ->count();
        });
    }

    /**
     * Calcula el valor total del inventario
     * SUM(precio_costo * stock_actual)
     * 
     * @return float Valor total del inventario
     */
    public function getValorInventario(): float
    {
        return $this->getKpiConCache('valor_inventario', function() {
            $valor = InventoryItem::find()
                ->where(['activo' => true])
                ->sum('precio_costo * stock_actual');
            
            return (float)($valor ?? 0);
        });
    }

    /**
     * Obtiene las citas programadas para hoy con detalles
     * 
     * @return array Lista de citas de hoy
     */
    public function getCitasHoy(): array
    {
        $hoy = date('Y-m-d');
        $citas = Cita::find()
            ->joinWith(['cliente', 'vehiculo'])
            ->where(['>=', 'fecha_hora', $hoy . ' 00:00:00'])
            ->andWhere(['<=', 'fecha_hora', $hoy . ' 23:59:59'])
            ->andWhere(['!=', 'estado', 'cancelada'])
            ->orderBy(['fecha_hora' => SORT_ASC])
            ->limit(10)
            ->all();

        $resultado = [];
        foreach ($citas as $cita) {
            $resultado[] = [
                'id' => $cita->id,
                'fecha_hora' => $cita->fecha_hora,
                'estado' => $cita->estado,
                'cliente' => $cita->cliente?->nombreCompleto ?? 'N/A',
                'vehiculo' => $cita->vehiculo ? $cita->vehiculo->marca . ' ' . $cita->vehiculo->modelo : 'N/A',
                'placa' => $cita->vehiculo?->placa ?? 'N/A',
                'servicio' => $cita->descripcion ?? 'Sin descripción',
            ];
        }

        return $resultado;
    }

    /**
     * Obtiene las alertas de stock crítico
     * 
     * @return array Lista de items con stock bajo
     */
    public function getAlertasStock(): array
    {
        $items = InventoryItem::find()
            ->where('stock_actual <= stock_minimo')
            ->andWhere(['activo' => true])
            ->orderBy(['stock_actual' => SORT_ASC])
            ->limit(10)
            ->all();

        $resultado = [];
        foreach ($items as $item) {
            $resultado[] = [
                'id' => $item->id,
                'codigo' => $item->codigo,
                'nombre' => $item->nombre,
                'stock_actual' => (int)$item->stock_actual,
                'stock_minimo' => (int)$item->stock_minimo,
                'categoria' => $item->categoria ?? 'Sin categoría',
                'es_critico' => $item->stock_actual === 0,
            ];
        }

        return $resultado;
    }

    /**
     * Obtiene las órdenes activas agrupadas por estado
     * 
     * @return array Lista de órdenes activas
     */
    public function getOrdenesActivas(): array
    {
        $ordenes = OrdenServicio::find()
            ->joinWith(['cliente', 'vehiculo'])
            ->where(['in', 'estado', ['abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega']])
            ->orderBy(['orden_servicio.created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        $resultado = [];
        foreach ($ordenes as $orden) {
            $resultado[] = [
                'id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
                'estado' => $orden->estado,
                'cliente' => $orden->cliente?->nombreCompleto ?? 'N/A',
                'vehiculo' => $orden->vehiculo ? $orden->vehiculo->marca . ' ' . $orden->vehiculo->modelo : 'N/A',
                'created_at' => $orden->created_at,
            ];
        }

        return $resultado;
    }

    /**
     * Obtiene los accesos rápidos según el rol del usuario
     * 
     * @return array Lista de accesos rápidos disponibles
     */
    public function getAccesosRapidos(): array
    {
        $user = Yii::$app->user->identity;
        $accesos = [];

        // Acceso rápido siempre disponible para usuarios autenticados
        $accesos[] = [
            'label' => 'Nueva Cita',
            'url' => ['/cita/create'],
            'icon' => 'fa-calendar-plus',
            'color' => 'blue',
            'permiso' => null, // Disponible para todos
        ];

        $accesos[] = [
            'label' => 'Nueva Orden',
            'url' => ['/orden-servicio/create'],
            'icon' => 'fa-clipboard-list',
            'color' => 'green',
            'permiso' => null,
        ];

        $accesos[] = [
            'label' => 'Nuevo Cliente',
            'url' => ['/cliente/create'],
            'icon' => 'fa-user-plus',
            'color' => 'purple',
            'permiso' => null,
        ];

        $accesos[] = [
            'label' => 'Ver Inventario',
            'url' => ['/inventario/index'],
            'icon' => 'fa-boxes',
            'color' => 'orange',
            'permiso' => null,
        ];

        return $accesos;
    }

    /**
     * Obtiene el conteo de trabajos activos por estado
     * 
     * @return array Conteo por estado
     */
    public function getTrabajosPorEstado(): array
    {
        $estados = ['abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega'];
        $resultado = [];

        foreach ($estados as $estado) {
            $count = OrdenServicio::find()
                ->where(['estado' => $estado])
                ->count();
            
            $resultado[$estado] = $count;
        }

        return $resultado;
    }
}
