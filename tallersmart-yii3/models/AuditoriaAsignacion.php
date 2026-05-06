<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla auditoria_asignacion
 */
class AuditoriaAsignacion extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'auditoria_asignacion';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id', 'tecnico_anterior_id', 'tecnico_nuevo_id', 'usuario_id'], 'required'],
            [['orden_servicio_id', 'tecnico_anterior_id', 'tecnico_nuevo_id', 'usuario_id'], 'integer'],
            [['tipo_cambio'], 'string', 'max' => 50], // asignacion, desasignacion, transferencia
            [['comentarios'], 'string'],
            [['fecha_cambio'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'tecnico_anterior_id' => 'Técnico Anterior',
            'tecnico_nuevo_id' => 'Técnico Nuevo',
            'usuario_id' => 'Usuario que realizó el cambio',
            'tipo_cambio' => 'Tipo de Cambio',
            'comentarios' => 'Comentarios',
            'fecha_cambio' => 'Fecha del Cambio',
        ];
    }

    /**
     * Relación con OrdenServicio
     */
    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }

    /**
     * Relación con Técnico anterior
     */
    public function getTecnicoAnterior(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnico_anterior_id']);
    }

    /**
     * Relación con Técnico nuevo
     */
    public function getTecnicoNuevo(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnico_nuevo_id']);
    }

    /**
     * Relación con Usuario
     */
    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Before save - establecer fecha
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->fecha_cambio = new Expression('NOW()');
            }
            return true;
        }
        return false;
    }
}
