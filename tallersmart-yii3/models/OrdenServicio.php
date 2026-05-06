<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla orden_servicio
 */
class OrdenServicio extends ActiveRecord
{
    // Estados posibles
    const ESTADO_ABIERTO = 'abierto';
    const ESTADO_EN_PROGRESO = 'en_progreso';
    const ESTADO_ESPERANDO_REPUESTOS = 'esperando_repuestos';
    const ESTADO_LISTO_PARA_ENTREGA = 'listo_para_entrega';
    const ESTADO_ENTREGADA = 'entregada';
    const ESTADO_CANCELADA = 'cancelada';

    // Transiciones válidas de estado
    const TRANSICIONES_VALIDAS = [
        self::ESTADO_ABIERTO => [self::ESTADO_EN_PROGRESO, self::ESTADO_ESPERANDO_REPUESTOS, self::ESTADO_CANCELADA],
        self::ESTADO_EN_PROGRESO => [self::ESTADO_ESPERANDO_REPUESTOS, self::ESTADO_LISTO_PARA_ENTREGA],
        self::ESTADO_ESPERANDO_REPUESTOS => [self::ESTADO_EN_PROGRESO, self::ESTADO_CANCELADA],
        self::ESTADO_LISTO_PARA_ENTREGA => [self::ESTADO_ENTREGADA],
        self::ESTADO_ENTREGADA => [],
        self::ESTADO_CANCELADA => [self::ESTADO_ABIERTO], // Solo reopen
    ];

