<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\AuditLog;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\db\Query;

/**
 * Controlador API para gestión de AuditLog
 */
class AuditLogController extends BaseController
{
    public $modelClass = '';

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        
        // Permitir acceso solo a administradores
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => function ($rule) {
                    return Yii::$app->user->identity && Yii::$app->user->identity->esAdministrador();
                },
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Lista logs de auditoría con filtros
     * HU-002, HU-008, HU-009, HU-010, HU-011, HU-015, HU-020
     */
    public function actionIndex()
    {
        $query = AuditLog::find()
            ->joinWith('usuario')
            ->orderBy(['created_at' => SORT_DESC]);

        // Filtros
        $usuarioId = Yii::$app->request->get('usuario_id');
        $entidad = Yii::$app->request->get('entidad');
        $accion = Yii::$app->request->get('accion');
        $fechaDesde = Yii::$app->request->get('fecha_desde');
        $fechaHasta = Yii::$app->request->get('fecha_hasta');
        $busqueda = Yii::$app->request->get('busqueda');
        $tabla = Yii::$app->request->get('tabla');

        if ($usuarioId) {
            $query->andWhere(['usuario_id' => $usuarioId]);
        }

        if ($entidad) {
            $query->andWhere(['entidad' => $entidad]);
        }

        if ($accion) {
            $query->andWhere(['accion' => $accion]);
        }

        if ($tabla) {
            $query->andWhere(['modulo' => $tabla]);
        }

        if ($fechaDesde) {
            $query->andWhere(['>=', 'created_at', $fechaDesde . ' 00:00:00']);
        }

        if ($fechaHasta) {
            $query->andWhere(['<=', 'created_at', $fechaHasta . ' 23:59:59']);
        }

        // HU-015: Búsqueda de texto en datos
        if ($busqueda) {
            $query->andWhere([
                'or',
                ['like', 'datos_antiguos', $busqueda],
                ['like', 'datos_nuevos', $busqueda]
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return [
            'items' => array_map(fn($log) => $this->formatLog($log), $dataProvider->models),
            'total' => $dataProvider->totalCount,
            'page' => $dataProvider->pagination->page + 1,
            'pageSize' => $dataProvider->pagination->pageSize,
        ];
    }

    /**
     * Ver detalle de un log
     * HU-012, HU-025
     */
    public function actionView($id)
    {
        $model = AuditLog::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Log no encontrado');
        }

        return [
            'log' => $this->formatLog($model),
            'diff' => $this->calculateDiff($model),
        ];
    }

    /**
     * Exportar logs a CSV
     * HU-013
     */
    public function actionExport()
    {
        $usuarioId = Yii::$app->request->get('usuario_id');
        $entidad = Yii::$app->request->get('entidad');
        $accion = Yii::$app->request->get('accion');
        $fechaDesde = Yii::$app->request->get('fecha_desde');
        $fechaHasta = Yii::$app->request->get('fecha_hasta');

        $query = AuditLog::find()
            ->joinWith('usuario')
            ->orderBy(['created_at' => SORT_DESC]);

        if ($usuarioId) {
            $query->andWhere(['usuario_id' => $usuarioId]);
        }
        if ($entidad) {
            $query->andWhere(['entidad' => $entidad]);
        }
        if ($accion) {
            $query->andWhere(['accion' => $accion]);
        }
        if ($fechaDesde) {
            $query->andWhere(['>=', 'created_at', $fechaDesde . ' 00:00:00']);
        }
        if ($fechaHasta) {
            $query->andWhere(['<=', 'created_at', $fechaHasta . ' 23:59:59']);
        }

        $logs = $query->all();

        // Registrar la exportación - HU-017
        AuditLog::registrarAccion(
            'EXPORT',
            'audit_log',
            null,
            null,
            json_encode(['filtros' => compact('usuarioId', 'entidad', 'accion', 'fechaDesde', 'fechaHasta')]),
            'audit_log'
        );

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/csv');
        Yii::$app->response->headers->add('Content-Disposition', 'attachment; filename="audit_log_' . date('Y-m-d_His') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Header CSV
        fputcsv($output, [
            'ID', 'Fecha', 'Usuario', 'Acción', 'Módulo', 'Entidad', 
            'Registro ID', 'IP', 'Datos Antiguos', 'Datos Nuevos'
        ]);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log->id,
                $log->created_at,
                $log->usuario?->nombre ?? 'N/A',
                $log->accion,
                $log->modulo,
                $log->entidad,
                $log->registro_id,
                $log->ip_address,
                $log->datos_antiguos,
                $log->datos_nuevos,
            ]);
        }

        fclose($output);
        return Yii::$app->response->data;
    }

    /**
     * Timeline de cambios para una entidad específica
     * HU-020
     */
    public function actionTimeline($entidad, $registroId)
    {
        $logs = AuditLog::find()
            ->where(['entidad' => $entidad, 'registro_id' => $registroId])
            ->orderBy(['created_at' => SORT_ASC])
            ->joinWith('usuario')
            ->all();

        return [
            'timeline' => array_map(fn($log) => [
                'id' => $log->id,
                'fecha' => $log->created_at,
                'accion' => $log->accion,
                'usuario' => $log->usuario?->nombre ?? 'Desconocido',
                'usuario_id' => $log->usuario_id,
                'datos_antiguos' => json_decode($log->datos_antiguos, true),
                'datos_nuevos' => json_decode($log->datos_nuevos, true),
                'diff' => $this->calculateDiff($log),
            ], $logs),
        ];
    }

