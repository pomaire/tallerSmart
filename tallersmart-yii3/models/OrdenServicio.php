<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla OrdenServicio
 */
class OrdenServicio extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'orden_servicio';
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'vehiculo_id'], 'required'],
            [['cita_id', 'cliente_id', 'vehiculo_id', 'created_by', 'finalizada_por', 'kilometraje'], 'integer'],
            [['numero_orden'], 'string', 'max' => 50],
            [['estado'], 'string', 'max' => 50],
            [['descripcion_problema', 'diagnostico', 'notas_internas'], 'string'],
            [['total'], 'number'],
            [['fecha_hora', 'finalizada_en', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cita_id' => 'Cita',
            'cliente_id' => 'Cliente',
            'vehiculo_id' => 'Vehículo',
            'numero_orden' => 'Número de Orden',
            'estado' => 'Estado',
            'descripcion_problema' => 'Descripción del Problema',
            'diagnostico' => 'Diagnóstico',
            'notas_internas' => 'Notas Internas',
            'kilometraje' => 'Kilometraje',
            'total' => 'Total',
            'finalizada_en' => 'Finalizada en',
            'finalizada_por' => 'Finalizada por',
            'created_by' => 'Creado por',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getCita(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cita::class, ['id' => 'cita_id']);
    }

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVehiculo(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Vehiculo::class, ['id' => 'vehiculo_id']);
    }

    public function getDetalles(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioDetalle::class, ['orden_servicio_id' => 'id']);
    }
}
