<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\db\Transaction;
use Yii;

/**
 * Controlador REST API para Órdenes de Servicio
 */
class OrdenServicioController extends BaseController
{
    public $modelClass = OrdenServicio::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'finalizar', 'agregar-servicio', 'eliminar-servicio', 'actualizar-precio'];
        return $behaviors;
    }

    /**
     * Listar órdenes de servicio con paginación y filtros
     * GET /api/ordenes-servicio
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = OrdenServicio::find()
            ->joinWith(['cliente', 'vehiculo'])
            ->orderBy(['created_at' => SORT_DESC]);

        $request = Yii::$app->request;
        $estado = $request->get('estado');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $clienteId = $request->get('cliente_id');
        $search = $request->get('search');

        if ($estado) {
            $query->andWhere(['orden_servicio.estado' => $estado]);
        }

        if ($fechaDesde) {
            $query->andWhere(['>=', 'orden_servicio.created_at', $fechaDesde]);
        }

        if ($fechaHasta) {
            $query->andWhere(['<=', 'orden_servicio.created_at', $fechaHasta]);
        }

        if ($clienteId) {
            $query->andWhere(['orden_servicio.cliente_id' => $clienteId]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'cliente.nombre', $search],
                ['like', 'vehiculo.placa', $search],
                ['like', 'orden_servicio.numero_orden', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener orden de servicio por ID
     * GET /api/ordenes-servicio/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        // Obtener detalles con información completa del servicio
        $detalles = [];
        foreach ($model->detalles as $detalle) {
            $detalles[] = [
                'id' => $detalle->id,
                'servicio_id' => $detalle->servicio_id,
                'servicio_nombre' => $detalle->servicio ? $detalle->servicio->nombre : $detalle->descripcion,
                'descripcion' => $detalle->descripcion,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'precio_original' => $detalle->precio_original,
                'subtotal' => $detalle->subtotal,
                'tipo' => $detalle->tipo,
                'notas' => $detalle->notas ?? '',
                'duracion_estimada' => $detalle->duracionEstimada,
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'id' => $model->id,
                'numero_orden' => $model->numero_orden,
                'estado' => $model->estado,
                'cliente' => $model->cliente,
                'vehiculo' => $model->vehiculo,
                'descripcion_problema' => $model->descripcion_problema,
                'diagnostico' => $model->diagnostico,
                'notas_internas' => $model->notas_internas,
                'kilometraje' => $model->kilometraje,
                'total' => $model->total,
                'esta_finalizada' => $model->estaFinalizada,
                'esta_facturada' => $model->estaFacturada,
                'duracion_total_minutos' => $model->duracionTotal,
                'duracion_total_formateada' => $model->duracionTotalFormateada,
                'created_at' => $model->created_at,
                'updated_at' => $model->updated_at,
                'detalles' => $detalles,
            ],
        ];
    }

    /**
     * Crear nueva orden de servicio
     * POST /api/ordenes-servicio
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        
        $transaction = $db->beginTransaction(Transaction::SERIALIZABLE);
        
        try {
            $model = new OrdenServicio();
            $model->cita_id = $request->post('cita_id');
            $model->cliente_id = $request->post('cliente_id');
            $model->vehiculo_id = $request->post('vehiculo_id');
            $model->numero_orden = $this->generarNumeroOrden();
            $model->estado = $request->post('estado', 'pendiente');
            $model->descripcion_problema = $request->post('descripcion_problema');
            $model->diagnostico = $request->post('diagnostico');
            $model->notas_internas = $request->post('notas_internas');
            $model->kilometraje = (int)$request->post('kilometraje', 0);
            $model->created_by = Yii::$app->user->id ?? null;

            if (!$model->save()) {
                throw new BadRequestHttpException(json_encode($model->getErrors()));
            }

            // Guardar detalles de la orden
            $detalles = $request->post('detalles', []);
            foreach ($detalles as $detalleData) {
                $detalle = new OrdenServicioDetalle();
                $detalle->orden_servicio_id = $model->id;
                $detalle->servicio_id = $detalleData['servicio_id'] ?? null;
                $detalle->descripcion = $detalleData['descripcion'];
                $detalle->cantidad = (int)($detalleData['cantidad'] ?? 1);
                $detalle->precio_unitario = (float)($detalleData['precio_unitario'] ?? 0);
                $detalle->tipo = $detalleData['tipo'] ?? 'servicio';
                
                if (!$detalle->save()) {
                    throw new BadRequestHttpException(json_encode($detalle->getErrors()));
                }
            }

            $transaction->commit();

            // Actualizar estado de la cita si existe
            if ($model->cita_id) {
                $cita = \app\models\Cita::findOne($model->cita_id);
                if ($cita) {
                    $cita->estado = 'completada';
                    $cita->save(false);
                }
            }

            return [
                'success' => true,
                'data' => $model,
                'message' => 'Orden de servicio creada correctamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Actualizar orden de servicio existente
     * PUT/PATCH /api/ordenes-servicio/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->estado = $request->post('estado', $model->estado);
        $model->descripcion_problema = $request->post('descripcion_problema', $model->descripcion_problema);
        $model->diagnostico = $request->post('diagnostico', $model->diagnostico);
        $model->notas_internas = $request->post('notas_internas', $model->notas_internas);
        $model->kilometraje = $request->post('kilometraje', $model->kilometraje);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Orden de servicio actualizada correctamente',
        ];
    }

    /**
     * Finalizar orden de servicio
     * POST /api/ordenes-servicio/{id}/finalizar
     */
    public function actionFinalizar($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        
        if ($model->estado === 'finalizada') {
            throw new BadRequestHttpException('La orden ya está finalizada');
        }

        $model->estado = 'finalizada';
        $model->finalizada_en = date('Y-m-d H:i:s');
        $model->finalizada_por = Yii::$app->user->id ?? null;
        
        // Calcular total
        $total = 0;
        foreach ($model->detalles as $detalle) {
            $total += $detalle->cantidad * $detalle->precio_unitario;
        }
        $model->total = $total;

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'message' => 'Orden de servicio finalizada correctamente',
            'total' => $total,
        ];
    }

    /**
     * Eliminar orden de servicio
     * DELETE /api/ordenes-servicio/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $model->delete();

        return [
            'success' => true,
            'message' => 'Orden de servicio eliminada correctamente',
        ];
    }

    /**
     * Agregar servicio a orden existente
     * POST /api/ordenes-servicio/{id}/agregar-servicio
     */
    public function actionAgregarServicio($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        // Validar que la orden no esté finalizada/cerrada
        if ($model->estaFinalizada) {
            throw new BadRequestHttpException('No se pueden agregar servicios a una orden finalizada');
        }

        $servicioId = $request->post('servicio_id');
        $cantidad = (int)($request->post('cantidad', 1));
        $notas = $request->post('notas', '');

        if (!$servicioId) {
            throw new BadRequestHttpException('El ID del servicio es requerido');
        }

        // Obtener servicio del catálogo
        $servicio = Servicio::findOne($servicioId);
        if (!$servicio) {
            throw new NotFoundHttpException('Servicio no encontrado en el catálogo');
        }

        // Crear detalle de orden
        $detalle = new OrdenServicioDetalle();
        $detalle->orden_servicio_id = $model->id;
        $detalle->servicio_id = $servicioId;
        $detalle->descripcion = $servicio->nombre;
        $detalle->cantidad = $cantidad;
        $detalle->precio_unitario = (float)$request->post('precio_unitario', $servicio->precio);
        $detalle->precio_original = $servicio->precio;
        $detalle->tipo = 'servicio';
        $detalle->notas = $notas;

        if (!$detalle->save()) {
            throw new BadRequestHttpException(json_encode($detalle->getErrors()));
        }

        // Recalcular total de la orden
        $total = 0;
        foreach ($model->detalles as $d) {
            $total += $d->cantidad * $d->precio_unitario;
        }
        $model->total = $total;
        $model->save(false);

        return [
            'success' => true,
            'data' => $detalle,
            'message' => 'Servicio agregado correctamente a la orden',
            'total_actualizado' => $total,
        ];
    }

    /**
     * Eliminar servicio de orden existente
     * DELETE /api/ordenes-servicio/{idOrden}/eliminar-servicio/{idDetalle}
     */
    public function actionEliminarServicio($idOrden, $idDetalle): array
    {
        $model = $this->findModel($idOrden);

        // Validar que la orden no esté facturada
        if ($model->estaFacturada) {
            throw new BadRequestHttpException('No se pueden eliminar servicios de una orden facturada');
        }

        // Buscar el detalle
        $detalle = OrdenServicioDetalle::findOne($idDetalle);
        if (!$detalle) {
            throw new NotFoundHttpException('Detalle de orden no encontrado');
        }

        // Verificar que el detalle pertenezca a la orden
        if ($detalle->orden_servicio_id != $idOrden) {
            throw new BadRequestHttpException('El detalle no pertenece a esta orden');
        }

        // Eliminar el detalle
        $detalle->delete();

        // Recalcular total de la orden
        $total = 0;
        foreach ($model->detalles as $d) {
            $total += $d->cantidad * $d->precio_unitario;
        }
        $model->total = $total;
        $model->save(false);

        return [
            'success' => true,
            'message' => 'Servicio eliminado correctamente de la orden',
            'total_actualizado' => $total,
        ];
    }

    /**
     * Modificar precio de servicio en orden
     * PUT /api/ordenes-servicio/{idOrden}/actualizar-precio/{idDetalle}
     */
    public function actionActualizarPrecio($idOrden, $idDetalle): array
    {
        $model = $this->findModel($idOrden);
        $request = Yii::$app->request;

        // Validar que la orden no esté facturada
        if ($model->estaFacturada) {
            throw new BadRequestHttpException('No se puede modificar el precio de una orden facturada');
        }

        // Buscar el detalle
        $detalle = OrdenServicioDetalle::findOne($idDetalle);
        if (!$detalle) {
            throw new NotFoundHttpException('Detalle de orden no encontrado');
        }

        // Verificar que el detalle pertenezca a la orden
        if ($detalle->orden_servicio_id != $idOrden) {
            throw new BadRequestHttpException('El detalle no pertenece a esta orden');
        }

        $nuevoPrecio = (float)$request->post('precio_unitario');
        $justificacion = $request->post('justificacion', '');

        if ($nuevoPrecio < 0) {
            throw new BadRequestHttpException('El precio no puede ser negativo');
        }

        // Guardar precio anterior para auditoría
        $precioAnterior = $detalle->precio_unitario;

        // Actualizar precio
        $detalle->precio_unitario = $nuevoPrecio;
        
        if (!$detalle->save()) {
            throw new BadRequestHttpException(json_encode($detalle->getErrors()));
        }

        // Registrar en log de auditoría si hay justificación
        if ($justificacion || $precioAnterior != $nuevoPrecio) {
            $auditLog = new \app\models\AuditLog();
            $auditLog->tabla_afectada = 'orden_servicio_detalle';
            $auditLog->registro_id = $detalle->id;
            $auditLog->accion = 'cambio_precio';
            $auditLog->datos_antiguos = json_encode(['precio_unitario' => $precioAnterior]);
            $auditLog->datos_nuevos = json_encode([
                'precio_unitario' => $nuevoPrecio,
                'justificacion' => $justificacion,
            ]);
            $auditLog->usuario_id = Yii::$app->user->id ?? null;
            $auditLog->fecha = date('Y-m-d H:i:s');
            $auditLog->ip = Yii::$app->request->userIP ?? null;
            $auditLog->save(false);
        }

        // Recalcular total de la orden
        $total = 0;
        foreach ($model->detalles as $d) {
            $total += $d->cantidad * $d->precio_unitario;
        }
        $model->total = $total;
        $model->save(false);

        return [
            'success' => true,
            'message' => 'Precio actualizado correctamente',
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $nuevoPrecio,
            'total_actualizado' => $total,
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): OrdenServicio
    {
        $model = OrdenServicio::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Orden de servicio no encontrada');
        }

        return $model;
    }

    /**
     * Generar número de orden único
     */
    private function generarNumeroOrden(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastOrden = OrdenServicio::find()
            ->where(['like', 'numero_orden', "OS-{$year}{$month}-", false])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        
        $consecutivo = 1;
        if ($lastOrden) {
            $parts = explode('-', $lastOrden->numero_orden);
            $consecutivo = (int)end($parts) + 1;
        }

        return sprintf('OS-%s%04d', $year . $month, $consecutivo);
    }
}
