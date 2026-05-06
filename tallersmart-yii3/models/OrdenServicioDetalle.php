<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla OrdenServicioDetalle
 */
class OrdenServicioDetalle extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'orden_servicio_detalle';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id'], 'required'],
            [['orden_servicio_id', 'servicio_id', 'cantidad'], 'integer'],
            [['descripcion', 'notas'], 'string'],
            [['precio_unitario', 'precio_original'], 'number'],
            [['tipo'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'servicio_id' => 'Servicio',
            'descripcion' => 'Descripción',
            'cantidad' => 'Cantidad',
            'precio_unitario' => 'Precio Unitario',
            'precio_original' => 'Precio Original del Catálogo',
            'tipo' => 'Tipo',
            'notas' => 'Notas',
        ];
    }

    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }

    public function getServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }

    /**
     * Calcula el subtotal del detalle (cantidad * precio unitario)
     */
    public function getSubtotal(): float
    {
        return $this->cantidad * $this->precio_unitario;
    }

    /**
     * Obtiene el servicio relacionado y devuelve su duración estimada
     */
    public function getDuracionEstimada(): ?int
    {
        if ($this->servicio) {
            return $this->servicio->duracion_estimada;
        }
        return null;
    }
}
