<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla Vehiculo
 */
class Vehiculo extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'vehiculo';
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'marca', 'modelo', 'placa'], 'required'],
            [['cliente_id', 'year', 'kilometraje'], 'integer'],
            [['marca', 'modelo', 'color', 'motor', 'placa', 'vin'], 'string', 'max' => 100],
            [['notas'], 'string'],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cliente_id' => 'Cliente',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'year' => 'Año',
            'placa' => 'Placa',
            'vin' => 'VIN',
            'color' => 'Color',
            'kilometraje' => 'Kilometraje',
            'motor' => 'Motor',
            'notas' => 'Notas',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getCitas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Cita::class, ['vehiculo_id' => 'id']);
    }

    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['vehiculo_id' => 'id']);
    }
}
