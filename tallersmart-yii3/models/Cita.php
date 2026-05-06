<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla Cita
 */
class Cita extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'cita';
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'vehiculo_id', 'fecha_hora'], 'required'],
            [['cliente_id', 'vehiculo_id', 'created_by'], 'integer'],
            [['fecha_hora'], 'safe'],
            [['estado'], 'string', 'max' => 50],
            [['notas'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cliente_id' => 'Cliente',
            'vehiculo_id' => 'Vehículo',
            'fecha_hora' => 'Fecha y Hora',
            'estado' => 'Estado',
            'notas' => 'Notas',
            'created_by' => 'Creado por',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVehiculo(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Vehiculo::class, ['id' => 'vehiculo_id']);
    }

    public function getCitaServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CitaServicio::class, ['cita_id' => 'id']);
    }

    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['cita_id' => 'id']);
    }
}
