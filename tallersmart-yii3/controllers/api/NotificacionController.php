<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Notificacion;
use app\models\PreferenciasNotificacion;
use app\models\Usuario;
use Yii;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Controller para gestión de notificaciones
 */
class NotificacionController extends BaseController
{
    /**
     * Obtener lista de notificaciones del usuario autenticado
     * HU-002, HU-017, HU-022
     */
    public function actionIndex(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        
        $filtros = [
            'tipo' => Yii::$app->request->get('tipo', ''),
            'estado' => Yii::$app->request->get('estado', ''),
            'prioridad' => Yii::$app->request->get('prioridad', ''),
            'busqueda' => Yii::$app->request->get('busqueda', ''),
        ];
        
        $pagina = (int)Yii::$app->request->get('pagina', 1);
        $porPagina = (int)Yii::$app->request->get('por_pagina', 20);
        
        $resultado = Notificacion::obtenerHistorial($usuarioId, $pagina, $porPagina, $filtros);
        
        // Calcular estadísticas
        $stats = [
            'total' => Notificacion::find()->where(['usuario_id' => $usuarioId])->count(),
            'noLeidas' => Notificacion::contarNoLeidas($usuarioId),
            'citas' => Notificacion::find()->where(['usuario_id' => $usuarioId, 'tipo' => 'cita'])->count(),
            'ordenes' => Notificacion::find()->where(['usuario_id' => $usuarioId, 'tipo' => 'orden'])->count(),
            'sistema' => Notificacion::find()->where(['usuario_id' => $usuarioId, 'tipo' => 'sistema'])->count(),
        ];
        
        return [
            'items' => array_map(fn($n) => $this->transformarNotificacion($n), $resultado['items']),
            'total' => $resultado['total'],
            'totalPages' => $resultado['totalPages'],
            'pagina' => $resultado['pagina'],
            'stats' => $stats,
        ];
    }
    
    /**
     * Ver detalle de una notificación
     * HU-002
     */
    public function actionView(int $id): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $notificacion = Notificacion::findOne(['id' => $id, 'usuario_id' => $usuarioId]);
        
        if (!$notificacion) {
            throw new NotFoundHttpException('Notificación no encontrada');
        }
        
        // Marcar como leída automáticamente al ver
        $notificacion->marcarComoLeida();
        
