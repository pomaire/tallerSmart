<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla Cita
 */
class Cita extends ActiveRecord
{
    // Estados posibles
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_CONFIRMADA = 'confirmada';
    const ESTADO_EN_PROGRESO = 'en_progreso';
    const ESTADO_EN_ESPERA = 'en_espera';
    const ESTADO_CANCELADA = 'cancelada';
    const ESTADO_NO_SHOW = 'no_show';
    const ESTADO_COMPLETADA = 'completada';
    
    public static function tableName(): string
    {
        return 'cita';
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'vehiculo_id', 'fecha_hora'], 'required'],
            [['cliente_id', 'vehiculo_id', 'created_by', 'tecnico_id'], 'integer'],
            [['fecha_hora', 'hora_inicio', 'hora_fin'], 'safe'],
            [['estado'], 'string', 'max' => 50],
            [['estado'], 'in', 'range' => array_keys(self::getEstados())],
            [['notas', 'telefono_contacto'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            // HU-011: Validar al menos un servicio
            [['servicios'], 'required', 'on' => 'create'],
            // HU-022: Validar rango de hora
            [['hora_inicio', 'hora_fin'], 'validateHorario'],
        ];
    }

    /**
     * HU-022: Validar que hora_inicio < hora_fin
     */
    public function validateHorario($attribute, $params)
    {
        if ($this->hora_inicio && $this->hora_fin) {
            if ($this->hora_inicio >= $this->hora_fin) {
                $this->addError($attribute, 'La hora de inicio debe ser menor que la hora de fin');
            }
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cliente_id' => 'Cliente',
            'vehiculo_id' => 'Vehículo',
            'fecha_hora' => 'Fecha y Hora',
            'hora_inicio' => 'Hora Inicio',
            'hora_fin' => 'Hora Fin',
            'estado' => 'Estado',
            'notas' => 'Notas',
            'created_by' => 'Creado por',
            'tecnico_id' => 'Técnico',
            'telefono_contacto' => 'Teléfono Contacto',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    /**
     * Obtener lista de estados
     */
    public static function getEstados(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_CONFIRMADA => 'Confirmada',
            self::ESTADO_EN_PROGRESO => 'En Progreso',
            self::ESTADO_EN_ESPERA => 'En Espera',
            self::ESTADO_CANCELADA => 'Cancelada',
            self::ESTADO_NO_SHOW => 'No Show',
            self::ESTADO_COMPLETADA => 'Completada',
        ];
    }

    /**
     * HU-026: Verificar si la cita puede ser editada
     */
    public function puedeEditar(): bool
    {
        return !in_array($this->estado, [self::ESTADO_EN_PROGRESO, self::ESTADO_COMPLETADA, self::ESTADO_CANCELADA]);
    }

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVehiculo(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Vehiculo::class, ['id' => 'vehiculo_id']);
    }

    public function getCitaServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CitaServicio::class, ['cita_id' => 'id']);
    }

    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['cita_id' => 'id']);
    }

    public function getTecnico(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnico_id']);
    }

    /**
     * HU-009: Cambiar estado de la cita con auditoría
     */
    public function cambiarEstado(string $nuevoEstado, ?string $motivo = null): bool
    {
        $estadoAnterior = $this->estado;
        
        if ($estadoAnterior === $nuevoEstado) {
            return true;
        }
        
        $this->estado = $nuevoEstado;
        
        if (!$this->save(false)) {
            return false;
        }
        
        // Registrar en auditoría
        AuditLog::registrarAccion(
            'UPDATE',
            'Cita',
            $this->id,
            json_encode(['estado' => $estadoAnterior, 'motivo' => $motivo]),
            json_encode(['estado' => $nuevoEstado]),
            'Cita'
        );
        
        return true;
    }

    /**
     * HU-005: Verificar si hay solapamiento de horarios
     */
    public function haySolapamiento(): bool
    {
        if (!$this->fecha_hora || !$this->hora_inicio || !$this->hora_fin) {
            return false;
        }
        
        $query = self::find()
            ->where(['fecha_hora' => $this->fecha_hora])
            ->andWhere(['not', ['id' => $this->id ?? 0]])
            ->andWhere(['not', ['estado' => [self::ESTADO_CANCELADA, self::ESTADO_NO_SHOW]]])
            ->andWhere([
                'or',
                ['and', ['<=', 'hora_inicio', $this->hora_fin], ['>=', 'hora_fin', $this->hora_inicio]],
                ['and', ['<=', 'hora_inicio', $this->hora_inicio], ['>=', 'hora_fin', $this->hora_inicio]],
            ]);
        
        return (bool)$query->count();
    }

    /**
     * HU-029: Contar citas activas en una fecha
     */
    public static function contarCitasActivasPorFecha(string $fecha): int
    {
        return self::find()
            ->where(['fecha_hora' => $fecha])
            ->andWhere(['not', ['estado' => [self::ESTADO_CANCELADA, self::ESTADO_NO_SHOW]]])
            ->count();
    }

    /**
     * HU-029: Verificar si un día está lleno (Workshop Full)
     */
    public static function esDiaLleno(string $fecha, int $capacidadMaxima = 10): bool
    {
        return self::contarCitasActivasPorFecha($fecha) >= $capacidadMaxima;
    }

    /**
     * Before save - actualizar timestamps
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new Expression('NOW()');
            }
            $this->updated_at = new Expression('NOW()');
            return true;
        }
        return false;
    }
}
