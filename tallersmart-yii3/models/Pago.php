<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla Pago
 */
class Pago extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'pago';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id', 'monto'], 'required'],
            [['orden_servicio_id', 'created_by'], 'integer'],
            [['monto'], 'number'],
            [['metodo_pago'], 'string', 'max' => 50],
            [['referencia'], 'string', 'max' => 100],
            [['notas'], 'string'],
            [['fecha_pago', 'created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'monto' => 'Monto',
            'metodo_pago' => 'Método de Pago',
            'referencia' => 'Referencia',
            'notas' => 'Notas',
            'fecha_pago' => 'Fecha de Pago',
            'created_by' => 'Creado por',
            'created_at' => 'Creado en',
        ];
    }

    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }
}