        return $this->transformarNotificacion($notificacion);
    }
    
    /**
     * Marcar notificación como leída
     * HU-003
     */
    public function actionMarcarLeida(int $id): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $notificacion = Notificacion::findOne(['id' => $id, 'usuario_id' => $usuarioId]);
        
        if (!$notificacion) {
            throw new NotFoundHttpException('Notificación no encontrada');
        }
        
        $notificacion->marcarComoLeida();
        
        return ['success' => true, 'message' => 'Notificación marcada como leída'];
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     * HU-004
     */
    public function actionMarcarTodasLeidas(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $tipo = Yii::$app->request->get('tipo');
        
        $cantidad = Notificacion::marcarTodasLeidas($usuarioId, $tipo);
        
        return [
            'success' => true, 
            'message' => "{$cantidad} notificaciones marcadas como leídas",
            'cantidad' => $cantidad,
        ];
    }
    
    /**
     * Eliminar notificación
     * HU-005
     */
    public function actionEliminar(int $id): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        
        if (!Notificacion::eliminarParaUsuario($id, $usuarioId)) {
            throw new NotFoundHttpException('Notificación no encontrada');
        }
        
        return ['success' => true, 'message' => 'Notificación eliminada'];
    }
    
    /**
     * Obtener contador de no leídas (badge)
     * HU-016
     */
    public function actionContador(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        
        return [
            'noLeidas' => Notificacion::contarNoLeidas($usuarioId),
            'total' => Notificacion::find()->where(['usuario_id' => $usuarioId])->count(),
        ];
    }
    
    /**
     * Obtener preferencias de notificación
     * HU-015
     */
    public function actionPreferencias(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $preferencias = PreferenciasNotificacion::obtenerParaUsuario($usuarioId);
        
        return [
            'tipos' => [
                'cita_nueva' => $preferencias->notificar_cita_nueva,
                'cita_confirmada' => $preferencias->notificar_cita_confirmada,
                'cita_reprogramada' => $preferencias->notificar_cita_reprogramada,
                'no_show' => $preferencias->notificar_no_show,
                'recordatorio' => $preferencias->notificar_recordatorio,
                'orden_asignada' => $preferencias->notificar_orden_asignada,
                'orden_lista' => $preferencias->notificar_orden_lista,
                'cambio_estado' => $preferencias->notificar_cambio_estado,
                'stock_bajo' => $preferencias->notificar_stock_bajo,
                'expiry' => $preferencias->notificar_expiry,
                'pago_recibido' => $preferencias->notificar_pago_recibido,
                'nuevo_usuario' => $preferencias->notificar_nuevo_usuario,
            ],
            'canales' => [
                'plataforma' => $preferencias->canal_plataforma,
                'email' => $preferencias->canal_email,
                'push' => $preferencias->canal_push,
            ],
            'silencio' => [
                'activo' => $preferencias->estaEnSilencio(),
                'hasta' => $preferencias->silenciar_hasta,
            ],
        ];
    }
    
    /**
     * Actualizar preferencias de notificación
     * HU-015
     */
    public function actionActualizarPreferencias(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $preferencias = PreferenciasNotificacion::obtenerParaUsuario($usuarioId);
        
        $datos = Yii::$app->request->post();
        
        if ($preferencias->actualizarPreferencias($datos)) {
            return ['success' => true, 'message' => 'Preferencias actualizadas'];
        }
        
        return ['success' => false, 'errors' => $preferencias->getErrors()];
    }
    
    /**
     * Activar modo silencio
     * HU-028
     */
    public function actionActivarSilencio(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $preferencias = PreferenciasNotificacion::obtenerParaUsuario($usuarioId);
        
        $horas = (int)Yii::$app->request->post('horas', 8);
        
        if ($preferencias->activarSilencio($horas)) {
            return [
                'success' => true, 
                'message' => "Notificaciones silenciadas por {$horas} horas",
                'hasta' => $preferencias->silenciar_hasta,
            ];
        }
        
        return ['success' => false, 'errors' => $preferencias->getErrors()];
    }
    
    /**
     * Desactivar modo silencio
     * HU-028
     */
    public function actionDesactivarSilencio(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        $preferencias = PreferenciasNotificacion::obtenerParaUsuario($usuarioId);
        
        if ($preferencias->desactivarSilencio()) {
            return ['success' => true, 'message' => 'Notificaciones reactivadas'];
        }
        
        return ['success' => false, 'errors' => $preferencias->getErrors()];
    }
    
    /**
     * Ejecutar acción rápida desde notificación
     * HU-026
     */
    public function actionAccionRapida(): array
    {
        $usuarioId = $this->getUsuarioAutenticado()->id;
        
        $notificacionId = (int)Yii::$app->request->post('notificacion_id');
        $accion = Yii::$app->request->post('accion');
        
        // Verificar que la notificación pertenece al usuario
        $notificacion = Notificacion::findOne(['id' => $notificacionId, 'usuario_id' => $usuarioId]);
        if (!$notificacion) {
            throw new NotFoundHttpException('Notificación no encontrada');
        }
        
        if (\app\models\NotificacionService::ejecutarAccionRapida($notificacionId, $accion)) {
            return ['success' => true, 'message' => 'Acción ejecutada correctamente'];
        }
        
        return ['success' => false, 'message' => 'No se pudo ejecutar la acción'];
    }
    
    /**
     * Transformar notificación para respuesta API
     */
    private function transformarNotificacion(Notificacion $notif): array
    {
        return [
            'id' => $notif->id,
            'titulo' => $notif->titulo,
            'mensaje' => $notif->mensaje,
            'tipo' => $notif->tipo,
            'tipoLabel' => Notificacion::getTipos()[$notif->tipo] ?? $notif->tipo,
            'icono' => Notificacion::getIconoPorTipo($notif->tipo),
            'color' => Notificacion::getColorPorTipo($notif->tipo),
            'prioridad' => $notif->prioridad,
            'prioridadLabel' => Notificacion::getPrioridades()[$notif->prioridad] ?? $notif->prioridad,
            'leida' => $notif->leida === Notificacion::ESTADO_LEIDA,
            'fecha' => $notif->created_at,
            'leidaEn' => $notif->leida_en,
            'entidadId' => $notif->entidad_id,
            'entidadTipo' => $notif->entidad_tipo,
            'urlRelacionada' => $notif->url_relacionada,
            'emailEnviado' => $notif->email_enviado,
            'pushEnviado' => $notif->push_enviado,
        ];
    }
}
