<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla Servicio
 */
class Servicio extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'servicio';
    }

    public function rules(): array
    {
        return [
            [['nombre', 'precio'], 'required'],
            [['categoria_id'], 'integer'],
            [['nombre', 'descripcion'], 'string', 'max' => 255],
            [['precio'], 'number'],
            [['duracion_estimada'], 'integer', 'min' => 0],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'categoria_id' => 'Categoría',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'precio' => 'Precio',
            'duracion_estimada' => 'Duración Estimada (min)',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getCategoria(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getCitaServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CitaServicio::class, ['servicio_id' => 'id']);
    }

    public function getOrdenServicioDetalles(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioDetalle::class, ['servicio_id' => 'id']);
    }
}
