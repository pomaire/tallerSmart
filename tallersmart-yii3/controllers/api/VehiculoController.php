<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Vehiculo;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use Yii;

/**
 * Controlador REST API para Vehículos
 */
class VehiculoController extends BaseController
{
    public $modelClass = Vehiculo::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete'];
        return $behaviors;
    }

    /**
     * Listar vehículos con paginación y filtros
     * GET /api/vehiculos
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Vehiculo::find()
            ->joinWith('cliente')
            ->orderBy(['created_at' => SORT_DESC]);

        $request = Yii::$app->request;
        $clienteId = $request->get('cliente_id');
        $search = $request->get('search');

        if ($clienteId) {
            $query->andWhere(['vehiculo.cliente_id' => $clienteId]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'marca', $search],
                ['like', 'modelo', $search],
                ['like', 'placa', $search],
                ['like', 'vin', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener vehículo por ID
     * GET /api/vehiculos/{id}
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
     * Crear nuevo vehículo
     * POST /api/vehiculos
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        
        $model = new Vehiculo();
        $model->cliente_id = $request->post('cliente_id');
        $model->marca = $request->post('marca');
        $model->modelo = $request->post('modelo');
        $model->year = (int)$request->post('year', date('Y'));
        $model->placa = $request->post('placa');
        $model->vin = $request->post('vin');
        $model->color = $request->post('color');
        $model->kilometraje = (int)$request->post('kilometraje', 0);
        $model->motor = $request->post('motor');
        $model->notas = $request->post('notas');
        $model->activo = $request->post('activo', true);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Vehículo creado correctamente',
        ];
    }

    /**
     * Actualizar vehículo existente
     * PUT/PATCH /api/vehiculos/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->cliente_id = $request->post('cliente_id', $model->cliente_id);
        $model->marca = $request->post('marca', $model->marca);
        $model->modelo = $request->post('modelo', $model->modelo);
        $model->year = $request->post('year', $model->year);
        $model->placa = $request->post('placa', $model->placa);
        $model->vin = $request->post('vin', $model->vin);
        $model->color = $request->post('color', $model->color);
        $model->kilometraje = $request->post('kilometraje', $model->kilometraje);
        $model->motor = $request->post('motor', $model->motor);
        $model->notas = $request->post('notas', $model->notas);
        $model->activo = $request->post('activo', $model->activo);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Vehículo actualizado correctamente',
        ];
    }

    /**
     * Eliminar vehículo
     * DELETE /api/vehiculos/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $model->delete();

        return [
            'success' => true,
            'message' => 'Vehículo eliminado correctamente',
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Vehiculo
    {
        $model = Vehiculo::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Vehículo no encontrado');
        }

        return $model;
    }
}
