<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla Categoria
 */
class Categoria extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'categoria';
    }

    public function rules(): array
    {
        return [
            [['nombre'], 'required'],
            [['nombre', 'descripcion'], 'string', 'max' => 255],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Servicio::class, ['categoria_id' => 'id']);
    }
}
