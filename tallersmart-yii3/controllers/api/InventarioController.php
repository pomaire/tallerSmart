<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\InventoryItem;
use app\models\InventoryMovement;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\db\Transaction;
use Yii;

/**
 * Controlador REST API para Inventario
 */
class InventarioController extends BaseController
{
    public $modelClass = InventoryItem::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'movements', 'adjust'];
        return $behaviors;
    }

    /**
     * Listar items de inventario con paginación y filtros
     * GET /api/inventario
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = InventoryItem::find()->orderBy(['nombre' => SORT_ASC]);

        $request = Yii::$app->request;
        $categoria = $request->get('categoria');
        $search = $request->get('search');
        $stockMinimo = $request->get('stock_minimo');

        if ($categoria) {
            $query->andWhere(['categoria' => $categoria]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'nombre', $search],
                ['like', 'codigo', $search],
                ['like', 'descripcion', $search],
            ]);
        }

        if ($stockMinimo !== null) {
            $query->andWhere(['<=', 'stock_actual', $stockMinimo]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener item de inventario por ID
     * GET /api/inventario/{id}
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
     * Crear nuevo item de inventario
     * POST /api/inventario
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        
        $transaction = $db->beginTransaction();
        
        try {
            $model = new InventoryItem();
            $model->codigo = $request->post('codigo');
            $model->nombre = $request->post('nombre');
            $model->descripcion = $request->post('descripcion');
            $model->categoria = $request->post('categoria');
            $model->precio_costo = (float)$request->post('precio_costo', 0);
            $model->precio_venta = (float)$request->post('precio_venta', 0);
            $model->stock_actual = (int)$request->post('stock_actual', 0);
            $model->stock_minimo = (int)$request->post('stock_minimo', 5);
            $model->unidad_medida = $request->post('unidad_medida', 'UNIDAD');
            $model->ubicacion = $request->post('ubicacion');
            $model->activo = $request->post('activo', true);

            if (!$model->save()) {
                throw new BadRequestHttpException(json_encode($model->getErrors()));
            }

            // Registrar movimiento inicial si hay stock
            if ($model->stock_actual > 0) {
                $movement = new InventoryMovement();
                $movement->inventory_item_id = $model->id;
                $movement->tipo = 'entrada';
                $movement->cantidad = $model->stock_actual;
                $movement->precio_unitario = $model->precio_costo;
                $movement->razon = 'Stock inicial';
                $movement->created_by = Yii::$app->user->id ?? null;
                $movement->created_at = date('Y-m-d H:i:s');
                $movement->save(false);
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => $model,
                'message' => 'Item de inventario creado correctamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Actualizar item de inventario existente
     * PUT/PATCH /api/inventario/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->codigo = $request->post('codigo', $model->codigo);
        $model->nombre = $request->post('nombre', $model->nombre);
        $model->descripcion = $request->post('descripcion', $model->descripcion);
        $model->categoria = $request->post('categoria', $model->categoria);
        $model->precio_costo = $request->post('precio_costo', $model->precio_costo);
        $model->precio_venta = $request->post('precio_venta', $model->precio_venta);
        $model->stock_minimo = $request->post('stock_minimo', $model->stock_minimo);
        $model->unidad_medida = $request->post('unidad_medida', $model->unidad_medida);
        $model->ubicacion = $request->post('ubicacion', $model->ubicacion);
        $model->activo = $request->post('activo', $model->activo);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Item de inventario actualizado correctamente',
        ];
    }

    /**
     * Ajustar stock de un item
     * POST /api/inventario/{id}/adjust
     */
    public function actionAdjust($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        
        $transaction = $db->beginTransaction();
        
        try {
            $cantidad = (int)$request->post('cantidad');
            $tipo = $request->post('tipo');
            $razon = $request->post('razon', 'Ajuste manual');

            if ($cantidad === 0) {
                throw new BadRequestHttpException('La cantidad no puede ser cero');
            }

            if (!in_array($tipo, ['entrada', 'salida'])) {
                throw new BadRequestHttpException('Tipo de movimiento inválido');
            }

            if ($tipo === 'salida' && $model->stock_actual < abs($cantidad)) {
                throw new BadRequestHttpException('Stock insuficiente');
            }

            // Actualizar stock
            if ($tipo === 'entrada') {
                $model->stock_actual += $cantidad;
            } else {
                $model->stock_actual -= abs($cantidad);
            }

            if (!$model->save()) {
                throw new BadRequestHttpException(json_encode($model->getErrors()));
            }

            // Registrar movimiento
            $movement = new InventoryMovement();
            $movement->inventory_item_id = $model->id;
            $movement->tipo = $tipo;
            $movement->cantidad = abs($cantidad);
            $movement->precio_unitario = $model->precio_costo;
            $movement->razon = $razon;
            $movement->created_by = Yii::$app->user->id ?? null;
            $movement->created_at = date('Y-m-d H:i:s');
            
            if (!$movement->save()) {
                throw new BadRequestHttpException(json_encode($movement->getErrors()));
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => [
                    'item' => $model,
                    'movimiento' => $movement,
                ],
                'message' => 'Stock ajustado correctamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Obtener movimientos de un item
     * GET /api/inventario/{id}/movements
     */
    public function actionMovements($id): ActiveDataProvider
    {
        $this->findModel($id); // Verificar que existe
        
        $query = InventoryMovement::find()
            ->where(['inventory_item_id' => $id])
            ->orderBy(['created_at' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);
    }

    /**
     * Eliminar item de inventario
     * DELETE /api/inventario/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $model->activo = false; // Soft delete
        $model->save(false);

        return [
            'success' => true,
            'message' => 'Item de inventario eliminado correctamente',
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): InventoryItem
    {
        $model = InventoryItem::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Item de inventario no encontrado');
        }

        return $model;
    }
}
