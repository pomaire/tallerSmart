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
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete'];
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
        $model->delete();

        return [
            'success' => true,
            'message' => 'Servicio eliminado correctamente',
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
