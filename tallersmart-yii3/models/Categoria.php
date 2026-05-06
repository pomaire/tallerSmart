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
            [['tipo'], 'in', 'range' => ['servicio', 'inventario', 'ambos']],
            [['icono', 'color'], 'string', 'max' => 50],
            [['orden'], 'integer'],
            [['padreId'], 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['padreId' => 'id']],
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
            'tipo' => 'Tipo',
            'icono' => 'Icono',
            'color' => 'Color',
            'orden' => 'Orden',
            'padreId' => 'Categoría Padre',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Servicio::class, ['categoriaId' => 'id']);
    }

    public function getInventoryItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryItem::class, ['categoria_id' => 'id']);
    }

    public function getPadre(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Categoria::class, ['id' => 'padreId']);
    }

    public function getHijos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Categoria::class, ['padreId' => 'id']);
    }
}