    public static function tableName(): string
    {
        return 'orden_servicio';
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'vehiculo_id'], 'required'],
            [['cita_id', 'cliente_id', 'vehiculo_id', 'tecnico_id', 'created_by', 'finalizada_por', 'kilometraje'], 'integer'],
            [['numero_orden', 'folio'], 'string', 'max' => 50],
            [['estado'], 'string', 'max' => 50],
            [['prioridad'], 'in', 'range' => ['baja', 'media', 'alta', 'urgente']],
            [['descripcion_problema', 'diagnostico', 'notas_internas', 'notas_tecnico'], 'string'],
            [['total', 'subtotal', 'descuento'], 'number'],
            [['fecha_hora', 'fecha_entrega_estimada', 'fecha_entrega_real', 'finalizada_en', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'cita_id' => 'Cita',
            'cliente_id' => 'Cliente',
            'vehiculo_id' => 'Vehículo',
            'tecnico_id' => 'Técnico',
            'numero_orden' => 'Número de Orden',
            'folio' => 'Folio',
            'estado' => 'Estado',
            'prioridad' => 'Prioridad',
            'descripcion_problema' => 'Descripción del Problema',
            'diagnostico' => 'Diagnóstico',
            'notas_internas' => 'Notas Internas',
            'notas_tecnico' => 'Notas del Técnico',
            'kilometraje' => 'Kilometraje',
            'total' => 'Total',
            'fecha_entrega_estimada' => 'Fecha Entrega Estimada',
            'fecha_entrega_real' => 'Fecha Entrega Real',
            'finalizada_en' => 'Finalizada en',
            'finalizada_por' => 'Finalizada por',
            'created_by' => 'Creado por',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    /**
     * Verifica si la orden está finalizada/cerrada
     */
    public function getEstaFinalizada(): bool
    {
        return $this->estado === 'finalizada' || $this->estado === 'facturada';
    }

    /**
     * Verifica si la orden está facturada
     */
    public function getEstaFacturada(): bool
    {
        return $this->estado === 'facturada';
    }

    /**
     * Calcula la duración total estimada de todos los servicios en la orden
     */
    public function getDuracionTotal(): int
    {
        $duracionTotal = 0;
        foreach ($this->detalles as $detalle) {
            if ($detalle->servicio && $detalle->servicio->duracion_estimada) {
                $duracionTotal += $detalle->servicio->duracion_estimada * $detalle->cantidad;
            }
        }
        return $duracionTotal;
    }

    /**
     * Formatea la duración total en formato legible (X horas Y minutos)
     */
    public function getDuracionTotalFormateada(): string
    {
        $minutos = $this->duracionTotal;
        $horas = intdiv($minutos, 60);
        $minutosRestantes = $minutos % 60;
        
        if ($horas > 0 && $minutosRestantes > 0) {
            return "{$horas} hora(s) {$minutosRestantes} minuto(s)";
        } elseif ($horas > 0) {
            return "{$horas} hora(s)";
        } else {
            return "{$minutosRestantes} minuto(s)";
        }
    }

    public function getCita(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cita::class, ['id' => 'cita_id']);
    }

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVehiculo(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Vehiculo::class, ['id' => 'vehiculo_id']);
    }

    public function getDetalles(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioDetalle::class, ['orden_servicio_id' => 'id']);
    }

    /**
     * Relación con Técnico asignado
     */
    public function getTecnico(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnico_id']);
    }

    /**
     * Before save - actualizar timestamps y campos por defecto
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new Expression('NOW()');
                if (!$this->folio) {
                    $this->folio = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                }
            }
            $this->updated_at = new Expression('NOW()');
            return true;
        }
        return false;
    }

    /**
     * Verifica si una transición de estado es válida (HU-008)
     * @param string $nuevoEstado
     * @return bool
     */
    public function canTransitionTo(string $nuevoEstado): bool
    {
        $estadoActual = $this->estado ?? self::ESTADO_ABIERTO;
        
        if (!isset(self::TRANSICIONES_VALIDAS[$estadoActual])) {
            return false;
        }
        
        return in_array($nuevoEstado, self::TRANSICIONES_VALIDAS[$estadoActual]);
    }

    /**
     * Obtiene el mensaje de error para una transición inválida (HU-008)
     * @param string $nuevoEstado
     * @return string
     */
    public function getTransitionErrorMessage(string $nuevoEstado): string
    {
        $estadoActual = $this->estado ?? self::ESTADO_ABIERTO;
        $transicionesPermitidas = self::TRANSICIONES_VALIDAS[$estadoActual] ?? [];
        
        if (empty($transicionesPermitidas)) {
            return "La orden en estado '{$estadoActual}' no puede cambiar a ningún otro estado.";
        }
        
        $estadosPermitidos = implode(', ', $transicionesPermitidas);
        return "Transición inválida: de '{$estadoActual}' solo se puede cambiar a: {$estadosPermitidos}";
    }

    /**
     * Valida que el vehículo pertenezca al cliente (HU-019)
     * @return bool
     */
    public function validarVehiculoCliente(): bool
    {
        if (!$this->vehiculo_id || !$this->cliente_id) {
            return false;
        }
        
        $vehiculo = Vehiculo::findOne($this->vehiculo_id);
        if (!$vehiculo) {
            return false;
        }
        
        return $vehiculo->cliente_id === $this->cliente_id;
    }

    /**
     * Registra un cambio de estado en el historial (HU-020)
     * @param string $estadoAnterior
     * @param string $estadoNuevo
     * @param int|null $usuarioId
     * @return bool
     */
    public function registrarCambioEstado(string $estadoAnterior, string $estadoNuevo, ?int $usuarioId = null): bool
    {
        $historial = new OrdenServicioHistorial();
        $historial->orden_servicio_id = $this->id;
        $historial->estado_anterior = $estadoAnterior;
        $historial->estado_nuevo = $estadoNuevo;
        $historial->usuario_id = $usuarioId;
        $historial->fecha_cambio = new Expression('NOW()');
        
        return $historial->save(false);
    }

    /**
     * Obtiene el historial de cambios de estado (HU-020)
     * @return \yii\db\ActiveQuery
     */
    public function getHistorialEstados(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioHistorial::class, ['orden_servicio_id' => 'id'])
            ->orderBy(['fecha_cambio' => SORT_ASC]);
    }

    /**
     * Calcula el KPI de trabajos activos (HU-010)
     * open + in_progress + waiting_parts
     * @return int
     */
    public static function getTrabajosActivosCount(): int
    {
        return self::find()
            ->where(['estado' => [
                self::ESTADO_ABIERTO,
                self::ESTADO_EN_PROGRESO,
                self::ESTADO_ESPERANDO_REPUESTOS
            ]])
            ->count();
    }

    /**
     * Verifica si la orden está abierta
     */
    public function getEstaAbierta(): bool
    {
        return $this->estado === self::ESTADO_ABIERTO;
    }

    /**
     * Verifica si la orden está en progreso
     */
    public function getEstaEnProgreso(): bool
    {
        return $this->estado === self::ESTADO_EN_PROGRESO;
    }

    /**
     * Verifica si la orden está esperando repuestos
     */
    public function getEstaEsperandoRepuestos(): bool
    {
        return $this->estado === self::ESTADO_ESPERANDO_REPUESTOS;
    }

    /**
     * Verifica si la orden está lista para entrega
     */
    public function getEstaListaParaEntrega(): bool
    {
        return $this->estado === self::ESTADO_LISTO_PARA_ENTREGA;
    }

    /**
     * Verifica si la orden está entregada
     */
    public function getEstaEntregada(): bool
    {
        return $this->estado === self::ESTADO_ENTREGADA;
    }

    /**
     * Verifica si la orden está cancelada
     */
    public function getEstaCancelada(): bool
    {
        return $this->estado === self::ESTADO_CANCELADA;
    }
}
