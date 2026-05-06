<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla Cliente
 */
class Cliente extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'cliente';
    }

    public function rules(): array
    {
        return [
            [['nombre', 'documento'], 'required'],
            [['tipo_documento'], 'string', 'max' => 50],
            [['documento'], 'string', 'max' => 20],
            [['email'], 'email'],
            [['email', 'telefono', 'direccion', 'ciudad'], 'string', 'max' => 100],
            [['notas'], 'string'],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'tipo_documento' => 'Tipo Documento',
            'documento' => 'Documento',
            'email' => 'Email',
            'telefono' => 'Teléfono',
            'direccion' => 'Dirección',
            'ciudad' => 'Ciudad',
            'notas' => 'Notas',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getVehiculos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Vehiculo::class, ['cliente_id' => 'id']);
    }

    public function getCitas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Cita::class, ['cliente_id' => 'id']);
    }

    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['cliente_id' => 'id']);
    }
}
