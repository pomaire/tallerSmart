<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Cita;
use app\models\CitaServicio;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\db\Transaction;
use Yii;

/**
 * Controlador REST API para Citas
 */
class CitaController extends BaseController
{
    public $modelClass = Cita::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'cancel'];
        return $behaviors;
    }

    /**
     * Listar citas con paginación y filtros
     * GET /api/citas
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Cita::find()
            ->joinWith(['cliente', 'vehiculo'])
            ->orderBy(['fecha_hora' => SORT_DESC]);

        $request = Yii::$app->request;
        $estado = $request->get('estado');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $clienteId = $request->get('cliente_id');
        $search = $request->get('search');

        if ($estado) {
            $query->andWhere(['cita.estado' => $estado]);
        }

        if ($fechaDesde) {
            $query->andWhere(['>=', 'cita.fecha_hora', $fechaDesde]);
        }

        if ($fechaHasta) {
            $query->andWhere(['<=', 'cita.fecha_hora', $fechaHasta]);
        }

        if ($clienteId) {
            $query->andWhere(['cita.cliente_id' => $clienteId]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'cliente.nombre', $search],
                ['like', 'vehiculo.placa', $search],
                ['like', 'cita.notas', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener cita por ID
     * GET /api/citas/{id}
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
     * Crear nueva cita
     * POST /api/citas
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        
        $transaction = $db->beginTransaction(Transaction::SERIALIZABLE);
        
        try {
            $model = new Cita();
            $model->cliente_id = $request->post('cliente_id');
            $model->vehiculo_id = $request->post('vehiculo_id');
            $model->fecha_hora = $request->post('fecha_hora');
            $model->estado = $request->post('estado', 'pendiente');
            $model->notas = $request->post('notas');
            $model->created_by = Yii::$app->user->id ?? null;

            if (!$model->save()) {
                throw new BadRequestHttpException(json_encode($model->getErrors()));
            }

            // Guardar servicios de la cita
            $servicios = $request->post('servicios', []);
            foreach ($servicios as $servicioData) {
                $citaServicio = new CitaServicio();
                $citaServicio->cita_id = $model->id;
                $citaServicio->servicio_id = $servicioData['servicio_id'];
                $citaServicio->cantidad = (int)($servicioData['cantidad'] ?? 1);
                $citaServicio->precio_unitario = (float)($servicioData['precio_unitario'] ?? 0);
                
                if (!$citaServicio->save()) {
                    throw new BadRequestHttpException(json_encode($citaServicio->getErrors()));
                }
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => $model,
                'message' => 'Cita creada correctamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Actualizar cita existente
     * PUT/PATCH /api/citas/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->cliente_id = $request->post('cliente_id', $model->cliente_id);
        $model->vehiculo_id = $request->post('vehiculo_id', $model->vehiculo_id);
        $model->fecha_hora = $request->post('fecha_hora', $model->fecha_hora);
        $model->estado = $request->post('estado', $model->estado);
        $model->notas = $request->post('notas', $model->notas);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Cita actualizada correctamente',
        ];
    }

    /**
     * Cancelar cita
     * POST /api/citas/{id}/cancel
     */
    public function actionCancel($id): array
    {
        $model = $this->findModel($id);
        
        if ($model->estado === 'cancelada') {
            throw new BadRequestHttpException('La cita ya está cancelada');
        }

        $model->estado = 'cancelada';
        
        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'message' => 'Cita cancelada correctamente',
        ];
    }

    /**
     * Eliminar cita
     * DELETE /api/citas/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $model->delete();

        return [
            'success' => true,
            'message' => 'Cita eliminada correctamente',
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Cita
    {
        $model = Cita::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Cita no encontrada');
        }

        return $model;
    }
}
