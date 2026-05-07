<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Pago;
use app\models\CierreCaja;
use app\models\DocumentoTributario;
use app\models\OrdenServicio;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use Yii;

/**
 * Controlador REST API para Pagos y Documentos Tributarios
 * Soporta: HU-001, HU-002, HU-003, HU-004, HU-005, HU-007, HU-008, HU-009, 
 *          HU-010, HU-011, HU-012, HU-013, HU-014, HU-016, HU-017, HU-019, 
 *          HU-020, HU-022, HU-023, HU-024, HU-025, HU-027
 */
class PagoController extends BaseController
{
    public $modelClass = Pago::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = [
            'index', 'view', 'create', 'update', 'delete',
            'anular', 'historial', 'estado-cobranza', 'saldo-pendiente',
            'cerrar-caja', 'resumen-diario', 'emitir-boleta', 'emitir-nota-credito'
        ];
        return $behaviors;
    }

    /**
     * Listar pagos con filtros (HU-011, HU-028)
     * GET /api/pagos
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Pago::find()
            ->joinWith(['ordenServicio', 'cliente'])
            ->orderBy(['created_at' => SORT_DESC]);

        $request = Yii::$app->request;
        $estado = $request->get('estado');
        $metodoPago = $request->get('metodo_pago');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $ordenId = $request->get('orden_id');

        if ($estado) {
            $query->andWhere(['pago.estado' => $estado]);
        }

        if ($metodoPago) {
            $query->andWhere(['pago.metodo_pago' => $metodoPago]);
        }

        if ($fechaDesde) {
            $query->andWhere(['>=', 'pago.fecha_pago', $fechaDesde]);
        }

        if ($fechaHasta) {
            $query->andWhere(['<=', 'pago.fecha_pago', $fechaHasta]);
        }

        if ($ordenId) {
            $query->andWhere(['pago.orden_servicio_id' => $ordenId]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Ver detalle de pago (HU-011)
     * GET /api/pagos/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        return [
            'success' => true,
            'data' => [
                'id' => $model->id,
                'folio' => $model->folio,
                'monto' => $model->monto,
                'monto_formateado' => $model->montoFormateado,
                'metodo_pago' => $model->metodo_pago,
                'estado' => $model->estado,
                'referencia' => $model->referencia,
                'referencia_tarjeta' => $model->referencia_tarjeta, // HU-013
                'notas' => $model->notas, // HU-027
                'fecha_pago' => $model->fecha_pago,
                'orden' => $model->ordenServicio,
                'cliente' => $model->cliente,
                'creador' => $model->creador,
            ],
        ];
    }

    /**
     * Registrar nuevo pago (HU-001, HU-002, HU-003, HU-005, HU-013, HU-014, HU-027)
     * POST /api/pagos
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $model = new Pago();
            $model->orden_servicio_id = $request->post('orden_servicio_id');
            $model->cliente_id = $request->post('cliente_id');
            $model->monto = (float)$request->post('monto');
            
            // HU-014: Validar monto no negativo
            if ($model->monto < 0) {
                throw new BadRequestHttpException('El monto no puede ser negativo');
            }
            
            $model->metodo_pago = $request->post('metodo_pago');
            $model->referencia = $request->post('referencia');
            $model->notas = $request->post('notas'); // HU-027
            
            // HU-013: Registrar últimos 4 dígitos de tarjeta
            if (in_array($model->metodo_pago, [Pago::METODO_TARJETA_CREDITO, Pago::METODO_TARJETA_DEBITO])) {
                $modelo->referencia_tarjeta = $request->post('referencia_tarjeta');
            }

            if (!$model->save()) {
                throw new BadRequestHttpException(json_encode($model->getErrors()));
            }

            // Actualizar estado de la orden si está completamente pagada
            $orden = OrdenServicio::findOne($model->orden_servicio_id);
            if ($orden && Pago::isOrdenPagada($orden->id)) {
                $orden->estado = OrdenServicio::ESTADO_ENTREGADA;
                $orden->save(false);
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => $model,
                'message' => 'Pago registrado correctamente',
                'vuelto' => Pago::calcularVuelto($model->monto, $orden->total ?? 0), // HU-004
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Anular pago (HU-012)
     * POST /api/pagos/{id}/anular
     */
    public function actionAnular($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        
        $usuarioId = Yii::$app->user->id ?? null;
        $motivo = $request->post('motivo');

        if (!$model->anular($usuarioId, $motivo)) {
            throw new BadRequestHttpException('No se pudo anular el pago');
        }

        return [
            'success' => true,
            'message' => 'Pago anulado correctamente',
            'data' => $model,
        ];
    }

    /**
     * Obtener historial de pagos de una orden (HU-011)
     * GET /api/pagos/historial?orden_id={id}
     */
    public function actionHistorial(): array
    {
        $ordenId = Yii::$app->request->get('orden_id');
        
        if (!$ordenId) {
            throw new BadRequestHttpException('El ID de la orden es requerido');
        }

        $pagos = Pago::getHistorialPorOrden((int)$ordenId);
        
        return [
            'success' => true,
            'data' => array_map(function($pago) {
                return [
                    'id' => $pago->id,
                    'folio' => $pago->folio,
                    'monto' => $pago->monto,
                    'monto_formateado' => $pago->montoFormateado,
                    'metodo_pago' => $pago->metodo_pago,
                    'estado' => $pago->estado,
                    'fecha_pago' => $pago->fecha_pago,
                    'notas' => $pago->notas,
                ];
            }, $pagos),
        ];
    }

    /**
     * Obtener estado de cobranza de una orden (HU-006, HU-023)
     * GET /api/pagos/estado-cobranza?orden_id={id}
     */
    public function actionEstadoCobranza(): array
    {
        $ordenId = Yii::$app->request->get('orden_id');
        
        if (!$ordenId) {
            throw new BadRequestHttpException('El ID de la orden es requerido');
        }

        $estado = Pago::getEstadoCobranza((int)$ordenId);
        $saldoPendiente = Pago::getSaldoPendiente((int)$ordenId);

        return [
            'success' => true,
            'data' => [
                'estado' => $estado,
                'saldo_pendiente' => $saldoPendiente,
                'es_pagada' => Pago::isOrdenPagada((int)$ordenId),
            ],
        ];
    }

    /**
     * Obtener saldo pendiente de una orden (HU-005, HU-023)
     * GET /api/pagos/saldo-pendiente?orden_id={id}
     */
    public function actionSaldoPendiente(): array
    {
        $ordenId = Yii::$app->request->get('orden_id');
        
        if (!$ordenId) {
            throw new BadRequestHttpException('El ID de la orden es requerido');
        }

        return [
            'success' => true,
            'data' => [
                'orden_id' => (int)$ordenId,
                'saldo_pendiente' => Pago::getSaldoPendiente((int)$ordenId),
            ],
        ];
    }

    /**
     * Cerrar caja diaria (HU-008)
     * POST /api/pagos/cerrar-caja
     */
    public function actionCerrarCaja(): array
    {
        $request = Yii::$app->request;
        $cierre = CierreCaja::getCierreActual();

        if (!$cierre) {
            throw new BadRequestHttpException('No hay una caja abierta para cerrar');
        }

        $montoFinal = (float)$request->post('monto_final');
        $observaciones = $request->post('observaciones');

        if (!$cierre->cerrar($montoFinal, $observaciones)) {
            throw new BadRequestHttpException('No se pudo cerrar la caja');
        }

        return [
            'success' => true,
            'message' => 'Caja cerrada correctamente',
            'data' => [
                'id' => $cierre->id,
                'monto_inicial' => $cierre->montoInicialFormateado,
                'monto_final' => $cierre->montoFinalFormateado,
                'total_ingresos' => $cierre->totalIngresos,
                'balance_final' => $cierre->balanceFinal,
                'fecha_cierre' => $cierre->fecha_fin,
            ],
        ];
    }

    /**
     * Obtener resumen diario de ventas (HU-008, HU-009, HU-016)
     * GET /api/pagos/resumen-diario?fecha={Y-m-d}
     */
    public function actionResumenDiario(): array
    {
        $fecha = Yii::$app->request->get('fecha', date('Y-m-d'));
        $resumen = CierreCaja::generarResumenDia($fecha);

        return [
            'success' => true,
            'data' => [
                'fecha' => $fecha,
                'total_ventas' => $resumen['total'],
                'cantidad_ordenes' => $resumen['cantidad_pagos'],
                'promedio_venta' => $resumen['cantidad_pagos'] > 0 ? $resumen['total'] / $resumen['cantidad_pagos'] : 0,
                'desglose_metodos' => [
                    'efectivo' => $resumen['efectivo'],
                    'transferencia' => $resumen['transferencia'],
                    'tarjeta_credito' => $resumen['tarjeta_credito'],
                    'tarjeta_debito' => $resumen['tarjeta_debito'],
                ],
            ],
        ];
    }

    /**
     * Emitir boleta electrónica (HU-010, HU-024)
     * POST /api/pagos/emitir-boleta
     */
    public function actionEmitirBoleta(): array
    {
        $request = Yii::$app->request;
        $ordenId = $request->post('orden_servicio_id');
        
        if (!$ordenId) {
            throw new BadRequestHttpException('El ID de la orden es requerido');
        }

        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new NotFoundHttpException('Orden no encontrada');
        }

        $boleta = new DocumentoTributario();
        $boleta->tipo = DocumentoTributario::TIPO_BOLETA;
        $boleta->orden_servicio_id = $ordenId;
        $boleta->cliente_id = $orden->cliente_id;
        $boleta->monto_total = $orden->total;
        $boleta->monto_neto = $orden->total / 1.19;
        $boleta->iva = $orden->total - $boleta->monto_neto;

        if (!$boleta->emitir(Yii::$app->user->id ?? null)) {
            throw new BadRequestHttpException('No se pudo emitir la boleta');
        }

        return [
            'success' => true,
            'message' => 'Boleta emitida correctamente',
            'data' => [
                'folio_interno' => $boleta->folio,
                'folio_sii' => $boleta->folio_sii,
                'monto_total' => $boleta->montoTotalFormateado,
                'fecha_emision' => $boleta->fecha_emision,
                'estado' => $boleta->estado,
            ],
        ];
    }

    /**
     * Emitir nota de crédito (HU-019)
     * POST /api/pagos/emitir-nota-credito
     */
    public function actionEmitirNotaCredito(): array
    {
        $request = Yii::$app->request;
        $ordenId = $request->post('orden_servicio_id');
        $monto = (float)$request->post('monto');
        $motivo = $request->post('motivo');

        if (!$ordenId || !$monto || !$motivo) {
            throw new BadRequestHttpException('Todos los campos son requeridos');
        }

        $nota = DocumentoTributario::emitirNotaCredito(
            (int)$ordenId, 
            $monto, 
            $motivo, 
            Yii::$app->user->id ?? null
        );

        if (!$nota) {
            throw new BadRequestHttpException('No se pudo emitir la nota de crédito');
        }

        return [
            'success' => true,
            'message' => 'Nota de crédito emitida correctamente',
            'data' => [
                'folio_interno' => $nota->folio,
                'folio_sii' => $nota->folio_sii,
                'monto_total' => $nota->montoTotalFormateado,
                'motivo' => $nota->observaciones,
            ],
        ];
    }

    protected function findModel($id): Pago
    {
        $model = Pago::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Pago no encontrado');
        }
        return $model;
    }
}
