<?php

declare(strict_types=1);

namespace app\models;

use yii\mail\MailerInterface;

/**
 * Servicio para gestión de notificaciones
 * Soporta envío de emails, push notifications y plantillas
 */
class NotificacionService
{
    // HU-030: Plantillas predefinidas
    private const PLANTILLAS = [
        'nueva_cita' => [
            'titulo' => 'Nueva cita creada',
            'mensaje' => 'Nueva cita: {cliente}, Fecha {fecha}',
            'tipo' => Notificacion::TIPO_CITA,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
        'cita_confirmada' => [
            'titulo' => 'Cita confirmada',
            'mensaje' => 'Cita confirmada: {cliente}',
            'tipo' => Notificacion::TIPO_CITA,
            'prioridad' => Notificacion::PRIORIDAD_BAJA,
        ],
        'cita_reprogramada' => [
            'titulo' => 'Cita reprogramada',
            'mensaje' => 'Cita reprogramada: Nueva fecha {nueva_fecha}',
            'tipo' => Notificacion::TIPO_CITA,
            'prioridad' => Notificacion::PRIORIDAD_ALTA,
        ],
        'no_show' => [
            'titulo' => 'Cliente no se presentó',
            'mensaje' => 'Cliente no se presentó: Fecha {fecha}',
            'tipo' => Notificacion::TIPO_WARNING,
            'prioridad' => Notificacion::PRIORIDAD_ALTA,
        ],
        'orden_lista' => [
            'titulo' => 'Orden lista para entrega',
            'mensaje' => 'Orden {folio} lista para entrega',
            'tipo' => Notificacion::TIPO_SUCCESS,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
        'orden_asignada' => [
            'titulo' => 'Nueva orden asignada',
            'mensaje' => 'Nueva orden asignada: {folio}',
            'tipo' => Notificacion::TIPO_ORDEN,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
        'orden_cambio_estado' => [
            'titulo' => 'Cambio de estado de orden',
            'mensaje' => 'Orden {folio}: {estado_anterior} → {estado_nuevo}',
            'tipo' => Notificacion::TIPO_ORDEN,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
        'stock_bajo' => [
            'titulo' => 'Stock bajo',
            'mensaje' => 'Stock bajo: {producto}',
            'tipo' => Notificacion::TIPO_WARNING,
            'prioridad' => Notificacion::PRIORIDAD_ALTA,
        ],
        'esperando_repuestos' => [
            'titulo' => 'Orden esperando repuestos',
            'mensaje' => 'Orden {folio} esperando repuestos',
            'tipo' => Notificacion::TIPO_INVENTARIO,
            'prioridad' => Notificacion::PRIORIDAD_ALTA,
        ],
        'pago_recibido' => [
            'titulo' => 'Pago recibido',
            'mensaje' => 'Pago recibido: ${monto}',
            'tipo' => Notificacion::TIPO_SUCCESS,
            'prioridad' => Notificacion::PRIORIDAD_BAJA,
        ],
        'expiry' => [
            'titulo' => 'Producto por vencer',
            'mensaje' => 'Producto {producto} expira en {dias} días',
            'tipo' => Notificacion::TIPO_WARNING,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
        'nuevo_usuario' => [
            'titulo' => 'Nuevo usuario registrado',
            'mensaje' => 'Nuevo usuario registrado: {email}',
            'tipo' => Notificacion::TIPO_SISTEMA,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
        'recordatorio_cita' => [
            'titulo' => 'Recordatorio de cita',
            'mensaje' => 'Cita mañana: {cliente}, Hora {hora}',
            'tipo' => Notificacion::TIPO_CITA,
            'prioridad' => Notificacion::PRIORIDAD_MEDIA,
        ],
    ];
    
    /**
     * HU-030: Obtener plantilla por nombre
     */
    public static function getPlantilla(string $nombre): ?array
    {
        return self::PLANTILLAS[$nombre] ?? null;
    }
    
    /**
     * HU-030: Crear notificación usando plantilla
     * @param string $nombrePlantilla Nombre de la plantilla
     * @param int $usuarioId ID del usuario destinatario
     * @param array $datos Datos para reemplazar placeholders
     * @param bool $enviarEmail Si debe enviar email
     * @param bool $enviarPush Si debe enviar push
     * @return Notificacion|false
     */
    public static function crearConPlantilla(
        string $nombrePlantilla,
        int $usuarioId,
        array $datos = [],
        bool $enviarEmail = false,
        bool $enviarPush = false
    ): Notificacion|false {
        $plantilla = self::getPlantilla($nombrePlantilla);
        
        if (!$plantilla) {
            return false;
        }
        
        // Reemplazar placeholders
        $titulo = self::reemplazarPlaceholders($plantilla['titulo'], $datos);
        $mensaje = self::reemplazarPlaceholders($plantilla['mensaje'], $datos);
        
        return Notificacion::crear(
            usuarioId: $usuarioId,
            titulo: $titulo,
            mensaje: $mensaje,
            tipo: $plantilla['tipo'],
            prioridad: $plantilla['prioridad'],
            enviarEmail: $enviarEmail,
            enviarPush: $enviarPush
        );
    }
    
    /**
     * HU-030: Reemplazar placeholders en texto
     */
    private static function reemplazarPlaceholders(string $texto, array $datos): string
    {
        foreach ($datos as $clave => $valor) {
            $texto = str_replace('{' . $clave . '}', (string)$valor, $texto);
        }
        return $texto;
    }
    
    /**
     * HU-006: Notificar nueva cita a usuarios afectados
     */
    public static function notificarNuevaCita(Cita $cita, array $usuariosIds): array
    {
        $notificaciones = [];
        $cliente = $cita->cliente;
        $nombreCliente = $cliente ? $cliente->nombre : 'Desconocido';
        
        foreach ($usuariosIds as $usuarioId) {
            $notif = self::crearConPlantilla(
                'nueva_cita',
                $usuarioId,
                [
                    'cliente' => $nombreCliente,
                    'fecha' => $cita->fecha_hora,
                ]
            );
            if ($notif) {
                $notif->entidad_id = $cita->id;
                $notif->entidad_tipo = 'Cita';
                $notif->url_relacionada = '/citas/view/' . $cita->id;
                $notif->save(false);
                $notificaciones[] = $notif;
            }
        }
        
        return $notificaciones;
    }
    
    /**
     * HU-008: Notificar cita confirmada
     */
    public static function notificarCitaConfirmada(Cita $cita, int $usuarioId): ?Notificacion
    {
        $cliente = $cita->cliente;
        $nombreCliente = $cliente ? $cliente->nombre : 'Desconocido';
        
        $notif = self::crearConPlantilla(
            'cita_confirmada',
            $usuarioId,
            ['cliente' => $nombreCliente]
        );
        
        if ($notif) {
            $notif->entidad_id = $cita->id;
            $notif->entidad_tipo = 'Cita';
            $notif->url_relacionada = '/citas/view/' . $cita->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-018: Notificar reprogramación de cita
     */
    public static function notificarCitaReprogramada(Cita $cita, array $usuariosIds, string $nuevaFecha): array
    {
        $notificaciones = [];
        
        foreach ($usuariosIds as $usuarioId) {
            $notif = self::crearConPlantilla(
                'cita_reprogramada',
                $usuarioId,
                ['nueva_fecha' => $nuevaFecha]
            );
            if ($notif) {
                $notif->entidad_id = $cita->id;
                $notif->entidad_tipo = 'Cita';
                $notif->url_relacionada = '/citas/view/' . $cita->id;
                $notif->save(false);
                $notificaciones[] = $notif;
            }
        }
        
        return $notificaciones;
    }
    
    /**
     * HU-019: Notificar no-show
     */
    public static function notificarNoShow(Cita $cita, array $adminIds): array
    {
        $notificaciones = [];
        
        foreach ($adminIds as $usuarioId) {
            $notif = self::crearConPlantilla(
                'no_show',
                $usuarioId,
                ['fecha' => $cita->fecha_hora]
            );
            if ($notif) {
                $notif->entidad_id = $cita->id;
                $notif->entidad_tipo = 'Cita';
                $notif->url_relacionada = '/citas/view/' . $cita->id;
                $notif->save(false);
                $notificaciones[] = $notif;
            }
        }
        
        return $notificaciones;
    }
    
    /**
     * HU-025: Notificar recordatorio de cita (24 horas antes)
     */
    public static function notificarRecordatorioCita(Cita $cita, int $usuarioId): ?Notificacion
    {
        $cliente = $cita->cliente;
        $nombreCliente = $cliente ? $cliente->nombre : 'Desconocido';
        
        $notif = self::crearConPlantilla(
            'recordatorio_cita',
            $usuarioId,
            [
                'cliente' => $nombreCliente,
                'hora' => date('H:i', strtotime($cita->fecha_hora)),
            ]
        );
        
        if ($notif) {
            $notif->entidad_id = $cita->id;
            $notif->entidad_tipo = 'Cita';
            $notif->url_relacionada = '/citas/view/' . $cita->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-009: Notificar orden lista para entrega
     */
    public static function notificarOrdenLista(OrdenServicio $orden, int $usuarioId): ?Notificacion
    {
        $notif = self::crearConPlantilla(
            'orden_lista',
            $usuarioId,
            ['folio' => $orden->folio]
        );
        
        if ($notif) {
            $notif->entidad_id = $orden->id;
            $notif->entidad_tipo = 'OrdenServicio';
            $notif->url_relacionada = '/ordenes/view/' . $orden->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-010: Notificar asignación de técnico a orden
     */
    public static function notificarAsignacionTecnico(OrdenServicio $orden, int $tecnicoUsuarioId): ?Notificacion
    {
        $notif = self::crearConPlantilla(
            'orden_asignada',
            $tecnicoUsuarioId,
            ['folio' => $orden->folio]
        );
        
        if ($notif) {
            $notif->entidad_id = $orden->id;
            $notif->entidad_tipo = 'OrdenServicio';
            $notif->url_relacionada = '/ordenes/view/' . $orden->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-023: Notificar cambio de estado de orden
     */
    public static function notificarCambioEstadoOrden(OrdenServicio $orden, int $usuarioId, string $estadoAnterior, string $estadoNuevo): ?Notificacion
    {
        $notif = self::crearConPlantilla(
            'orden_cambio_estado',
            $usuarioId,
            [
                'folio' => $orden->folio,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
            ]
        );
        
        if ($notif) {
            $notif->entidad_id = $orden->id;
            $notif->entidad_tipo = 'OrdenServicio';
            $notif->url_relacionada = '/ordenes/view/' . $orden->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-020: Notificar orden esperando repuestos
     */
    public static function notificarEsperandoRepuestos(OrdenServicio $orden, array $comprasIds): array
    {
        $notificaciones = [];
        
        foreach ($comprasIds as $usuarioId) {
            $notif = self::crearConPlantilla(
                'esperando_repuestos',
                $usuarioId,
                ['folio' => $orden->folio]
            );
            if ($notif) {
                $notif->entidad_id = $orden->id;
                $notif->entidad_tipo = 'OrdenServicio';
                $notif->url_relacionada = '/ordenes/view/' . $orden->id;
                $notif->save(false);
                $notificaciones[] = $notif;
            }
        }
        
        return $notificaciones;
    }
    
    /**
     * HU-007: Notificar stock bajo
     */
    public static function notificarStockBajo(InventoryItem $item, array $inventarioIds): array
    {
        $notificaciones = [];
        
        foreach ($inventarioIds as $usuarioId) {
            $notif = self::crearConPlantilla(
                'stock_bajo',
                $usuarioId,
                ['producto' => $item->nombre]
            );
            if ($notif) {
                $notif->entidad_id = $item->id;
                $notif->entidad_tipo = 'InventoryItem';
                $notif->url_relacionada = '/inventario/view/' . $item->id;
                $notif->save(false);
                $notificaciones[] = $notif;
            }
        }
        
        return $notificaciones;
    }
    
    /**
     * HU-011: Notificar pago recibido
     */
    public static function notificarPagoRecibido(Pago $pago, int $usuarioId): ?Notificacion
    {
        $notif = self::crearConPlantilla(
            'pago_recibido',
            $usuarioId,
            ['monto' => number_format($pago->monto, 0, ',', '.')]
        );
        
        if ($notif) {
            $notif->entidad_id = $pago->id;
            $notif->entidad_tipo = 'Pago';
            $notif->url_relacionada = '/pagos/view/' . $pago->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-027: Notificar nuevo usuario registrado
     */
    public static function notificarNuevoUsuario(Usuario $usuario, array $adminIds): array
    {
        $notificaciones = [];
        
        foreach ($adminIds as $usuarioId) {
            $notif = self::crearConPlantilla(
                'nuevo_usuario',
                $usuarioId,
                ['email' => $usuario->email]
            );
            if ($notif) {
                $notif->entidad_id = $usuario->id;
                $notif->entidad_tipo = 'Usuario';
                $notif->url_relacionada = '/usuarios/view/' . $usuario->id;
                $notif->save(false);
                $notificaciones[] = $notif;
            }
        }
        
        return $notificaciones;
    }
    
    /**
     * HU-029: Notificar expiry de producto
     */
    public static function notificarExpiry(InventoryItem $item, int $diasParaVencer, int $usuarioId): ?Notificacion
    {
        $notif = self::crearConPlantilla(
            'expiry',
            $usuarioId,
            [
                'producto' => $item->nombre,
                'dias' => $diasParaVencer,
            ]
        );
        
        if ($notif) {
            $notif->entidad_id = $item->id;
            $notif->entidad_tipo = 'InventoryItem';
            $notif->url_relacionada = '/inventario/view/' . $item->id;
            $notif->save(false);
        }
        
        return $notif;
    }
    
    /**
     * HU-014: Enviar email de notificación
     */
    public static function enviarEmail(Notificacion $notificacion): bool
    {
        // Implementación simplificada - en producción usar Yii mailer
        $usuario = $notificacion->usuario;
        if (!$usuario || !$usuario->email) {
            return false;
        }
        
        // Simular envío de email
        // En producción: Yii::$app->mailer->compose()->setTo($usuario->email)->setSubject($notificacion->titulo)->setTextBody($notificacion->mensaje)->send();
        
        $notificacion->marcarEmailEnviado();
        return true;
    }
    
    /**
     * HU-021: Enviar push notification
     */
    public static function enviarPush(Notificacion $notificacion): bool
    {
        // Implementación simplificada - en producción usar servicio de push (Firebase, OneSignal, etc.)
        
        // Simular envío de push
        $notificacion->marcarPushEnviado();
        return true;
    }
    
    /**
     * HU-026: Ejecutar acción rápida desde notificación
     */
    public static function ejecutarAccionRapida(int $notificacionId, string $accion, array $datos = []): bool
    {
        $notificacion = Notificacion::findOne($notificacionId);
        if (!$notificacion) {
            return false;
        }
        
        switch ($accion) {
            case 'confirmar_cita':
                if ($notificacion->entidad_tipo === 'Cita') {
                    $cita = Cita::findOne($notificacion->entidad_id);
                    if ($cita) {
                        $cita->estado = Cita::ESTADO_CONFIRMADA;
                        return $cita->save(false);
                    }
                }
                break;
                
            case 'cancelar_cita':
                if ($notificacion->entidad_tipo === 'Cita') {
                    $cita = Cita::findOne($notificacion->entidad_id);
                    if ($cita) {
                        $cita->estado = Cita::ESTADO_CANCELADA;
                        return $cita->save(false);
                    }
                }
                break;
                
            case 'aprobar_orden':
                if ($notificacion->entidad_tipo === 'OrdenServicio') {
                    $orden = OrdenServicio::findOne($notificacion->entidad_id);
                    if ($orden) {
                        $orden->estado = OrdenServicio::ESTADO_EN_PROGRESO;
                        return $orden->save(false);
                    }
                }
                break;
                
            case 'marcar_leida':
                return $notificacion->marcarComoLeida();
        }
        
        return false;
    }
}
