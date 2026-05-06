<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Cliente;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use Yii;

/**
 * Controlador REST API para Clientes
 */
class ClienteController extends BaseController
{
    public $modelClass = Cliente::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'stats', 'export'];
        return $behaviors;
    }

    /**
     * Listar clientes con paginación y filtros
     * GET /api/clientes
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Cliente::find()->orderBy(['created_at' => SORT_DESC]);

        $request = Yii::$app->request;
        $search = $request->get('search');
        $searchEmail = $request->get('search_email');
        $searchTelefono = $request->get('search_telefono');
        $tipoDocumento = $request->get('tipo_documento');
        $estado = $request->get('estado');
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        // HU-002: Búsqueda por nombre (ya existente)
        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'nombre', $search],
                ['like', 'documento', $search],
            ]);
        }

        // HU-003: Búsqueda específica por email
        if ($searchEmail) {
            $query->andFilterWhere(['like', 'email', $searchEmail]);
        }

        // HU-004: Búsqueda específica por teléfono
        if ($searchTelefono) {
            $query->andFilterWhere(['like', 'telefono', $searchTelefono]);
        }

        if ($tipoDocumento) {
            $query->andWhere(['tipo_documento' => $tipoDocumento]);
        }

        // HU-011/HU-012: Filtro por estado (activo/cancelado)
        if ($estado !== null && $estado !== '') {
            $query->andWhere(['activo' => (int)$estado]);
        }

        // HU-024: Filtro por rango de fechas
        if ($fechaInicio) {
            $query->andFilterWhere(['>=', 'created_at', $fechaInicio]);
        }
        if ($fechaFin) {
            $query->andFilterWhere(['<=', 'created_at', $fechaFin]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * HU-010: Obtener estadísticas de clientes
     * GET /api/clientes/stats
     */
    public function actionStats(): array
    {
        $totalClientes = Cliente::find()->count();
        $clientesActivos = Cliente::find()->where(['activo' => 1])->count();
        $clientesNuevosMes = Cliente::find()
            ->where(['>=', 'created_at', date('Y-m-01')])
            ->count();

        return [
            'success' => true,
            'data' => [
                'total_clientes' => $totalClientes,
                'clientes_activos' => $clientesActivos,
                'clientes_nuevos_mes' => $clientesNuevosMes,
            ],
        ];
    }

    /**
     * HU-021: Exportar clientes a CSV
     * GET /api/clientes/export
     */
    public function actionExport()
    {
        $clientes = Cliente::find()->all();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="clientes_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Encabezados
        fputcsv($output, ['Nombre', 'Email', 'Teléfono', 'Dirección', 'Estado']);
        
        // Datos
        foreach ($clientes as $cliente) {
            fputcsv($output, [
                $cliente->nombre,
                $cliente->email,
                $cliente->telefono,
                $cliente->direccion,
                $cliente->activo ? 'Activo' : 'Cancelado',
            ]);
        }
        
        fclose($output);
        Yii::$app->end();
    }

    /**
     * Obtener cliente por ID con datos relacionados
     * GET /api/clientes/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        // HU-020: Incluir historial de servicios (órdenes)
        $ordenesServicio = [];
        foreach ($model->ordenServicio as $orden) {
            $ordenesServicio[] = [
                'id' => $orden->id,
                'codigo' => $orden->codigo,
                'fecha' => $orden->fecha_creacion,
                'estado' => $orden->estado,
                'vehiculo' => $orden->vehiculo ? [
                    'marca' => $orden->vehiculo->marca,
                    'modelo' => $orden->vehiculo->modelo,
                    'patente' => $orden->vehiculo->patente,
                ] : null,
            ];
        }

        // HU-030: Dashboard del cliente - última cita y última orden
        $ultimaCita = $model->citas->orderBy(['fecha' => SORT_DESC])->one();
        $ultimaOrden = $model->ordenServicio->orderBy(['fecha_creacion' => SORT_DESC])->one();
        
        return [
            'success' => true,
            'data' => [
                ...$model->attributes,
                'vehiculos_count' => $model->vehiculos->count(),
                'ordenes_count' => count($ordenesServicio),
                'ordenes_servicio' => $ordenesServicio,
                'ultima_cita' => $ultimaCita ? [
                    'id' => $ultimaCita->id,
                    'fecha' => $ultimaCita->fecha,
                    'estado' => $ultimaCita->estado,
                ] : null,
                'ultima_orden' => $ultimaOrden ? [
                    'id' => $ultimaOrden->id,
                    'codigo' => $ultimaOrden->codigo,
                    'fecha' => $ultimaOrden->fecha_creacion,
                    'estado' => $ultimaOrden->estado,
                ] : null,
            ],
        ];
    }

    /**
     * Crear nuevo cliente
     * POST /api/clientes
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        
        $model = new Cliente();
        $model->nombre = $request->post('nombre');
        $model->tipo_documento = $request->post('tipo_documento', 'DNI');
        $model->documento = $request->post('documento');
        $model->email = $request->post('email');
        $model->telefono = $request->post('telefono');
        $model->direccion = $request->post('direccion');
        $model->ciudad = $request->post('ciudad');
        $model->notas = $request->post('notas');
        $model->activo = $request->post('activo', true);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Cliente creado correctamente',
        ];
    }

    /**
     * Actualizar cliente existente
     * PUT/PATCH /api/clientes/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        // HU-012: Restringir edición de clientes cancelados
        if (!$model->activo) {
            throw new BadRequestHttpException('No se puede editar un cliente cancelado');
        }

        $model->nombre = $request->post('nombre', $model->nombre);
        $model->tipo_documento = $request->post('tipo_documento', $model->tipo_documento);
        $model->documento = $request->post('documento', $model->documento);
        $model->email = $request->post('email', $model->email);
        $model->telefono = $request->post('telefono', $model->telefono);
        $model->direccion = $request->post('direccion', $model->direccion);
        $model->ciudad = $request->post('ciudad', $model->ciudad);
        $model->notas = $request->post('notas', $model->notas);
        $model->activo = $request->post('activo', $model->activo);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Cliente actualizado correctamente',
        ];
    }

    /**
     * Eliminar cliente (soft delete)
     * DELETE /api/clientes/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        
        // HU-007/HU-025: Soft delete - solo desactivar, no eliminar
        $model->activo = false;
        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'message' => 'Cliente desactivado correctamente',
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Cliente
    {
        $model = Cliente::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Cliente no encontrado');
        }

        return $model;
    }
}
