<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Servicio;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use Yii;

/**
 * Controlador REST API para Servicios
 */
class ServicioController extends BaseController
{
    public $modelClass = Servicio::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'duplicar', 'exportar'];
        return $behaviors;
    }

    /**
     * Listar servicios con paginación y filtros
     * GET /api/servicios
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Servicio::find()
            ->joinWith('categoria')
            ->orderBy(['nombre' => SORT_ASC]);

        $request = Yii::$app->request;
        $categoriaId = $request->get('categoria_id');
        $activo = $request->get('activo');
        $search = $request->get('search');

        if ($categoriaId) {
            $query->andWhere(['servicio.categoria_id' => $categoriaId]);
        }

        if ($activo !== null) {
            $query->andWhere(['servicio.activo' => (bool)$activo]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'servicio.nombre', $search],
                ['like', 'servicio.descripcion', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener servicio por ID
     * GET /api/servicios/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        return [
            'success' => true,
            'data' => $model,
        ];
    }

    /**
     * Crear nuevo servicio
     * POST /api/servicios
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        
        $model = new Servicio();
        $model->categoria_id = $request->post('categoria_id');
        $model->nombre = $request->post('nombre');
        $model->descripcion = $request->post('descripcion');
        $model->precio = (float)$request->post('precio', 0);
        $model->duracion_estimada = (int)$request->post('duracion_estimada', 60);
        $model->activo = $request->post('activo', true);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Servicio creado correctamente',
        ];
    }

    /**
     * Actualizar servicio existente
     * PUT/PATCH /api/servicios/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->categoria_id = $request->post('categoria_id', $model->categoria_id);
        $model->nombre = $request->post('nombre', $model->nombre);
        $model->descripcion = $request->post('descripcion', $model->descripcion);
        $model->precio = $request->post('precio', $model->precio);
        $model->duracion_estimada = $request->post('duracion_estimada', $model->duracion_estimada);
        $model->activo = $request->post('activo', $model->activo);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Servicio actualizado correctamente',
        ];
    }

    /**
     * Eliminar servicio
     * DELETE /api/servicios/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);

        // Verificar si el servicio tiene órdenes asociadas
        $ordenesAsociadas = \app\models\OrdenServicioDetalle::find()
            ->where(['servicio_id' => $model->id])
            ->joinWith('ordenServicio')
            ->andWhere(['!=', 'orden_servicio.estado', 'finalizada'])
            ->count();

        if ($ordenesAsociadas > 0) {
            throw new BadRequestHttpException('El servicio tiene órdenes activas asociadas y no puede ser eliminado');
        }

        // Soft delete: desactivar en lugar de eliminar físicamente
        $model->activo = false;
        $model->nombre = '[ELIMINADO] ' . $model->nombre;
        
        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'message' => 'Servicio eliminado correctamente (desactivado)',
        ];
    }

    /**
     * Duplicar servicio existente
     * POST /api/servicios/{id}/duplicar
     */
    public function actionDuplicar($id): array
    {
        $original = $this->findModel($id);

        // Crear nuevo servicio con datos copiados
        $model = new Servicio();
        $model->categoria_id = $original->categoria_id;
        $model->nombre = $original->nombre . ' (Copia)';
        $model->descripcion = $original->descripcion;
        $model->precio = $original->precio;
        $model->duracion_estimada = $original->duracion_estimada;
        $model->activo = true; // El duplicado siempre empieza activo

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Servicio duplicado correctamente',
        ];
    }

    /**
     * Exportar catálogo de servicios a CSV
     * GET /api/servicios/exportar
     */
    public function actionExportar(): array
    {
        $request = Yii::$app->request;
        $categoriaId = $request->get('categoria_id');
        $activo = $request->get('activo');

        // Obtener todos los servicios con sus categorías
        $query = Servicio::find()
            ->joinWith('categoria')
            ->orderBy(['servicio.nombre' => SORT_ASC]);

        if ($categoriaId) {
            $query->andWhere(['servicio.categoria_id' => $categoriaId]);
        }

        if ($activo !== null) {
            $query->andWhere(['servicio.activo' => (bool)$activo]);
        }

        $servicios = $query->all();

        // Preparar datos para CSV
        $datos = [];
        foreach ($servicios as $servicio) {
            $datos[] = [
                'nombre' => $servicio->nombre,
                'categoria' => $servicio->categoria ? $servicio->categoria->nombre : 'Sin categoría',
                'precio' => number_format($servicio->precio, 2, '.', ''),
                'duracion_minutos' => $servicio->duracion_estimada,
                'descripcion' => str_replace(["\n", "\r", ";"], " ", $servicio->descripcion ?? ''),
                'activo' => $servicio->activo ? 'Sí' : 'No',
            ];
        }

        return [
            'success' => true,
            'data' => $datos,
            'total' => count($datos),
            'formato' => 'CSV',
            'columnas' => ['nombre', 'categoria', 'precio', 'duracion_minutos', 'descripcion', 'activo'],
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Servicio
    {
        $model = Servicio::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Servicio no encontrado');
        }

        return $model;
    }
}
