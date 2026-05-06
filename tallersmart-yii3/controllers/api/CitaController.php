<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Cita;
use app\models\CitaServicio;
use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;
use app\models\AuditLog;
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
        $behaviors['access']['rules'][0]['actions'] = [
            'index', 'view', 'create', 'update', 'delete', 
            'cancel', 'confirmar', 'no-show', 'iniciar-servicio',
            'reprogramar', 'estadisticas', 'verificar-solapamiento'
        ];
        return $behaviors;
    }

    /**
     * Listar citas con paginación y filtros
     * GET /api/citas
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Cita::find()
            ->joinWith(['cliente', 'vehiculo', 'tecnico', 'citaServicios'])
            ->orderBy(['fecha_hora' => SORT_DESC]);

        $request = Yii::$app->request;
        $estado = $request->get('estado');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $clienteId = $request->get('cliente_id');
        $search = $request->get('search');
        $tecnicoId = $request->get('tecnico_id');

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

        if ($tecnicoId) {
            $query->andWhere(['cita.tecnico_id' => $tecnicoId]);
        }

        // HU-013: Ocultar canceladas por defecto
        $verCanceladas = $request->get('ver_canceladas', false);
        if (!$verCanceladas) {
            $query->andWhere(['not', ['cita.estado' => [Cita::ESTADO_CANCELADA]]]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'cliente.nombre', $search],
                ['like', 'cliente.rut', $search],
                ['like', 'vehiculo.placa', $search],
                ['like', 'vehiculo.patente', $search],
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
            $model->hora_inicio = $request->post('hora_inicio');
            $model->hora_fin = $request->post('hora_fin');
            $model->estado = $request->post('estado', Cita::ESTADO_PENDIENTE);
            $model->notas = $request->post('notas');
            $model->telefono_contacto = $request->post('telefono_contacto');
            $model->tecnico_id = $request->post('tecnico_id');
            $model->created_by = Yii::$app->user->id ?? null;

            // HU-005: Validar solapamiento de horarios
            if ($model->haySolapamiento()) {
                throw new BadRequestHttpException('Existe un conflicto de horarios en la fecha seleccionada');
            }

            // HU-029: Verificar capacidad del día
            if (Cita::esDiaLleno($model->fecha_hora)) {
                Yii::warning("Workshop Full para fecha {$model->fecha_hora}");
            }

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

            // HU-009: Registrar auditoría de creación
            AuditLog::registrarAccion(
                'CREATE',
                'Cita',
                $model->id,
                null,
                json_encode($model->attributes),
                'Cita'
            );

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

        // HU-026: No editar citas iniciadas
        if (!$model->puedeEditar()) {
            throw new BadRequestHttpException('No se puede editar cita en progreso, completada o cancelada');
        }

        $estadoAnterior = $model->estado;

        $model->cliente_id = $request->post('cliente_id', $model->cliente_id);
        $model->vehiculo_id = $request->post('vehiculo_id', $model->vehiculo_id);
        $model->fecha_hora = $request->post('fecha_hora', $model->fecha_hora);
        $model->hora_inicio = $request->post('hora_inicio', $model->hora_inicio);
        $model->hora_fin = $request->post('hora_fin', $model->hora_fin);
        $model->estado = $request->post('estado', $model->estado);
        $model->notas = $request->post('notas', $model->notas);
        $model->telefono_contacto = $request->post('telefono_contacto', $model->telefono_contacto);
        $model->tecnico_id = $request->post('tecnico_id', $model->tecnico_id);

        // HU-005: Validar solapamiento si cambió la hora
        if ($model->isAttributeChanged('fecha_hora') || 
            $model->isAttributeChanged('hora_inicio') || 
            $model->isAttributeChanged('hora_fin')) {
            if ($model->haySolapamiento()) {
                throw new BadRequestHttpException('Existe un conflicto de horarios en la fecha seleccionada');
            }
        }

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        // HU-009: Registrar auditoría si cambió el estado
        if ($estadoAnterior !== $model->estado) {
            AuditLog::registrarAccion(
                'UPDATE',
                'Cita',
                $model->id,
                json_encode(['estado' => $estadoAnterior]),
                json_encode(['estado' => $model->estado]),
                'Cita'
            );
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Cita actualizada correctamente',
        ];
    }

    /**
     * HU-016: Confirmar cita pendiente
     * POST /api/citas/{id}/confirmar
     */
    public function actionConfirmar($id): array
    {
        $model = $this->findModel($id);
        
        if ($model->estado !== Cita::ESTADO_PENDIENTE) {
            throw new BadRequestHttpException('Solo se pueden confirmar citas pendientes');
        }

        if (!$model->cambiarEstado(Cita::ESTADO_CONFIRMADA)) {
            throw new BadRequestHttpException('No se pudo confirmar la cita');
        }

        return [
            'success' => true,
            'message' => 'Cita confirmada correctamente',
        ];
    }

    /**
     * Cancelar cita
     * POST /api/citas/{id}/cancel
     */
    public function actionCancel($id): array
    {
        $model = $this->findModel($id);
        
        if ($model->estado === Cita::ESTADO_CANCELADA) {
            throw new BadRequestHttpException('La cita ya está cancelada');
        }

        $request = Yii::$app->request;
        $motivo = $request->post('motivo', null);

        if (!$model->cambiarEstado(Cita::ESTADO_CANCELADA, $motivo)) {
            throw new BadRequestHttpException('No se pudo cancelar la cita');
        }

        return [
            'success' => true,
            'message' => 'Cita cancelada correctamente',
        ];
    }

    /**
     * HU-018: Marcar cita como no-show
     * POST /api/citas/{id}/no-show
     */
    public function actionNoShow($id): array
    {
        $model = $this->findModel($id);
        
        if (in_array($model->estado, [Cita::ESTADO_CANCELADA, Cita::ESTADO_COMPLETADA, Cita::ESTADO_NO_SHOW])) {
            throw new BadRequestHttpException('No se puede marcar como no-show esta cita');
        }

        $request = Yii::$app->request;
        $motivo = $request->post('motivo', null);

        if (!$model->cambiarEstado(Cita::ESTADO_NO_SHOW, $motivo)) {
            throw new BadRequestHttpException('No se pudo marcar la cita como no-show');
        }

        return [
            'success' => true,
            'message' => 'Cita marcada como no-show correctamente',
        ];
    }

    /**
     * HU-007: Iniciar servicio desde cita - Crear Orden de Servicio
     * POST /api/citas/{id}/iniciar-servicio
     */
    public function actionIniciarServicio($id): array
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction(Transaction::SERIALIZABLE);
        
        try {
            $cita = $this->findModel($id);
            
            if ($cita->estado !== Cita::ESTADO_CONFIRMADA) {
                throw new BadRequestHttpException('Solo se pueden iniciar citas confirmadas');
            }

            // Crear Orden de Servicio
            $orden = new OrdenServicio();
            $orden->cita_id = $cita->id;
            $orden->cliente_id = $cita->cliente_id;
            $orden->vehiculo_id = $cita->vehiculo_id;
            $orden->tecnico_id = $cita->tecnico_id;
            $orden->estado = 'en_progreso';
            $orden->prioridad = 'media';
            $orden->descripcion_problema = $cita->notas;
            $orden->created_by = Yii::$app->user->id ?? null;

            if (!$orden->save()) {
                throw new BadRequestHttpException(json_encode($orden->getErrors()));
            }

            // Copiar servicios de la cita a la orden
            $citaServicios = $cita->citaServicios;
            foreach ($citaServicios as $citaServicio) {
                $detalle = new OrdenServicioDetalle();
                $detalle->orden_servicio_id = $orden->id;
                $detalle->servicio_id = $citaServicio->servicio_id;
                $detalle->cantidad = $citaServicio->cantidad;
                $detalle->precio_unitario = $citaServicio->precio_unitario;
                
                if (!$detalle->save()) {
                    throw new BadRequestHttpException(json_encode($detalle->getErrors()));
                }
            }

            // Actualizar estado de la cita a "En Progreso"
            if (!$cita->cambiarEstado(Cita::ESTADO_EN_PROGRESO, 'Orden de servicio creada: #' . $orden->id)) {
                throw new BadRequestHttpException('No se pudo actualizar el estado de la cita');
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => [
                    'orden' => $orden,
                    'cita' => $cita,
                ],
                'message' => 'Orden de servicio creada exitosamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * HU-030: Reprogramar cita
     * POST /api/citas/{id}/reprogramar
     */
    public function actionReprogramar($id): array
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        
        try {
            $cita = $this->findModel($id);
            $request = Yii::$app->request;
            
            if (!in_array($cita->estado, [Cita::ESTADO_PENDIENTE, Cita::ESTADO_CONFIRMADA])) {
                throw new BadRequestHttpException('Solo se pueden reprogramar citas pendientes o confirmadas');
            }

            $nuevaFecha = $request->post('nueva_fecha');
            $nuevaHoraInicio = $request->post('nueva_hora_inicio');
            $nuevaHoraFin = $request->post('nueva_hora_fin');

            if (!$nuevaFecha || !$nuevaHoraInicio || !$nuevaHoraFin) {
                throw new BadRequestHttpException('Debe especificar nueva fecha y horario');
            }

            // Verificar solapamiento con nuevo horario
            $citaOriginalFecha = $cita->fecha_hora;
            $citaOriginalHoraInicio = $cita->hora_inicio;
            $citaOriginalHoraFin = $cita->hora_fin;
            
            $cita->fecha_hora = $nuevaFecha;
            $cita->hora_inicio = $nuevaHoraInicio;
            $cita->hora_fin = $nuevaHoraFin;
            
            if ($cita->haySolapamiento()) {
                throw new BadRequestHttpException('Existe un conflicto de horarios en la nueva fecha/hora seleccionada');
            }

            // Guardar cambios
            $cita->fecha_hora = $nuevaFecha;
            $cita->hora_inicio = $nuevaHoraInicio;
            $cita->hora_fin = $nuevaHoraFin;
            
            if (!$cita->save()) {
                throw new BadRequestHttpException(json_encode($cita->getErrors()));
            }

            // HU-009: Registrar auditoría
            AuditLog::registrarAccion(
                'UPDATE',
                'Cita',
                $cita->id,
                json_encode([
                    'fecha_hora' => $citaOriginalFecha,
                    'hora_inicio' => $citaOriginalHoraInicio,
                    'hora_fin' => $citaOriginalHoraFin,
                ]),
                json_encode([
                    'fecha_hora' => $nuevaFecha,
                    'hora_inicio' => $nuevaHoraInicio,
                    'hora_fin' => $nuevaHoraFin,
                ]),
                'Cita - Reprogramación'
            );

            // HU-030: Aquí se debería enviar email al cliente (pendiente implementación HU-008/HU-024)
            // Por ahora registramos que se debería notificar
            Yii::info("Cita #{$cita->id} reprogramada. Se debe notificar al cliente.");

            $transaction->commit();

            return [
                'success' => true,
                'data' => $cita,
                'message' => 'Cita reprogramada correctamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * HU-028: Estadísticas de citas por día
     * GET /api/citas/estadisticas
     */
    public function actionEstadisticas(): array
    {
        $request = Yii::$app->request;
        $mes = $request->get('mes', date('Y-m'));
        
        $primerDia = $mes . '-01';
        $ultimoDia = date('Y-m-t', strtotime($primerDia));
        
        $estadisticas = [];
        
        // Total por estado
        $totalPorEstado = Cita::find()
            ->select(['estado', 'COUNT(*) as total'])
            ->where(['between', 'fecha_hora', $primerDia, $ultimoDia])
            ->groupBy('estado')
            ->asArray()
            ->all();
        
        // Citas por día
        $citasPorDia = Cita::find()
            ->select(['fecha_hora', 'COUNT(*) as total'])
            ->where(['between', 'fecha_hora', $primerDia, $ultimoDia])
            ->groupBy('fecha_hora')
            ->orderBy(['fecha_hora' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Resumen
        $resumen = [
            'total' => array_sum(array_column($totalPorEstado, 'total')),
            'pendientes' => 0,
            'confirmadas' => 0,
            'en_progreso' => 0,
            'completadas' => 0,
            'canceladas' => 0,
            'no_show' => 0,
        ];
        
        foreach ($totalPorEstado as $item) {
            $key = strtolower(str_replace(' ', '_', $item['estado']));
            if (isset($resumen[$key])) {
                $resumen[$key] = (int)$item['total'];
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'mes' => $mes,
                'resumen' => $resumen,
                'por_estado' => $totalPorEstado,
                'por_dia' => $citasPorDia,
            ],
        ];
    }

    /**
     * HU-005: Verificar solapamiento de horarios
     * POST /api/citas/verificar-solapamiento
     */
    public function actionVerificarSolapamiento(): array
    {
        $request = Yii::$app->request;
        
        $cita = new Cita();
        $cita->fecha_hora = $request->post('fecha_hora');
        $cita->hora_inicio = $request->post('hora_inicio');
        $cita->hora_fin = $request->post('hora_fin');
        $cita->id = $request->post('id', null);
        
        $haySolapamiento = $cita->haySolapamiento();
        
        return [
            'success' => true,
            'data' => [
                'solapamiento' => $haySolapamiento,
                'mensaje' => $haySolapamiento ? 'Existe conflicto de horarios' : 'Horario disponible',
            ],
        ];
    }

    /**
     * Eliminar cita
     * DELETE /api/citas/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        
        // HU-026: No eliminar citas en progreso
        if ($model->estado === Cita::ESTADO_EN_PROGRESO) {
            throw new BadRequestHttpException('No se puede eliminar una cita en progreso');
        }
        
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
