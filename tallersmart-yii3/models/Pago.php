<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla Pago
 * Soporta: HU-001, HU-002, HU-003, HU-005, HU-007, HU-011, HU-012, HU-013, HU-014, HU-027
 */
class Pago extends ActiveRecord
{
    // Estados de pago (HU-023)
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_ANULADO = 'anulado';
    const ESTADO_REEMBOLSADO = 'reembolsado';
    
    // Métodos de pago (HU-002)
    const METODO_EFECTIVO = 'efectivo';
    const METODO_TRANSFERENCIA = 'transferencia';
    const METODO_TARJETA_CREDITO = 'tarjeta_credito';
    const METODO_TARJETA_DEBITO = 'tarjeta_debito';
    const METODO_CHEQUE = 'cheque';
    const METODO_OTRO = 'otro';

    public static function tableName(): string
    {
        return 'pago';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id', 'monto', 'metodo_pago'], 'required'],
            [['orden_servicio_id', 'cliente_id', 'created_by'], 'integer'],
            [['monto'], 'number', 'min' => 0], // HU-014: Validar monto no negativo
            [['metodo_pago'], 'string', 'max' => 50],
            [['metodo_pago'], 'in', 'range' => array_keys(self::getMetodosPagoList())],
            [['estado'], 'string', 'max' => 50],
            [['estado'], 'default', 'value' => self::ESTADO_PENDIENTE],
            [['referencia', 'referencia_tarjeta'], 'string', 'max' => 100], // HU-013: Referencia tarjeta
            [['notas'], 'string'], // HU-027: Observaciones de pago
            [['fecha_pago', 'created_at', 'updated_at'], 'safe'],
            [['folio'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'folio' => 'Folio',
            'orden_servicio_id' => 'Orden de Servicio',
            'cliente_id' => 'Cliente',
            'monto' => 'Monto',
            'metodo_pago' => 'Método de Pago',
            'estado' => 'Estado',
            'referencia' => 'Referencia',
            'referencia_tarjeta' => 'Últimos 4 dígitos',
            'notas' => 'Observaciones',
            'fecha_pago' => 'Fecha de Pago',
            'created_by' => 'Creado por',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }
    
    /**
     * Lista de métodos de pago disponibles (HU-002)
     */
    public static function getMetodosPagoList(): array
    {
        return [
            self::METODO_EFECTIVO => 'Efectivo',
            self::METODO_TRANSFERENCIA => 'Transferencia',
            self::METODO_TARJETA_CREDITO => 'Tarjeta de Crédito',
            self::METODO_TARJETA_DEBITO => 'Tarjeta de Débito',
            self::METODO_CHEQUE => 'Cheque',
            self::METODO_OTRO => 'Otro',
        ];
    }
    
    /**
     * Lista de estados de pago (HU-023)
     */
    public static function getEstadosList(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_ANULADO => 'Anulado',
            self::ESTADO_REEMBOLSADO => 'Reembolsado',
        ];
    }
    
    /**
     * Formatea el monto en formato chileno $XX.XXX (HU-003)
     */
    public function getMontoFormateado(): string
    {
        return '$' . number_format($this->monto, 0, ',', '.');
    }
    
    /**
     * Verifica si el pago está aprobado
     */
    public function getEstaAprobado(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }
    
    /**
     * Verifica si el pago está pendiente
     */
    public function getEstaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
    
    /**
     * Verifica si el pago está anulado (HU-012)
     */
    public function getEstaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }

    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }
    
    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }
    
    public function getCreador(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'created_by']);
    }
    
    /**
     * Before save - actualizar timestamps y generar folio
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new Expression('NOW()');
                if (!$this->folio) {
                    $this->folio = 'PAGO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                }
            }
            $this->updated_at = new Expression('NOW()');
            return true;
        }
        return false;
    }
    
    /**
     * Anular pago (HU-012)
     * @param int|null $usuarioId ID del usuario que anula
     * @param string|null $motivo Motivo de la anulación
     * @return bool
     */
    public function anular(?int $usuarioId = null, ?string $motivo = null): bool
    {
        if ($this->estado === self::ESTADO_ANULADO) {
            return false; // Ya está anulado
        }
        
        $estadoAnterior = $this->estado;
        $this->estado = self::ESTADO_ANULADO;
        
        if (!$this->save(false)) {
            return false;
        }
        
        // Registrar en auditoría
        $this->registrarAuditoria($estadoAnterior, self::ESTADO_ANULADO, $usuarioId, $motivo);
        
        // Actualizar estado de la orden asociada
        if ($this->orden_servicio_id) {
            $orden = $this->ordenServicio;
            if ($orden) {
                // Si era el único pago, devolver la orden a pendiente
                $pagosAprobados = self::find()
                    ->where(['orden_servicio_id' => $this->orden_servicio_id])
                    ->andWhere(['estado' => self::ESTADO_APROBADO])
                    ->count();
                
                if ($pagosAprobados === 0) {
                    $orden->estado = OrdenServicio::ESTADO_LISTO_PARA_ENTREGA; // O el estado anterior al pago
                    $orden->save(false);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Registrar auditoría de anulación (HU-012)
     */
    private function registrarAuditoria(string $estadoAnterior, string $estadoNuevo, ?int $usuarioId, ?string $motivo): void
    {
        $auditLog = new AuditLog();
        $auditLog->usuario_id = $usuarioId;
        $auditLog->accion = 'UPDATE';
        $auditLog->modulo = 'pago';
        $auditLog->entidad = 'pago';
        $auditLog->registro_id = $this->id;
        $auditLog->datos_antiguos = json_encode(['estado' => $estadoAnterior]);
        $auditLog->datos_nuevos = json_encode(['estado' => $estadoNuevo, 'motivo' => $motivo]);
        $auditLog->save(false);
    }
    
    /**
     * Calcular vuelto (HU-004)
     * @param float $montoRecibido Monto recibido del cliente
     * @return float Vuelto a entregar
     */
    public static function calcularVuelto(float $montoRecibido, float $montoTotal): float
    {
        return max(0, $montoRecibido - $montoTotal);
    }
    
    /**
     * Obtener historial de pagos de una orden (HU-011)
     * @param int $ordenId ID de la orden
     * @return Pago[]
     */
    public static function getHistorialPorOrden(int $ordenId): array
    {
        return self::find()
            ->where(['orden_servicio_id' => $ordenId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
    
    /**
     * Obtener saldo pendiente de una orden (HU-005, HU-023)
     * @param int $ordenId ID de la orden
     * @return float Saldo pendiente
     */
    public static function getSaldoPendiente(int $ordenId): float
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            return 0;
        }
        
        $totalPagado = self::find()
            ->where(['orden_servicio_id' => $ordenId])
            ->andWhere(['estado' => self::ESTADO_APROBADO])
            ->sum('monto');
        
        return max(0, $orden->total - ($totalPagado ?? 0));
    }
    
    /**
     * Verificar si una orden está completamente pagada (HU-001, HU-023)
     * @param int $ordenId ID de la orden
     * @return bool
     */
    public static function isOrdenPagada(int $ordenId): bool
    {
        return self::getSaldoPendiente($ordenId) <= 0;
    }
    
    /**
     * Obtener estado de cobranza de una orden (HU-006, HU-023)
     * @param int $ordenId ID de la orden
     * @return string Estado: 'pendiente', 'parcial', 'pagada'
     */
    public static function getEstadoCobranza(int $ordenId): string
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            return 'pendiente';
        }
        
        $totalPagado = self::find()
            ->where(['orden_servicio_id' => $ordenId])
            ->andWhere(['estado' => self::ESTADO_APROBADO])
            ->sum('monto');
        
        if ($totalPagado <= 0) {
            return 'pendiente';
        } elseif ($totalPagado < $orden->total) {
            return 'parcial';
        } else {
            return 'pagada';
        }
    }
}
