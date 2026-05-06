<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla InventoryCategory
 */
class InventoryCategory extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'inventory_category';
    }

    public function rules(): array
    {
        return [
            [['nombre'], 'required'],
            [['nombre'], 'string', 'max' => 100],
            [['descripcion'], 'string'],
            [['codigo'], 'string', 'max' => 10],
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
            'codigo' => 'Código',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    /**
     * Relación con InventoryItem (HU-027)
     */
    public function getItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryItem::class, ['categoria_id' => 'id']);
    }

    /**
     * Obtener items con stock bajo de esta categoría
     */
    public function getItemsStockBajo(): \yii\db\ActiveQuery
    {
        return $this->getItems()
            ->where(['<=', 'stock_actual', new \yii\db\Expression('stock_minimo')])
            ->andWhere(['activo' => true]);
    }
}
