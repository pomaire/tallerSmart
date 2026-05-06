<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\InventoryItem;
use app\models\InventoryMovement;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\db\Transaction;
use yii\web\UploadedFile;
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
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'movements', 'adjust', 'export-csv', 'generate-sku'];
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
        $estadoStock = $request->get('estado_stock'); // HU-026

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

        // HU-026: Filtrar por estado de stock
        if ($estadoStock) {
            if ($estadoStock === 'sin_stock') {
                $query->andWhere(['stock_actual' => 0]);
            } elseif ($estadoStock === 'stock_bajo') {
                $query->andWhere(['<=', 'stock_actual', new \yii\db\Expression('stock_minimo')])
                      ->andWhere(['>', 'stock_actual', 0]);
            } elseif ($estadoStock === 'en_stock') {
                $query->andWhere(['>', 'stock_actual', new \yii\db\Expression('stock_minimo')]);
            }
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
            'data' => [
                ...$model->toArray(),
                'stock_estado' => $model->getStockEstado(),
                'stock_estado_color' => $model->getStockEstadoColor(),
                'stock_estado_label' => $model->getStockEstadoLabel(),
                'stock_porcentaje' => round($model->getStockPorcentaje(), 2),
                'precio_formateado' => InventoryItem::formatPrice($model->precio_venta),
                'unidad_medida_formateada' => $model->getUnidadMedidaFormateada(),
            ],
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
            
            // HU-008: Autogenerar SKU si no se proporciona
            $codigo = $request->post('codigo');
            if (empty($codigo)) {
                $categoria = $request->post('categoria', 'Repuestos Generales');
                $codigo = InventoryItem::generarSKU($categoria);
            }
            
            $model->codigo = $codigo;
            $model->nombre = $request->post('nombre');
            $model->descripcion = $request->post('descripcion');
            $model->categoria = $request->post('categoria');
            $model->categoria_id = $request->post('categoria_id');
            $model->precio_costo = (float)$request->post('precio_costo', 0);
            $model->precio_venta = (float)$request->post('precio_venta', 0);
            $model->stock_actual = (int)$request->post('stock_actual', 0);
            $model->stock_minimo = (int)$request->post('stock_minimo', 5);
            $model->stock_maximo = (int)$request->post('stock_maximo', 0); // HU-021
            $model->unidad_medida = $request->post('unidad_medida', 'UNIDAD');
            $model->ubicacion = $request->post('ubicacion');
            $model->activo = $request->post('activo', true);

            // HU-023: Validar y subir imagen
            $imagen = UploadedFile::getInstanceByName('imagen');
            if ($imagen) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imagen->extension);
                
                if (!in_array($extension, $allowedExtensions)) {
                    throw new BadRequestHttpException('Formato de imagen no permitido. Solo JPG, PNG y GIF.');
                }
                
                $filename = 'inv_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = Yii::getAlias('@webroot/uploads/inventory/');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                if ($imagen->saveAs($uploadPath . $filename)) {
                    $model->imagen = '/uploads/inventory/' . $filename;
                }
            }

            // HU-010: Validar SKU único
            if (InventoryItem::find()->where(['codigo' => $model->codigo])->exists()) {
                throw new BadRequestHttpException('El SKU ya existe en el sistema');
            }

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

            // HU-013: Validar cantidad no negativa
            if ($cantidad < 0) {
                throw new BadRequestHttpException('Cantidad no puede ser negativa');
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

            // HU-022: Generar notificación si hay stock bajo después del movimiento
            if ($model->isStockBajo()) {
                $this->generarNotificacionStockBajo($model);
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

    /**
     * Exportar inventario a CSV (HU-025)
     * GET /api/inventario/export-csv
     */
    public function actionExportCsv(): \yii\web\Response
    {
        $query = InventoryItem::find()->orderBy(['nombre' => SORT_ASC]);
        
        $request = Yii::$app->request;
        $categoria = $request->get('categoria');
        $search = $request->get('search');
        $estadoStock = $request->get('estado_stock');

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

        // HU-026: Filtrar por estado de stock
        if ($estadoStock) {
            if ($estadoStock === 'sin_stock') {
                $query->andWhere(['stock_actual' => 0]);
            } elseif ($estadoStock === 'stock_bajo') {
                $query->andWhere(['<=', 'stock_actual', new \yii\db\Expression('stock_minimo')])
                      ->andWhere(['>', 'stock_actual', 0]);
            } elseif ($estadoStock === 'en_stock') {
                $query->andWhere(['>', 'stock_actual', new \yii\db\Expression('stock_minimo')]);
            }
        }

        $items = $query->all();

        // Crear CSV
        $csvData = [];
        $csvData[] = ['SKU', 'Nombre', 'Categoría', 'Descripción', 'Precio Costo', 'Precio Venta', 'Stock Actual', 'Stock Mínimo', 'Stock Máximo', 'Unidad Medida', 'Ubicación', 'Estado'];

        foreach ($items as $item) {
            $csvData[] = [
                $item->codigo,
                $item->nombre,
                $item->categoria,
                $item->descripcion,
                $item->precio_costo,
                $item->precio_venta,
                $item->stock_actual,
                $item->stock_minimo,
                $item->stock_maximo ?? '',
                $item->unidad_medida,
                $item->ubicacion,
                $item->getStockEstadoLabel(),
            ];
        }

        // Generar contenido CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Preparar respuesta
        $filename = 'inventario_' . date('Y-m-d_His') . '.csv';
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/csv; charset=utf-8');
        Yii::$app->response->headers->add('Content-Disposition', 'attachment; filename="' . $filename . '"');
        Yii::$app->response->headers->add('Pragma', 'public');
        Yii::$app->response->headers->add('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        Yii::$app->response->headers->add('Expires', '0');
        
        return Yii::$app->response->sendContentAsFile($csvContent, $filename, ['mimeType' => 'text/csv']);
    }

    /**
     * Generar SKU automáticamente (HU-008)
     * GET /api/inventario/generate-sku?categoria=Aceites
     */
    public function actionGenerateSku(): array
    {
        $request = Yii::$app->request;
        $categoria = $request->get('categoria', 'Repuestos Generales');
        
        $sku = InventoryItem::generarSKU($categoria);
        
        return [
            'success' => true,
            'data' => [
                'sku' => $sku,
                'categoria' => $categoria,
            ],
        ];
    }

    /**
     * HU-029: Registrar salida automática por consumo de orden
     * POST /api/inventario/{id}/salida-orden
     */
    public function actionSalidaOrden($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        
        $transaction = $db->beginTransaction();
        
        try {
            $cantidad = (int)$request->post('cantidad');
            $ordenId = $request->post('orden_id');
            $razon = $request->post('razon', 'Consumo por orden de servicio #' . $ordenId);

            // Validaciones
            if ($cantidad <= 0) {
                throw new BadRequestHttpException('La cantidad debe ser mayor a cero');
            }

            // HU-029: Validar stock suficiente antes de consumir
            if ($model->stock_actual < $cantidad) {
                throw new BadRequestHttpException(
                    'Stock insuficiente. Disponible: ' . $model->stock_actual . ', Requerido: ' . $cantidad
                );
            }

            // Decrementar stock
            $model->stock_actual -= $cantidad;

            if (!$model->save()) {
                throw new BadRequestHttpException(json_encode($model->getErrors()));
            }

            // Registrar movimiento de salida
            $movement = new InventoryMovement();
            $movement->inventory_item_id = $model->id;
            $movement->tipo = 'salida';
            $movement->cantidad = $cantidad;
            $movement->precio_unitario = $model->precio_costo;
            $movement->razon = $razon;
            $movement->reference = 'ORDEN:' . $ordenId;
            $movement->created_by = Yii::$app->user->id ?? null;
            $movement->created_at = date('Y-m-d H:i:s');
            
            if (!$movement->save()) {
                throw new BadRequestHttpException(json_encode($movement->getErrors()));
            }

            // HU-022: Generar notificación si queda stock bajo
            if ($model->isStockBajo()) {
                $this->generarNotificacionStockBajo($model);
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => [
                    'item' => $model,
                    'movimiento' => $movement,
                    'stock_restante' => $model->stock_actual,
                ],
                'message' => 'Salida registrada correctamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Generar notificación de stock bajo (HU-022)
     */
    private function generarNotificacionStockBajo(InventoryItem $item): void
    {
        // Aquí se implementaría la lógica para guardar notificaciones en BD
        // o enviar emails/push notifications
        // Por ahora, solo registramos en log
        Yii::warning(
            "ALERTA STOCK BAJO: {$item->nombre} (SKU: {$item->codigo}) - Stock actual: {$item->stock_actual}, Mínimo: {$item->stock_minimo}",
            __METHOD__
        );
    }
}