    /**
     * Reporte de actividad por usuario
     * HU-021
     */
    public function actionReporteActividad()
    {
        $usuarioId = Yii::$app->request->get('usuario_id');
        $periodo = Yii::$app->request->get('periodo', 'semana'); // dia, semana, mes
        $fechaDesde = Yii::$app->request->get('fecha_desde');
        $fechaHasta = Yii::$app->request->get('fecha_hasta');

        $query = AuditLog::find();

        if ($usuarioId) {
            $query->andWhere(['usuario_id' => $usuarioId]);
        }

        if ($fechaDesde) {
            $query->andWhere(['>=', 'created_at', $fechaDesde . ' 00:00:00']);
        }

        if ($fechaHasta) {
            $query->andWhere(['<=', 'created_at', $fechaHasta . ' 23:59:59']);
        }

        // Agrupar por fecha y acción
        $formatoFecha = match ($periodo) {
            'dia' => '%Y-%m-%d',
            'semana' => '%Y-%u',
            'mes' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $actividad = (new Query())
            ->select([
                "DATE_FORMAT(created_at, '{$formatoFecha}') as fecha",
                'accion',
                'COUNT(*) as total',
            ])
            ->from('audit_log')
            ->groupBy(['fecha', 'accion'])
            ->orderBy(['fecha' => SORT_DESC])
            ->all();

        // Resumen por usuario
        $resumenUsuarios = (new Query())
            ->select([
                'usuario_id',
                'u.nombre as usuario_nombre',
                'COUNT(*) as total_acciones',
                'SUM(CASE WHEN accion = \'CREATE\' THEN 1 ELSE 0 END) as creaciones',
                'SUM(CASE WHEN accion = \'UPDATE\' THEN 1 ELSE 0 END) as actualizaciones',
                'SUM(CASE WHEN accion = \'DELETE\' THEN 1 ELSE 0 END) as eliminaciones',
            ])
            ->from('audit_log al')
            ->leftJoin('usuario u', 'al.usuario_id = u.id')
            ->groupBy('usuario_id')
            ->orderBy(['total_acciones' => SORT_DESC])
            ->limit(20)
            ->all();

        return [
            'actividad' => $actividad,
            'resumen_usuarios' => $resumenUsuarios,
            'periodo' => $periodo,
        ];
    }

    /**
     * Rollback/Restaurar desde log
     * HU-027
     */
    public function actionRollback($id)
    {
        $log = AuditLog::findOne($id);
        if (!$log) {
            throw new NotFoundHttpException('Log no encontrado');
        }

        if (!$log->datos_antiguos) {
            throw new ForbiddenHttpException('No hay datos anteriores para restaurar');
        }

        // Solo permitir rollback para UPDATE y DELETE
        if (!in_array($log->accion, ['UPDATE', 'DELETE'])) {
            throw new ForbiddenHttpException('Solo se puede hacer rollback de actualizaciones y eliminaciones');
        }

        $datosAnteriores = json_decode($log->datos_antiguos, true);
        
        // Registrar el rollback como una nueva acción
        AuditLog::registrarAccion(
            'ROLLBACK',
            $log->modulo,
            $log->registro_id,
            $log->datos_nuevos,
            $log->datos_antiguos,
            $log->entidad
        );

        return [
            'success' => true,
            'message' => 'Rollback registrado. Los datos anteriores están disponibles para restauración manual.',
            'datos_anteriores' => $datosAnteriores,
            'advertencia' => 'La restauración automática depende de la entidad. Consulte la documentación.',
        ];
    }

    /**
     * Formatear log para respuesta API
     */
    private function formatLog(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'fecha_creacion' => $log->created_at,
            'usuario_id' => $log->usuario_id,
            'usuario_nombre' => $log->usuario?->nombre ?? 'Sistema',
            'accion' => $log->accion,
            'modulo' => $log->modulo,
            'entidad' => $log->entidad,
            'entidad_tipo' => $log->entidad,
            'entidad_id' => $log->registro_id,
            'ip' => $log->ip_address,
            'datos_antiguos' => json_decode($log->datos_antiguos, true),
            'datos_nuevos' => json_decode($log->datos_nuevos, true),
            'cambios_json' => $this->calculateDiff($log),
        ];
    }

    /**
     * Calcular diff entre datos antiguos y nuevos
     * HU-012, HU-025
     */
    private function calculateDiff(AuditLog $log): array
    {
        $antiguos = json_decode($log->datos_antiguos ?? '{}', true) ?? [];
        $nuevos = json_decode($log->datos_nuevos ?? '{}', true) ?? [];

        $camposModificados = [];
        $todosLosCampos = array_unique(array_merge(array_keys($antiguos), array_keys($nuevos)));

        foreach ($todosLosCampos as $campo) {
            $valorAntiguo = $antiguos[$campo] ?? null;
            $valorNuevo = $nuevos[$campo] ?? null;

            if ($valorAntiguo !== $valorNuevo) {
                $camposModificados[$campo] = [
                    'anterior' => $valorAntiguo,
                    'nuevo' => $valorNuevo,
                    'modificado' => true,
                ];
            }
        }

        return [
            'campos_modificados' => $camposModificados,
            'total_cambios' => count($camposModificados),
            'completo' => [
                'antiguos' => $antiguos,
                'nuevos' => $nuevos,
            ],
        ];
    }
}
