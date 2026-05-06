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

    /**
     * Verifica si la orden está finalizada/cerrada
     */
    public function getEstaFinalizada(): bool
    {
        return $this->estado === 'finalizada' || $this->estado === 'facturada';
    }

    /**
     * Verifica si la orden está facturada
     */
    public function getEstaFacturada(): bool
    {
        return $this->estado === 'facturada';
    }

    /**
     * Calcula la duración total estimada de todos los servicios en la orden
     */
    public function getDuracionTotal(): int
    {
        $duracionTotal = 0;
        foreach ($this->detalles as $detalle) {
            if ($detalle->servicio && $detalle->servicio->duracion_estimada) {
                $duracionTotal += $detalle->servicio->duracion_estimada * $detalle->cantidad;
            }
        }
        return $duracionTotal;
    }

    /**
     * Formatea la duración total en formato legible (X horas Y minutos)
     */
    public function getDuracionTotalFormateada(): string
    {
        $minutos = $this->duracionTotal;
        $horas = intdiv($minutos, 60);
        $minutosRestantes = $minutos % 60;
        
        if ($horas > 0 && $minutosRestantes > 0) {
            return "{$horas} hora(s) {$minutosRestantes} minuto(s)";
        } elseif ($horas > 0) {
            return "{$horas} hora(s)";
        } else {
            return "{$minutosRestantes} minuto(s)";
        }
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
