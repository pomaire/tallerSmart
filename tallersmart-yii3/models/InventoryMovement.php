<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla InventoryMovement
 */
class InventoryMovement extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'inventory_movement';
    }

    public function rules(): array
    {
        return [
            [['inventory_item_id', 'tipo', 'cantidad'], 'required'],
            [['inventory_item_id', 'cantidad', 'created_by'], 'integer'],
            [['tipo'], 'string', 'max' => 20],
            [['precio_unitario'], 'number'],
            [['razon'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'inventory_item_id' => 'Item',
            'tipo' => 'Tipo',
            'cantidad' => 'Cantidad',
            'precio_unitario' => 'Precio Unitario',
            'razon' => 'Razón',
            'created_by' => 'Creado por',
            'created_at' => 'Creado en',
        ];
    }

    public function getItem(): \yii\db\ActiveQuery
    {
        return $this->hasOne(InventoryItem::class, ['id' => 'inventory_item_id']);
    }
}
