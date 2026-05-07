<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla preferencias_notificacion
 * HU-015: Preferencias de notificación por usuario
 */
class PreferenciasNotificacion extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'preferencias_notificacion';
    }

    public function rules(): array
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id'], 'integer', 'unique' => true],
            [['notificar_cita_nueva', 'notificar_cita_confirmada', 'notificar_cita_reprogramada', 
              'notificar_no_show', 'notificar_recordatorio', 'notificar_orden_asignada', 
              'notificar_orden_lista', 'notificar_cambio_estado', 'notificar_stock_bajo', 
              'notificar_expiry', 'notificar_pago_recibido', 'notificar_nuevo_usuario'], 'boolean'],
            [['canal_plataforma', 'canal_email', 'canal_push'], 'boolean'],
            [['silenciar_desde', 'silenciar_hasta'], 'datetime'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario',
            'notificar_cita_nueva' => 'Nueva Cita',
            'notificar_cita_confirmada' => 'Cita Confirmada',
            'notificar_cita_reprogramada' => 'Cita Reprogramada',
            'notificar_no_show' => 'No Show',
            'notificar_recordatorio' => 'Recordatorio',
            'notificar_orden_asignada' => 'Orden Asignada',
            'notificar_orden_lista' => 'Orden Lista',
            'notificar_cambio_estado' => 'Cambio Estado Orden',
            'notificar_stock_bajo' => 'Stock Bajo',
            'notificar_expiry' => 'Expiry Productos',
            'notificar_pago_recibido' => 'Pago Recibido',
            'notificar_nuevo_usuario' => 'Nuevo Usuario',
            'canal_plataforma' => 'Notificaciones en Plataforma',
            'canal_email' => 'Email',
            'canal_push' => 'Push Notifications',
            'silenciar_desde' => 'Silenciar Desde',
            'silenciar_hasta' => 'Silenciar Hasta',
            'created_at' => 'Creado En',
            'updated_at' => 'Actualizado En',
        ];
    }
    
    /**
     * HU-015: Obtener preferencias por defecto
     */
    public static function getPreferenciasPorDefecto(): array
    {
        return [
            'notificar_cita_nueva' => true,
            'notificar_cita_confirmada' => true,
            'notificar_cita_reprogramada' => true,
            'notificar_no_show' => true,
            'notificar_recordatorio' => true,
            'notificar_orden_asignada' => true,
            'notificar_orden_lista' => true,
            'notificar_cambio_estado' => true,
            'notificar_stock_bajo' => true,
            'notificar_expiry' => true,
            'notificar_pago_recibido' => true,
            'notificar_nuevo_usuario' => false,
            'canal_plataforma' => true,
            'canal_email' => false,
            'canal_push' => true,
        ];
    }
    
    /**
     * HU-015: Obtener o crear preferencias para usuario
     */
    public static function obtenerParaUsuario(int $usuarioId): self
    {
        $preferencias = self::findOne(['usuario_id' => $usuarioId]);
        
        if (!$preferencias) {
            $preferencias = new self();
            $preferencias->usuario_id = $usuarioId;
            $defaults = self::getPreferenciasPorDefecto();
            foreach ($defaults as $key => $value) {
                $preferencias->$key = $value;
            }
            $preferencias->save(false);
        }
        
        return $preferencias;
    }
    
    /**
     * HU-015: Verificar si debe recibir notificación de un tipo
     */
    public function debeRecibir(string $tipo): bool
    {
        $mapeoTipos = [
            'cita_nueva' => 'notificar_cita_nueva',
            'cita_confirmada' => 'notificar_cita_confirmada',
            'cita_reprogramada' => 'notificar_cita_reprogramada',
            'no_show' => 'notificar_no_show',
            'recordatorio' => 'notificar_recordatorio',
            'orden_asignada' => 'notificar_orden_asignada',
            'orden_lista' => 'notificar_orden_lista',
            'cambio_estado' => 'notificar_cambio_estado',
            'stock_bajo' => 'notificar_stock_bajo',
            'expiry' => 'notificar_expiry',
            'pago_recibido' => 'notificar_pago_recibido',
            'nuevo_usuario' => 'notificar_nuevo_usuario',
        ];
        
        $campo = $mapeoTipos[$tipo] ?? null;
        if (!$campo) {
            return true; // Por defecto permitir
        }
        
        return $this->$campo === true;
    }
    
    /**
     * HU-028: Verificar si el usuario está en modo silencio
     */
    public function estaEnSilencio(): bool
    {
        if ($this->silenciar_hasta === null) {
            return false;
        }
        
        $ahora = new \DateTime();
        $hasta = new \DateTime($this->silenciar_hasta);
        
        return $ahora < $hasta;
    }
    
    /**
     * HU-028: Activar modo silencio por X horas
     */
    public function activarSilencio(int $horas): bool
    {
        $this->silenciar_desde = new Expression('NOW()');
        $hasta = new \DateTime();
        $hasta->modify("+{$horas} hours");
        $this->silenciar_hasta = $hasta->format('Y-m-d H:i:s');
        
        return $this->save(false);
    }
    
    /**
     * HU-028: Desactivar modo silencio
     */
    public function desactivarSilencio(): bool
    {
        $this->silenciar_desde = null;
        $this->silenciar_hasta = null;
        
        return $this->save(false);
    }
    
    /**
     * HU-015: Actualizar preferencias desde formulario
     */
    public function actualizarPreferencias(array $datos): bool
    {
        $camposPermitidos = [
            'notificar_cita_nueva', 'notificar_cita_confirmada', 'notificar_cita_reprogramada',
            'notificar_no_show', 'notificar_recordatorio', 'notificar_orden_asignada',
            'notificar_orden_lista', 'notificar_cambio_estado', 'notificar_stock_bajo',
            'notificar_expiry', 'notificar_pago_recibido', 'notificar_nuevo_usuario',
            'canal_plataforma', 'canal_email', 'canal_push',
        ];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($datos[$campo])) {
                $this->$campo = (bool)$datos[$campo];
            }
        }
        
        return $this->save(false);
    }
    
    /**
     * Before save - actualizar timestamps
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new Expression('NOW()');
            }
            $this->updated_at = new Expression('NOW()');
            return true;
        }
        return false;
    }
    
    /**
     * Relación con Usuario
     */
    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
