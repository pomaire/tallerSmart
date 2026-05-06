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
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete'];
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
        $tipoDocumento = $request->get('tipo_documento');

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'nombre', $search],
                ['like', 'documento', $search],
                ['like', 'email', $search],
                ['like', 'telefono', $search],
            ]);
        }

        if ($tipoDocumento) {
            $query->andWhere(['tipo_documento' => $tipoDocumento]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener cliente por ID
     * GET /api/clientes/{id}
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
     * Eliminar cliente
     * DELETE /api/clientes/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $model->delete();

        return [
            'success' => true,
            'message' => 'Cliente eliminado correctamente',
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
