<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Tecnico;
use app\models\Usuario;
use app\models\OrdenServicio;
use app\models\AuditoriaAsignacion;
use app\models\SolicitudDiaLibre;
use app\models\HorarioTecnico;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\db\Expression;
use Yii;

/**
 * Controlador REST API para Técnicos
 */
class TecnicoController extends BaseController
{
    public $modelClass = Tecnico::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = [
            'index', 'view', 'create', 'update', 'delete',
            'ordenes-asignadas', 'historial', 'productividad',
            'asignar-orden', 'desasignar-orden', 'transferir-orden',
            'disponibilidad', 'carga-trabajo', 'horas-trabajadas',
            'calificacion', 'ranking', 'exportar',
            'horarios', 'solicitudes-dia-libre', 'auditoria'
        ];
        return $behaviors;
    }

    /**
     * Listar técnicos con filtros
     * GET /api/tecnicos
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Tecnico::find()
            ->joinWith(['usuario'])
            ->orderBy(['createdAt' => SORT_DESC]);

        $request = Yii::$app->request;
        
        // Filtros
        $activo = $request->get('activo');
        $especialidad = $request->get('especialidad');
        $nivel = $request->get('nivel');
        $disponible = $request->get('disponible');
        $search = $request->get('search');

        if ($activo !== null) {
            $query->andWhere(['tecnico.activo' => (bool)$activo]);
        }

        if ($especialidad) {
            $query->andWhere(['LIKE', 'tecnico.especialidad', $especialidad]);
        }

        if ($nivel) {
            $query->andWhere(['tecnico.nivel' => $nivel]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'usuario.nombre', $search],
                ['like', 'usuario.email', $search],
                ['like', 'tecnico.especialidad', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Obtener técnico por ID con detalles completos
     * GET /api/tecnicos/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);

        return [
            'success' => true,
            'data' => [
                'id' => $model->id,
                'usuario' => $model->usuario,
                'especialidad' => $model->especialidad,
                'lista_especialidades' => $model->listaEspecialidades,
                'es_multiespecialidad' => $model->esMultiespecialidad,
                'nivel' => $model->nivel,
                'activo' => $model->activo,
                'estado' => $model->estado,
                'disponible' => $model->disponible,
                'cantidad_ordenes_activas' => $model->cantidadOrdenesActivas,
                'calificacion_promedio' => $model->calificacionPromedio,
                'createdAt' => $model->createdAt,
                'updatedAt' => $model->updatedAt,
            ],
        ];
    }

    /**
     * Crear nuevo técnico
     * POST /api/tecnicos
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // Crear usuario asociado
            $usuario = new Usuario();
            $usuario->nombre = $request->post('nombre');
            $usuario->email = $request->post('email');
            $usuario->telefono = $request->post('telefono');
            $usuario->setPassword($request->post('password') ?? 'temporal123');
            $usuario->activo = true;
            $usuario->idioma = 'es';
            
            if (!$usuario->save()) {
                throw new BadRequestHttpException('Error al crear usuario: ' . json_encode($usuario->errors));
            }

            // Crear técnico
            $tecnico = new Tecnico();
            $tecnico->usuarioId = $usuario->id;
            $tecnico->especialidad = $request->post('especialidad');
            $tecnico->nivel = $request->post('nivel', 'junior');
            $tecnico->activo = true;

            if (!$tecnico->save()) {
                throw new BadRequestHttpException('Error al crear técnico: ' . json_encode($tecnico->errors));
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Técnico creado correctamente',
                'data' => [
                    'id' => $tecnico->id,
                    'usuario_id' => $usuario->id,
                ],
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Actualizar técnico
     * PUT/PATCH /api/tecnicos/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        if ($request->post('especialidad') !== null) {
            $model->especialidad = $request->post('especialidad');
        }
        if ($request->post('nivel') !== null) {
            $model->nivel = $request->post('nivel');
        }
        if ($request->post('activo') !== null) {
            $model->activo = (bool)$request->post('activo');
        }

        // Actualizar usuario asociado
        if ($model->usuario) {
            if ($request->post('nombre') !== null) {
                $model->usuario->nombre = $request->post('nombre');
            }
            if ($request->post('email') !== null) {
                $model->usuario->email = $request->post('email');
            }
            if ($request->post('telefono') !== null) {
                $model->usuario->telefono = $request->post('telefono');
            }
            $model->usuario->save();
        }

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->errors));
        }

        return [
            'success' => true,
            'message' => 'Técnico actualizado correctamente',
            'data' => ['id' => $model->id],
        ];
    }

    /**
     * Eliminar técnico (soft delete - desactivar)
     * DELETE /api/tecnicos/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $model->activo = false;
        $model->save();

        return [
            'success' => true,
            'message' => 'Técnico desactivado correctamente',
        ];
    }

    /**
     * Obtener órdenes asignadas al técnico
     * GET /api/tecnicos/{id}/ordenes-asignadas
     */
    public function actionOrdenesAsignadas($id): array
    {
        $tecnico = $this->findModel($id);
        $request = Yii::$app->request;
        
        $estado = $request->get('estado');
        
        $query = $tecnico->getOrdenesServicio();

        if ($estado) {
            $query->andWhere(['estado' => $estado]);
        }

        $ordenes = $query->orderBy(['created_at' => SORT_DESC])->all();

        $data = [];
        foreach ($ordenes as $orden) {
            $data[] = [
                'id' => $orden->id,
                'folio' => $orden->folio,
                'estado' => $orden->estado,
                'prioridad' => $orden->prioridad,
                'cliente' => $orden->cliente,
                'vehiculo' => $orden->vehiculo,
                'fechaEntregaEstimada' => $orden->fechaEntregaEstimada,
                'created_at' => $orden->created_at,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Obtener historial de trabajos del técnico
     * GET /api/tecnicos/{id}/historial
     */
    public function actionHistorial($id): array
    {
        $tecnico = $this->findModel($id);
        $request = Yii::$app->request;
        $limite = (int)$request->get('limite', 50);

        $historial = $tecnico->getHistorialTrabajos($limite);

        $data = [];
        foreach ($historial as $orden) {
            $tiempoReal = null;
            if ($orden->fechaEntregaReal && $orden->created_at) {
                $diff = strtotime($orden->fechaEntregaReal) - strtotime($orden->created_at);
                $tiempoReal = max(0, round($diff / 60, 2)); // minutos
            }

            $data[] = [
                'id' => $orden->id,
                'folio' => $orden->folio,
                'estado' => $orden->estado,
                'total' => $orden->total,
                'fecha_creacion' => $orden->created_at,
                'fecha_entrega_real' => $orden->fechaEntregaReal,
                'tiempo_estimado_minutos' => $orden->duracionTotal,
                'tiempo_real_minutos' => $tiempoReal,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Obtener productividad del técnico
     * GET /api/tecnicos/{id}/productividad
     */
    public function actionProductividad($id): array
    {
        $tecnico = $this->findModel($id);
        $request = Yii::$app->request;
        
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $productividad = $tecnico->getProductividad($fechaDesde, $fechaHasta);
        $horasTrabajadas = $tecnico->getHorasTrabajadas($fechaDesde, $fechaHasta);

        return [
            'success' => true,
            'data' => [
                'ordenes_completadas' => $productividad['ordenes_completadas'],
                'horas_trabajadas' => $horasTrabajadas,
                'horas_trabajadas_formateadas' => sprintf('%d horas', (int)$horasTrabajadas),
                'eficiencia' => $productividad['eficiencia'],
                'calificacion_promedio' => $tecnico->calificacionPromedio,
            ],
        ];
    }

    /**
     * Asignar orden a técnico
     * POST /api/tecnicos/{id}/asignar-orden
     */
    public function actionAsignarOrden($id): array
    {
        $tecnico = $this->findModel($id);
        $request = Yii::$app->request;
        
        $ordenId = $request->post('orden_id');
        
        if (!$ordenId) {
            throw new BadRequestHttpException('orden_id es requerido');
        }

        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new NotFoundHttpException('Orden no encontrada');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $tecnicoAnteriorId = $orden->tecnicoId;
            
            // Registrar auditoría si había un técnico anterior
            if ($tecnicoAnteriorId) {
                $auditoria = new AuditoriaAsignacion();
                $auditoria->orden_servicio_id = $orden->id;
                $auditoria->tecnico_anterior_id = $tecnicoAnteriorId;
                $auditoria->tecnico_nuevo_id = $id;
                $auditoria->usuario_id = Yii::$app->user->id ?? null;
                $auditoria->tipo_cambio = 'reasignacion';
                $auditoria->comentarios = $request->post('comentarios', '');
                $auditoria->save();
            } else {
                $auditoria = new AuditoriaAsignacion();
                $auditoria->orden_servicio_id = $orden->id;
                $auditoria->tecnico_anterior_id = null;
                $auditoria->tecnico_nuevo_id = $id;
                $auditoria->usuario_id = Yii::$app->user->id ?? null;
                $auditoria->tipo_cambio = 'asignacion';
                $auditoria->comentarios = $request->post('comentarios', '');
                $auditoria->save();
            }

            // Asignar técnico a la orden
            $orden->tecnicoId = $id;
            $orden->estado = 'en_progreso';
            
            if (!$orden->save()) {
                throw new \Exception('Error al asignar orden: ' . json_encode($orden->errors));
            }

            $transaction->commit();

            // Aquí se podría enviar notificación al técnico (HU-028)
            // Notificacion::enviarAlTecnico($id, 'Nueva orden asignada', "Se te ha asignado la orden {$orden->folio}");

            return [
                'success' => true,
                'message' => 'Orden asignada correctamente',
                'data' => [
                    'orden_id' => $orden->id,
                    'tecnico_id' => $id,
                ],
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Desasignar técnico de orden
     * POST /api/tecnicos/{id}/desasignar-orden
     */
    public function actionDesasignarOrden($id): array
    {
        $tecnico = $this->findModel($id);
        $request = Yii::$app->request;
        
        $ordenId = $request->post('orden_id');
        
        if (!$ordenId) {
            throw new BadRequestHttpException('orden_id es requerido');
        }

        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new NotFoundHttpException('Orden no encontrada');
        }

        if ($orden->tecnicoId != $id) {
            throw new BadRequestHttpException('La orden no está asignada a este técnico');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // Registrar auditoría
            $auditoria = new AuditoriaAsignacion();
            $auditoria->orden_servicio_id = $orden->id;
            $auditoria->tecnico_anterior_id = $id;
            $auditoria->tecnico_nuevo_id = null;
            $auditoria->usuario_id = Yii::$app->user->id ?? null;
            $auditoria->tipo_cambio = 'desasignacion';
            $auditoria->comentarios = $request->post('comentarios', '');
            $auditoria->save();

            // Desasignar técnico
            $orden->tecnicoId = null;
            $orden->estado = 'abierto';
            
            if (!$orden->save()) {
                throw new \Exception('Error al desasignar orden: ' . json_encode($orden->errors));
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Orden desasignada correctamente',
                'data' => [
                    'orden_id' => $orden->id,
                ],
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Transferir orden entre técnicos
     * POST /api/tecnicos/transferir-orden
     */
    public function actionTransferirOrden(): array
    {
        $request = Yii::$app->request;
        
        $ordenId = $request->post('orden_id');
        $tecnicoOrigenId = $request->post('tecnico_origen_id');
        $tecnicoDestinoId = $request->post('tecnico_destino_id');

        if (!$ordenId || !$tecnicoDestinoId) {
            throw new BadRequestHttpException('orden_id y tecnico_destino_id son requeridos');
        }

        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new NotFoundHttpException('Orden no encontrada');
        }

        $tecnicoDestino = Tecnico::findOne($tecnicoDestinoId);
        if (!$tecnicoDestino) {
            throw new NotFoundHttpException('Técnico destino no encontrado');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // Registrar auditoría
            $auditoria = new AuditoriaAsignacion();
            $auditoria->orden_servicio_id = $orden->id;
            $auditoria->tecnico_anterior_id = $tecnicoOrigenId;
            $auditoria->tecnico_nuevo_id = $tecnicoDestinoId;
            $auditoria->usuario_id = Yii::$app->user->id ?? null;
            $auditoria->tipo_cambio = 'transferencia';
            $auditoria->comentarios = $request->post('comentarios', '');
            $auditoria->save();

            // Transferir orden
            $orden->tecnicoId = $tecnicoDestinoId;
            
            if (!$orden->save()) {
                throw new \Exception('Error al transferir orden: ' . json_encode($orden->errors));
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Orden transferida correctamente',
                'data' => [
                    'orden_id' => $orden->id,
                    'tecnico_anterior_id' => $tecnicoOrigenId,
                    'tecnico_nuevo_id' => $tecnicoDestinoId,
                ],
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Obtener disponibilidad del técnico
     * GET /api/tecnicos/{id}/disponibilidad
     */
    public function actionDisponibilidad($id): array
    {
        $tecnico = $this->findModel($id);

        return [
            'success' => true,
            'data' => [
                'tecnico_id' => $id,
                'activo' => $tecnico->activo,
                'disponible' => $tecnico->disponible,
                'estado' => $tecnico->estado,
                'cantidad_ordenes_activas' => $tecnico->cantidadOrdenesActivas,
            ],
        ];
    }

    /**
     * Obtener carga de trabajo de todos los técnicos
     * GET /api/tecnicos/carga-trabajo
     */
    public function actionCargaTrabajo(): array
    {
        $tecnicos = Tecnico::find()
            ->joinWith(['usuario'])
            ->where(['tecnico.activo' => true])
            ->all();

        $cargaTrabajo = [];
        $imbalance = false;
        $promedio = 0;
        $totalOrdenes = 0;

        foreach ($tecnicos as $tecnico) {
            $cantidad = $tecnico->cantidadOrdenesActivas;
            $totalOrdenes += $cantidad;
            
            $cargaTrabajo[] = [
                'id' => $tecnico->id,
                'nombre' => $tecnico->usuario?->nombre ?? 'Sin nombre',
                'especialidad' => $tecnico->especialidad,
                'cantidad_ordenes' => $cantidad,
                'estado' => $tecnico->estado,
                'disponible' => $tecnico->disponible,
            ];
        }

        if (count($tecnicos) > 0) {
            $promedio = $totalOrdenes / count($tecnicos);
            
            // Detectar imbalance (alguien con más del doble del promedio)
            foreach ($cargaTrabajo as $tecnico) {
                if ($tecnico['cantidad_ordenes'] > $promedio * 2) {
                    $imbalance = true;
                    break;
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'tecnicos' => $cargaTrabajo,
                'promedio_ordenes' => round($promedio, 2),
                'total_ordenes_activas' => $totalOrdenes,
                'hay_imbalance' => $imbalance,
                'alerta' => $imbalance ? 'Existe desbalance en la carga de trabajo' : null,
            ],
        ];
    }

    /**
     * Obtener horas trabajadas por técnico
     * GET /api/tecnicos/{id}/horas-trabajadas
     */
    public function actionHorasTrabajadas($id): array
    {
        $tecnico = $this->findModel($id);
        $request = Yii::$app->request;
        
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $horas = $tecnico->getHorasTrabajadas($fechaDesde, $fechaHasta);

        return [
            'success' => true,
            'data' => [
                'tecnico_id' => $id,
                'horas_trabajadas' => $horas,
                'horas_formateadas' => sprintf('%d horas', (int)$horas),
                'periodo' => [
                    'desde' => $fechaDesde,
                    'hasta' => $fechaHasta,
                ],
            ],
        ];
    }

    /**
     * Obtener calificación del técnico
     * GET /api/tecnicos/{id}/calificacion
     */
    public function actionCalificacion($id): array
    {
        $tecnico = $this->findModel($id);

        return [
            'success' => true,
            'data' => [
                'tecnico_id' => $id,
                'calificacion_promedio' => $tecnico->calificacionPromedio,
            ],
        ];
    }

    /**
     * Obtener ranking de técnicos destacados
     * GET /api/tecnicos/ranking
     */
    public function actionRanking(): array
    {
        $request = Yii::$app->request;
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $limite = (int)$request->get('limite', 10);

        $tecnicos = Tecnico::find()
            ->joinWith(['usuario'])
            ->where(['tecnico.activo' => true])
            ->all();

        $ranking = [];
        foreach ($tecnicos as $tecnico) {
            $productividad = $tecnico->getProductividad($fechaDesde, $fechaHasta);
            
            $ranking[] = [
                'id' => $tecnico->id,
                'nombre' => $tecnico->usuario?->nombre ?? 'Sin nombre',
                'especialidad' => $tecnico->especialidad,
                'ordenes_completadas' => $productividad['ordenes_completadas'],
                'horas_trabajadas' => $productividad['horas_trabajadas'],
                'eficiencia' => $productividad['eficiencia'],
                'calificacion_promedio' => $tecnico->calificacionPromedio,
                'puntaje_total' => round(
                    ($productividad['ordenes_completadas'] * 10) + 
                    ($tecnico->calificacionPromedio * 5) + 
                    ($productividad['eficiencia'] / 10),
                    2
                ),
            ];
        }

        // Ordenar por puntaje total
        usort($ranking, fn($a, $b) => $b['puntaje_total'] <=> $a['puntaje_total']);
        
        // Tomar los primeros N
        $ranking = array_slice($ranking, 0, $limite);

        // Agregar badge de destacado al primero
        if (count($ranking) > 0) {
            $ranking[0]['destacado'] = true;
            $ranking[0]['badge'] = '🏆 Técnico Destacado';
        }

        return [
            'success' => true,
            'data' => [
                'ranking' => $ranking,
                'periodo' => [
                    'desde' => $fechaDesde,
                    'hasta' => $fechaHasta,
                ],
            ],
        ];
    }

    /**
     * Exportar reporte de técnicos
     * GET /api/tecnicos/exportar
     */
    public function actionExportar(): array
    {
        $request = Yii::$app->request;
        $formato = $request->get('formato', 'json');
        
        $tecnicos = Tecnico::find()
            ->joinWith(['usuario'])
            ->all();

        $data = [];
        foreach ($tecnicos as $tecnico) {
            $productividad = $tecnico->getProductividad();
            
            $data[] = [
                'ID' => $tecnico->id,
                'Nombre' => $tecnico->usuario?->nombre ?? '',
                'Email' => $tecnico->usuario?->email ?? '',
                'Teléfono' => $tecnico->usuario?->telefono ?? '',
                'Especialidad' => $tecnico->especialidad,
                'Nivel' => $tecnico->nivel,
                'Activo' => $tecnico->activo ? 'Sí' : 'No',
                'Órdenes Completadas' => $productividad['ordenes_completadas'],
                'Horas Trabajadas' => $productividad['horas_trabajadas'],
                'Eficiencia' => $productividad['eficiencia'],
                'Calificación Promedio' => $tecnico->calificacionPromedio,
            ];
        }

        return [
            'success' => true,
            'formato' => $formato,
            'data' => $data,
            'mensaje' => $formato === 'excel' ? 'Para Excel, usar librería como PhpSpreadsheet' : null,
        ];
    }

    /**
     * Obtener horarios del técnico
     * GET /api/tecnicos/{id}/horarios
     */
    public function actionHorarios($id): array
    {
        $tecnico = $this->findModel($id);
        
        $horarios = HorarioTecnico::find()
            ->where(['tecnicoId' => $id])
            ->orderBy(['dia_semana' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($horarios as $horario) {
            $data[] = [
                'id' => $horario->id,
                'dia_semana' => $horario->dia_semana,
                'nombre_dia' => $horario->nombreDia,
                'hora_entrada' => $horario->hora_entrada,
                'hora_salida' => $horario->hora_salida,
                'activo' => $horario->activo,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Gestionar solicitudes de día libre
     * GET/POST /api/tecnicos/solicitudes-dia-libre
     */
    public function actionSolicitudesDiaLibre(): array
    {
        $request = Yii::$app->request;

        if ($request->isPost) {
            // Crear nueva solicitud
            $solicitud = new SolicitudDiaLibre();
            $solicitud->tecnicoId = $request->post('tecnico_id');
            $solicitud->fecha_solicitud = date('Y-m-d H:i:s');
            $solicitud->fecha_inicio = $request->post('fecha_inicio');
            $solicitud->fecha_fin = $request->post('fecha_fin');
            $solicitud->tipo = $request->post('tipo', 'personal');
            $solicitud->motivo = $request->post('motivo');
            $solicitud->estado = 'pendiente';

            if (!$solicitud->save()) {
                throw new BadRequestHttpException(json_encode($solicitud->errors));
            }

            return [
                'success' => true,
                'message' => 'Solicitud creada correctamente',
                'data' => ['id' => $solicitud->id],
            ];
        }

        // Listar solicitudes
        $tecnicoId = $request->get('tecnico_id');
        $estado = $request->get('estado');

        $query = SolicitudDiaLibre::find()->joinWith(['tecnico.usuario']);

        if ($tecnicoId) {
            $query->andWhere(['solicitud_dia_libre.tecnicoId' => $tecnicoId]);
        }

        if ($estado) {
            $query->andWhere(['estado' => $estado]);
        }

        $solicitudes = $query->orderBy(['fecha_solicitud' => SORT_DESC])->all();

        $data = [];
        foreach ($solicitudes as $solicitud) {
            $data[] = [
                'id' => $solicitud->id,
                'tecnico_id' => $solicitud->tecnicoId,
                'tecnico_nombre' => $solicitud->tecnico?->usuario?->nombre ?? '',
                'fecha_inicio' => $solicitud->fecha_inicio,
                'fecha_fin' => $solicitud->fecha_fin,
                'tipo' => $solicitud->tipo,
                'motivo' => $solicitud->motivo,
                'estado' => $solicitud->estado,
                'fecha_solicitud' => $solicitud->fecha_solicitud,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Obtener auditoría de asignaciones
     * GET /api/tecnicos/auditoria
     */
    public function actionAuditoria(): array
    {
        $request = Yii::$app->request;
        
        $tecnicoId = $request->get('tecnico_id');
        $ordenId = $request->get('orden_id');

        $query = AuditoriaAsignacion::find()
            ->joinWith(['ordenServicio', 'tecnicoAnterior.usuario', 'tecnicoNuevo.usuario', 'usuario']);

        if ($tecnicoId) {
            $query->andWhere([
                'or',
                ['tecnico_anterior_id' => $tecnicoId],
                ['tecnico_nuevo_id' => $tecnicoId],
            ]);
        }

        if ($ordenId) {
            $query->andWhere(['orden_servicio_id' => $ordenId]);
        }

        $auditorias = $query->orderBy(['fecha_cambio' => SORT_DESC])->limit(100)->all();

        $data = [];
        foreach ($auditorias as $auditoria) {
            $data[] = [
                'id' => $auditoria->id,
                'orden_folio' => $auditoria->ordenServicio?->folio ?? '',
                'tecnico_anterior' => $auditoria->tecnicoAnterior?->usuario?->nombre ?? 'N/A',
                'tecnico_nuevo' => $auditoria->tecnicoNuevo?->usuario?->nombre ?? 'N/A',
                'usuario_responsable' => $auditoria->usuario?->nombre ?? 'Sistema',
                'tipo_cambio' => $auditoria->tipo_cambio,
                'comentarios' => $auditoria->comentarios,
                'fecha_cambio' => $auditoria->fecha_cambio,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Tecnico
    {
        $model = Tecnico::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException("El técnico solicitado no existe.");
        }
        return $model;
    }
}
