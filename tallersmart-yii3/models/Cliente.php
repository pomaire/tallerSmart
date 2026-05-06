<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Modelo para la tabla Cliente
 */
class Cliente extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'cliente';
    }

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['nombre', 'documento'], 'required'],
            [['tipo_documento'], 'string', 'max' => 50],
            [['documento'], 'string', 'max' => 20],
            [['email'], 'email'],
            [['email'], 'unique', 'message' => 'Este email ya está registrado'],
            [['email', 'telefono', 'direccion', 'ciudad'], 'string', 'max' => 100],
            [['notas'], 'string'],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            // HU-016: Validar formato de nombre (solo letras y espacios)
            [['nombre'], 'match', 'pattern' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', 'message' => 'El nombre solo debe contener letras y espacios'],
            // HU-017: Validar formato de teléfono internacional
            [['telefono'], 'match', 'pattern' => '/^\+?[0-9\s\-()]+$/', 'message' => 'El teléfono debe tener un formato válido (ej: +56 9 1234 5678)'],
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

    /**
     * HU-023: Registrar auditoría de cambios
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if (!empty($changedAttributes)) {
            $accion = $insert ? 'CREATE' : 'UPDATE';
            $valoresAnteriores = json_encode($changedAttributes, JSON_UNESCAPED_UNICODE);
            $valoresNuevos = json_encode($this->attributes, JSON_UNESCAPED_UNICODE);
            
            AuditLog::registrarAccion(
                $accion,
                'cliente',
                $this->id,
                $valoresAnteriores,
                $valoresNuevos
            );
        }
    }

    public function beforeDelete()
    {
        // HU-025: Soft delete - no permitir eliminación física si hay órdenes activas
        if ($this->ordenServicio && count($this->ordenServicio) > 0) {
            return false;
        }
        
        // Registrar auditoría
        AuditLog::registrarAccion(
            'DELETE',
            'cliente',
            $this->id,
            json_encode($this->attributes, JSON_UNESCAPED_UNICODE),
            null
        );
        
        return parent::beforeDelete();
    }
}
