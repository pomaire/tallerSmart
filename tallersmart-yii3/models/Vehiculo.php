<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\validators\UniqueValidator;
use Yii;

/**
 * Modelo para la tabla Vehiculo
 */
class Vehiculo extends ActiveRecord
{
    // Escenario para validación de kilometraje ascendente
    public $kilometraje_anterior;
    
    public static function tableName(): string
    {
        return 'vehiculo';
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'marca', 'modelo', 'placa'], 'required'],
            [['cliente_id', 'year', 'kilometraje'], 'integer'],
            [['marca', 'modelo', 'color', 'motor', 'placa', 'vin'], 'string', 'max' => 100],
            [['notas'], 'string'],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            
            // HU-007: Validar patente única
            [['placa'], 'unique', 'message' => 'La patente ya está registrada en el sistema'],
            
            // HU-026: Validar formato de patente local (chilena)
            [['placa'], 'match', 'pattern' => '/^[A-Z]{4}\d{2}$|^[A-Z]{2}\d{4}$/', 'message' => 'Formato de patente inválido. Use LLLLNN (ej: ABCD12) o LLNNNN (ej: AB1234)'],
            
            // HU-011: Validar VIN de 17 caracteres
            [['vin'], 'match', 'pattern' => '/^[A-HJ-NPR-Z0-9]{17}$/i', 'message' => 'VIN debe tener exactamente 17 caracteres válidos'],
            [['vin'], 'string', 'length' => ['min' => 17, 'max' => 17], 'message' => 'VIN debe tener exactamente 17 caracteres'],
            
            // HU-013: Validación personalizada de kilometraje ascendente
            [['kilometraje'], 'validateKilometrajeAscendente'],
        ];
    }

    /**
     * HU-013: Valida que el kilometraje no sea menor al anterior
     */
    public function validateKilometrajeAscendente($attribute, $params, $validator)
    {
        if ($this->isNewRecord) {
            return;
        }
        
        // Obtener el valor anterior desde la base de datos
        $oldRecord = self::findOne($this->id);
        if ($oldRecord && $this->kilometraje < $oldRecord->kilometraje) {
            $this->addError($attribute, "El kilometraje ({$this->kilometraje}) no puede ser menor al registrado anteriormente ({$oldRecord->kilometraje})");
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cliente_id' => 'Cliente',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'year' => 'Año',
            'placa' => 'Placa',
            'vin' => 'VIN',
            'color' => 'Color',
            'kilometraje' => 'Kilometraje',
            'motor' => 'Motor',
            'notas' => 'Notas',
            'activo' => 'Activo',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getCitas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Cita::class, ['vehiculo_id' => 'id']);
    }

    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['vehiculo_id' => 'id']);
    }

    /**
     * HU-025: Obtener última cita del vehículo (optimizado con una sola query)
     */
    public function getUltimaCita(): ?Cita
    {
        return $this->hasOne(Cita::class, ['vehiculo_id' => 'id'])
            ->orderBy(['fecha_hora' => SORT_DESC])
            ->one();
    }

    /**
     * HU-025: Obtener próxima cita pendiente del vehículo (optimizado con una sola query)
     */
    public function getProximaCita(): ?Cita
    {
        return $this->hasOne(Cita::class, ['vehiculo_id' => 'id'])
            ->andWhere(['>=', 'fecha_hora', date('Y-m-d H:i:s')])
            ->andWhere(['estado' => 'pendiente'])
            ->orderBy(['fecha_hora' => SORT_ASC])
            ->one();
    }

    /**
     * HU-008: Formatear kilometraje con separador de miles
     */
    public function getKilometrajeFormateado(): string
    {
        return number_format($this->kilometraje, 0, ',', '.');
    }

    /**
     * HU-009: Obtener fecha de última cita formateada
     */
    public function getFechaUltimaCita(): string
    {
        $cita = $this->ultimaCita;
        return $cita ? date('d/m/Y', strtotime($cita->fecha_hora)) : 'N/A';
    }

    /**
     * HU-010: Obtener fecha de próxima cita formateada
     */
    public function getFechaProximaCita(): string
    {
        $cita = $this->proximaCita;
        return $cita ? date('d/m/Y', strtotime($cita->fecha_hora)) : 'Sin cita';
    }

    /**
     * HU-021: Verificar si tiene órdenes de servicio abiertas/pendientes
     */
    public function tieneOrdenesAbiertas(): bool
    {
        return $this->getOrdenesServicio()
            ->where(['NOT', ['estado' => ['finalizada', 'facturada', 'cancelada']]])
            ->exists();
    }

    /**
     * HU-021: Verificar si se puede eliminar el vehículo
     */
    public function canDelete(): array
    {
        if ($this->tieneOrdenesAbiertas()) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el vehículo porque tiene servicios pendientes'
            ];
        }
        return ['success' => true];
    }

    /**
     * HU-027: Registrar auditoría de cambios
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if (!empty($changedAttributes)) {
            $datosAntiguos = [];
            $datosNuevos = [];
            
            foreach ($changedAttributes as $attribute => $oldValue) {
                $datosAntiguos[$attribute] = $oldValue;
                $datosNuevos[$attribute] = $this->$attribute;
            }
            
            // Crear registro de auditoría directamente
            $audit = new \App\Model\AuditLog();
            $audit->accion = $insert ? 'CREATE' : 'UPDATE';
            $audit->modulo = 'Vehiculos';
            $audit->entidad = 'Vehiculo';
            $audit->registroId = $this->id;
            $audit->datosAntiguos = $insert ? null : $datosAntiguos;
            $audit->datosNuevos = $datosNuevos;
            $audit->usuarioId = Yii::$app->user->id ?? null;
            $audit->ipAddress = Yii::$app->request->userIP ?? null;
            $audit->save(false);
        }
    }

    /**
     * HU-027: Registrar auditoría al eliminar
     */
    public function beforeDelete()
    {
        $audit = new \App\Model\AuditLog();
        $audit->accion = 'DELETE';
        $audit->modulo = 'Vehiculos';
        $audit->entidad = 'Vehiculo';
        $audit->registroId = $this->id;
        $audit->datosAntiguos = $this->toArray();
        $audit->datosNuevos = null;
        $audit->usuarioId = Yii::$app->user->id ?? null;
        $audit->ipAddress = Yii::$app->request->userIP ?? null;
        $audit->save(false);
        
        return parent::beforeDelete();
    }
}
