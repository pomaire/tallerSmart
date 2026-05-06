<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla InventoryItem
 */
class InventoryItem extends ActiveRecord
{
    // Estados de stock
    const STATUS_SIN_STOCK = 'sin_stock';
    const STATUS_STOCK_BAJO = 'stock_bajo';
    const STATUS_EN_STOCK = 'en_stock';

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
            [['categoria_id'], 'integer'],
            [['categoria'], 'string', 'max' => 100],
            [['precio_costo', 'precio_venta'], 'number'],
            [['stock_actual', 'stock_minimo', 'stock_maximo'], 'integer'],
            [['unidad_medida'], 'string', 'max' => 20],
            [['ubicacion'], 'string', 'max' => 100],
            [['imagen'], 'string', 'max' => 255],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            [['imagen'], 'file', 'skipOnEmpty' => true, 'extensions' => 'jpg, jpeg, png, gif'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'codigo' => 'Código SKU',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'categoria_id' => 'Categoría',
            'categoria' => 'Categoría',
            'precio_costo' => 'Precio Costo',
            'precio_venta' => 'Precio Venta',
            'stock_actual' => 'Stock Actual',
            'stock_minimo' => 'Stock Mínimo',
            'stock_maximo' => 'Stock Máximo',
            'unidad_medida' => 'Unidad Medida',
            'ubicacion' => 'Ubicación',
            'imagen' => 'Imagen',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    /**
     * Relación con InventoryCategory (HU-027)
     */
    public function getCategoria(): \yii\db\ActiveQuery
    {
        return $this->hasOne(InventoryCategory::class, ['id' => 'categoria_id']);
    }

    public function getMovimientos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryMovement::class, ['inventory_item_id' => 'id']);
    }

    /**
     * Calcular estado del stock (HU-005, HU-011)
     * @return string Estado del stock
     */
    public function getStockEstado(): string
    {
        if ($this->stock_actual == 0) {
            return self::STATUS_SIN_STOCK;
        } elseif ($this->stock_actual <= $this->stock_minimo) {
            return self::STATUS_STOCK_BAJO;
        } else {
            return self::STATUS_EN_STOCK;
        }
    }

    /**
     * Obtener color del badge según estado (HU-011)
     * @return string Color del badge
     */
    public function getStockEstadoColor(): string
    {
        switch ($this->getStockEstado()) {
            case self::STATUS_SIN_STOCK:
                return 'danger';
            case self::STATUS_STOCK_BAJO:
                return 'warning';
            default:
                return 'success';
        }
    }

    /**
     * Obtener etiqueta del estado (HU-011)
     * @return string Etiqueta del estado
     */
    public function getStockEstadoLabel(): string
    {
        switch ($this->getStockEstado()) {
            case self::STATUS_SIN_STOCK:
                return 'Sin Stock';
            case self::STATUS_STOCK_BAJO:
                return 'Stock Bajo';
            default:
                return 'En Stock';
        }
    }

    /**
     * Calcular porcentaje de stock (HU-004, HU-021)
     * @return float Porcentaje de stock (0-100)
     */
    public function getStockPorcentaje(): float
    {
        if ($this->stock_maximo && $this->stock_maximo > 0) {
            return min(100, max(0, ($this->stock_actual / $this->stock_maximo) * 100));
        }
        // Si no hay stock_maximo, usar stock_minimo como referencia (100% cuando stock_actual >= stock_minimo * 2)
        if ($this->stock_minimo && $this->stock_minimo > 0) {
            return min(100, max(0, ($this->stock_actual / ($this->stock_minimo * 2)) * 100));
        }
        return $this->stock_actual > 0 ? 100 : 0;
    }

    /**
     * Generar SKU automáticamente (HU-008)
     * @param string $categoria Categoria o código de categoría
     * @return string SKU generado
     */
    public static function generarSKU(string $categoria): string
    {
        // Mapeo de categorías a códigos
        $categoriaCodigos = [
            'Aceites' => 'OIL',
            'Filtros' => 'FLT',
            'Frenos' => 'BRK',
            'Suspensión' => 'SUS',
            'Transmisión' => 'TRN',
            'Motor' => 'ENG',
            'Eléctrico' => 'ELE',
            'Refrigeración' => 'CLG',
            'Lubricantes' => 'LUB',
            'Repuestos Generales' => 'GEN',
        ];

        $codigo = $categoriaCodigos[$categoria] ?? strtoupper(substr($categoria, 0, 3));
        
        // Obtener el último SKU de esta categoría
        $ultimoItem = self::find()
            ->where(['like', 'codigo', "P-{$codigo}-"])
            ->orderBy(['codigo' => SORT_DESC])
            ->one();

        if ($ultimoItem) {
            // Extraer número del último SKU
            preg_match('/P-[A-Z]+-(\d+)/', $ultimoItem->codigo, $matches);
            $numero = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        } else {
            $numero = 1;
        }

        return sprintf('P-%s-%02d', $codigo, $numero);
    }

    /**
     * Validar que el SKU sea único (HU-010)
     * @param string $attribute Attribute name
     * @param array $params Parameters
     */
    public function validateUniqueSKU($attribute, $params)
    {
        $existing = self::find()
            ->where(['codigo' => $this->codigo])
            ->andWhere(['!=', 'id', $this->id ?? 0])
            ->exists();

        if ($existing) {
            $this->addError($attribute, 'El SKU ya existe en el sistema');
        }
    }

    /**
     * Formatear precio unitario (HU-024)
     * @param float|null $price Precio a formatear
     * @return string Precio formateado
     */
    public static function formatPrice(?float $price): string
    {
        if ($price === null) {
            return '$0.00';
        }
        return '$' . number_format($price, 2);
    }

    /**
     * Obtener unidad de medida formateada (HU-014)
     * @return string Unidad formateada
     */
    public function getUnidadMedidaFormateada(): string
    {
        $unidades = [
            'L' => 'Litros',
            'ML' => 'Mililitros',
            'KG' => 'Kilogramos',
            'G' => 'Gramos',
            'UN' => 'Unidades',
            'PAR' => 'Pares',
            'JGO' => 'Juegos',
            'MT' => 'Metros',
        ];

        $abbr = strtoupper($this->unidad_medida ?? 'UN');
        return $unidades[$abbr] ?? $abbr;
    }

    /**
     * Verificar si tiene stock bajo (HU-015, HU-022)
     * @return bool
     */
    public function isStockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }
}
