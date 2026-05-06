<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla CitaServicio
 */
class CitaServicio extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'cita_servicio';
    }

    public function rules(): array
    {
        return [
            [['cita_id', 'servicio_id'], 'required'],
            [['cita_id', 'servicio_id', 'cantidad'], 'integer'],
            [['precio_unitario'], 'number'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cita_id' => 'Cita',
            'servicio_id' => 'Servicio',
            'cantidad' => 'Cantidad',
            'precio_unitario' => 'Precio Unitario',
        ];
    }

    public function getCita(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cita::class, ['id' => 'cita_id']);
    }

    public function getServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }
}
