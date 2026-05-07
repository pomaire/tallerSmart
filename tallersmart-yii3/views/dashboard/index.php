<?php

/** @var array $kpis KPIs del dashboard */
/** @var array $citasHoy Citas programadas para hoy */
/** @var array $alertasStock Alertas de stock bajo */
/** @var array $ordenesActivas Órdenes activas */
/** @var array $accesosRapidos Accesos rápidos */

use yii\helpers\Url;
use app\components\widgets\KpiCard;
use app\components\widgets\CitasHoyWidget;
use app\components\widgets\AlertasStockWidget;
use app\components\widgets\OrdenesActivasWidget;
use app\components\widgets\AccesosRapidosWidget;

$this->title = 'Dashboard - Panel Principal';
$this->params['breadcrumbs'][] = 'Dashboard';

// Registrar script para auto-refresh
$refreshUrl = Url::to(['/dashboard/refresh-all']);
$refreshKpiUrl = Url::to(['/dashboard/refresh-kpi']);

$js = <<<JS
(function() {
    // Auto-refresh cada 60 segundos
    setInterval(function() {
        fetch('{$refreshUrl}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Dashboard actualizado automáticamente');
                // Aquí podríamos actualizar los valores sin recargar
            }
        })
        .catch(error => console.error('Error al actualizar dashboard:', error));
    }, 60000); // 60 segundos
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="p-6 bg-gray-50 min-h-screen">
    
    <!-- Header del Dashboard con saludo personalizado -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?php if (!Yii::$app->user->isGuest): ?>
                    ¡Hola, <?= htmlspecialchars(Yii::$app->user->identity->nombre, ENT_QUOTES, 'UTF-8') ?>! 👋
                <?php else: ?>
                    Bienvenido
                <?php endif; ?>
            </h1>
            <p class="text-gray-600 mt-1">
                Panel de control y métricas del taller
                <?php if (!Yii::$app->user->isGuest): ?>
                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">
                        <?= htmlspecialchars(Yii::$app->user->identity->rol?->nombre ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Botón de actualizar -->
            <button 
                onclick="actualizarDashboard()" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm"
                aria-label="Actualizar datos del dashboard"
            >
                <i class="fas fa-sync-alt"></i>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Indicador de carga -->
    <div id="loading-indicator" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex items-center gap-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 font-medium">Actualizando datos...</span>
        </div>
    </div>

    <!-- KPIs Principales - Grid responsive: 1 col móvil, 2 cols tablet, 4 cols desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?= KpiCard::widget([
            'titulo' => 'Servicios Activos',
            'valor' => $kpis['servicios_activos'] ?? 0,
            'icono' => 'wrench',
            'color' => 'primary',
            'subtitulo' => 'En progreso',
            'ariaLabel' => "Servicios activos: {$kpis['servicios_activos']}",
        ]) ?>

        <?= KpiCard::widget([
            'titulo' => 'Citas Hoy',
            'valor' => $kpis['citas_hoy'] ?? 0,
            'icono' => 'calendar-check',
            'color' => 'accent',
            'subtitulo' => 'Programadas',
            'mostrarCritico' => ($kpis['citas_hoy'] ?? 0) > 10,
            'ariaLabel' => "Citas programadas para hoy: {$kpis['citas_hoy']}",
        ]) ?>

        <?= KpiCard::widget([
            'titulo' => 'Stock Bajo',
            'valor' => $kpis['stock_bajo'] ?? 0,
            'icono' => 'exclamation-triangle',
            'color' => 'destructive',
            'subtitulo' => 'Productos críticos',
            'mostrarCritico' => ($kpis['stock_bajo'] ?? 0) > 0,
            'url' => Url::to(['/inventario/index']),
            'ariaLabel' => "Productos con stock bajo: {$kpis['stock_bajo']}",
        ]) ?>

        <?= KpiCard::widget([
            'titulo' => 'Ingresos Mes',
            'valor' => Yii::$app->formatter->asCurrency($kpis['ingresos_mes'] ?? 0),
            'icono' => 'dollar-sign',
            'color' => 'green',
            'subtitulo' => 'Este mes',
            'ariaLabel' => "Ingresos del mes: " . Yii::$app->formatter->asCurrency($kpis['ingresos_mes'] ?? 0),
        ]) ?>

        <?= KpiCard::widget([
            'titulo' => 'Trabajos Listos',
            'valor' => $kpis['trabajos_listos'] ?? 0,
            'icono' => 'check-circle',
            'color' => 'blue',
            'subtitulo' => 'Para entrega',
            'ariaLabel' => "Trabajos listos para entrega: {$kpis['trabajos_listos']}",
        ]) ?>

        <?= KpiCard::widget([
            'titulo' => 'Clientes Nuevos',
            'valor' => $kpis['clientes_nuevos'] ?? 0,
            'icono' => 'user-plus',
            'color' => 'purple',
            'subtitulo' => 'Este mes',
            'ariaLabel' => "Clientes nuevos este mes: {$kpis['clientes_nuevos']}",
        ]) ?>

        <?= KpiCard::widget([
            'titulo' => 'Valor Inventario',
            'valor' => Yii::$app->formatter->asCurrency($kpis['valor_inventario'] ?? 0),
            'icono' => 'boxes',
            'color' => 'orange',
            'subtitulo' => 'Total',
            'ariaLabel' => "Valor total del inventario: " . Yii::$app->formatter->asCurrency($kpis['valor_inventario'] ?? 0),
        ]) ?>
    </div>

    <!-- Widgets adicionales - Grid responsive -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna izquierda (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <?= CitasHoyWidget::widget(['citas' => $citasHoy]) ?>
            <?= OrdenesActivasWidget::widget(['ordenes' => $ordenesActivas]) ?>
        </div>

        <!-- Columna derecha (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            <?= AlertasStockWidget::widget(['alertas' => $alertasStock]) ?>
            <?= AccesosRapidosWidget::widget(['accesos' => $accesosRapidos]) ?>
        </div>
    </div>
</div>

<script>
function actualizarDashboard() {
    const loadingIndicator = document.getElementById('loading-indicator');
    loadingIndicator.classList.remove('hidden');
    
    fetch('<?= $refreshUrl ?>', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        loadingIndicator.classList.add('hidden');
        if (data.success) {
            location.reload();
        } else {
            alert('Error al actualizar: ' + (data.error || 'Desconocido'));
        }
    })
    .catch(error => {
        loadingIndicator.classList.add('hidden');
        console.error('Error:', error);
        alert('Error de conexión al actualizar el dashboard');
    });
}
</script>
