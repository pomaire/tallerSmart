<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla orden_servicio_historial (HU-020)
 * Registra el historial de cambios de estado de las órdenes de servicio
 */
class OrdenServicioHistorial extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'orden_servicio_historial';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id', 'estado_anterior', 'estado_nuevo'], 'required'],
            [['orden_servicio_id', 'usuario_id'], 'integer'],
            [['estado_anterior', 'estado_nuevo'], 'string', 'max' => 50],
            [['fecha_cambio'], 'safe'],
            [['comentarios'], 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'estado_anterior' => 'Estado Anterior',
            'estado_nuevo' => 'Estado Nuevo',
            'usuario_id' => 'Usuario',
            'fecha_cambio' => 'Fecha de Cambio',
            'comentarios' => 'Comentarios',
        ];
    }

    /**
     * Relación con la orden de servicio
     */
    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }

    /**
     * Relación con el usuario que hizo el cambio
     */
    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Before save - establecer fecha de cambio si no existe
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert && !$this->fecha_cambio) {
                $this->fecha_cambio = new Expression('NOW()');
            }
            return true;
        }
        return false;
    }
}
