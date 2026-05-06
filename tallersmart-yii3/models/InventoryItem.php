<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla InventoryItem
 */
class InventoryItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'inventory_item';
    }

    public function rules(): array
    {
        return [
            [['codigo', 'nombre'], 'required'],
            [['codigo'], 'string', 'max' => 50],
            [['nombre'], 'string', 'max' => 200],
            [['descripcion'], 'string'],
            [['categoria'], 'string', 'max' => 100],
            [['precio_costo', 'precio_venta'], 'number'],
            [['stock_actual', 'stock_minimo'], 'integer'],
            [['unidad_medida'], 'string', 'max' => 20],
            [['ubicacion'], 'string', 'max' => 100],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'codigo' => 'Código',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'categoria' => 'Categoría',
            'precio_costo' => 'Precio Costo',
            'precio_venta' => 'Precio Venta',
            'stock_actual' => 'Stock Actual',
            'stock_minimo' => 'Stock Mínimo',
            'unidad_medida' => 'Unidad Medida',
            'ubicacion' => 'Ubicación',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getMovimientos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryMovement::class, ['inventory_item_id' => 'id']);
    }
}
