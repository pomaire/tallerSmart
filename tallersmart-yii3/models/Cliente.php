<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla cliente
 */
final class Cliente extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'cliente';
    }

    public function getVehiculos(): array
    {
        return $this->hasMany(Vehiculo::class, ['clienteId' => 'id']);
    }

    public function getCitas(): array
    {
        return $this->hasMany(Cita::class, ['clienteId' => 'id']);
    }

    public function getOrdenesServicio(): array
    {
        return $this->hasMany(OrdenServicio::class, ['clienteId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['nombre', 'telefono'], 'required'],
            [['email'], 'email'],
            [['nombre'], 'string', 'max' => 200],
            [['email'], 'string', 'max' => 255],
            [['telefono'], 'string', 'max' => 20],
            [['documento'], 'string', 'max' => 50],
            [['direccion', 'notas'], 'string'],
            [['ciudad', 'pais'], 'string', 'max' => 100],
            [['activo'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'email' => 'Email',
            'telefono' => 'Teléfono',
            'documento' => 'Documento',
            'direccion' => 'Dirección',
            'ciudad' => 'Ciudad',
            'pais' => 'País',
            'activo' => 'Activo',
        ];
    }
}
