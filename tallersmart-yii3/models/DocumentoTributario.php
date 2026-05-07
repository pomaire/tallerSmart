<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla documento_tributario (Boletas, Notas de Crédito)
 * Soporta: HU-010, HU-019, HU-024, HU-030
 * 
 * INTERFAZ PREPARADA PARA INTEGRACIÓN CON SII (HU-024, HU-025)
 */
class DocumentoTributario extends ActiveRecord
{
    // Tipos de documentos (HU-010, HU-019)
    const TIPO_BOLETA = 'boleta';
    const TIPO_NOTA_CREDITO = 'nota_credito';
    const TIPO_FACTURA = 'factura';
    
    // Estados de emisión
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PENDIENTE_SII = 'pendiente_sii';
    const ESTADO_EMITIDO = 'emitido';
    const ESTADO_ANULADO = 'anulado';
    const ESTADO_FALLIDO = 'fallido';
    
    // Colas de reintento (HU-025)
    const COLA_PENDIENTE = 'pendiente';
    const COLA_PROCESANDO = 'procesando';
    const COLA_COMPLETADO = 'completado';
    const COLA_FALLIDO = 'fallido';

    public static function tableName(): string
    {
        return 'documento_tributario';
    }

    public function rules(): array
    {
        return [
            [['tipo', 'folio', 'monto_total'], 'required'],
            [['orden_servicio_id', 'cliente_id', 'usuario_id', 'folio_sii', 'intentos_envio'], 'integer'],
            [['tipo'], 'string', 'max' => 50],
            [['tipo'], 'in', 'range' => array_keys(self::getTiposList())],
            [['estado'], 'string', 'max' => 50],
            [['estado'], 'default', 'value' => self::ESTADO_BORRADOR],
            [['estado_cola'], 'default', 'value' => self::COLA_PENDIENTE],
            [['monto_total', 'monto_neto', 'iva'], 'number'],
            [['folio', 'rut_emisor', 'rut_receptor', 'razon_social_receptor'], 'string', 'max' => 100],
            [['fecha_emision', 'fecha_timbraje', 'datos_sii', 'observaciones'], 'safe'],
            [['codigo_barras', 'hash_documento'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'tipo' => 'Tipo Documento',
            'folio' => 'Folio Interno',
            'folio_sii' => 'Folio SII',
            'orden_servicio_id' => 'Orden de Servicio',
            'cliente_id' => 'Cliente',
            'usuario_id' => 'Emitido por',
            'monto_total' => 'Monto Total',
            'monto_neto' => 'Monto Neto',
            'iva' => 'IVA',
            'estado' => 'Estado',
            'estado_cola' => 'Estado Cola SII',
            'intentos_envio' => 'Intentos Envío',
            'rut_emisor' => 'RUT Emisor',
            'rut_receptor' => 'RUT Receptor',
            'razon_social_receptor' => 'Razón Social Receptor',
            'fecha_emision' => 'Fecha Emisión',
            'fecha_timbraje' => 'Fecha Timbraje',
            'datos_sii' => 'Datos SII',
            'observaciones' => 'Observaciones',
            'created_at' => 'Creado en',
        ];
    }
    
    /**
     * Lista de tipos de documentos (HU-010, HU-019)
     */
    public static function getTiposList(): array
    {
        return [
            self::TIPO_BOLETA => 'Boleta Electrónica',
            self::TIPO_NOTA_CREDITO => 'Nota de Crédito',
            self::TIPO_FACTURA => 'Factura Electrónica',
        ];
    }
    
    /**
     * Lista de estados
     */
    public static function getEstadosList(): array
    {
        return [
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_PENDIENTE_SII => 'Pendiente SII',
            self::ESTADO_EMITIDO => 'Emitido',
            self::ESTADO_ANULADO => 'Anulado',
            self::ESTADO_FALLIDO => 'Fallido',
        ];
    }
    
    /**
     * Verifica si el documento está emitido
     */
    public function getEstaEmitido(): bool
    {
        return $this->estado === self::ESTADO_EMITIDO;
    }
    
    /**
     * Verifica si el documento está anulado
     */
    public function getEstaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }
    
    /**
     * Formatear monto en formato chileno
     */
    public function getMontoTotalFormateado(): string
    {
        return '$' . number_format($this->monto_total ?? 0, 0, ',', '.');
    }

    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }
    
    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }
    
    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
    
    /**
     * Before save - actualizar timestamps
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new Expression('NOW()');
                if (!$this->folio) {
                    $this->folio = $this->generarFolioInterno();
                }
            }
            return true;
        }
        return false;
    }
    
    /**
     * Generar folio interno consecutivo
     */
    private function generarFolioInterno(): string
    {
        $prefijo = strtoupper(substr($this->tipo, 0, 2));
        $ultimo = self::find()
            ->where(['tipo' => $this->tipo])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        
        $consecutivo = $ultimo ? intval(substr($ultimo->folio, -6)) + 1 : 1;
        return $prefijo . '-' . str_pad((string)$consecutivo, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Emitir boleta (HU-010)
     * @param int|null $usuarioId ID del usuario que emite
     * @return bool
     */
    public function emitir(?int $usuarioId = null): bool
    {
        if ($this->estado === self::ESTADO_EMITIDO) {
            return false; // Ya está emitida
        }
        
        $this->usuario_id = $usuarioId;
        $this->fecha_emision = new Expression('NOW()');
        $this->estado = self::ESTADO_PENDIENTE_SII;
        $this->estado_cola = self::COLA_PENDIENTE;
        
        if (!$this->save(false)) {
            return false;
        }
        
        // Intentar timbrar con SII (HU-024)
        return $this->timbrarConSII();
    }
    
    /**
     * Timbrar documento con SII (HU-024)
     * INTERFAZ PREPARADA PARA INTEGRACIÓN FUTURA
     * @return bool
     */
    public function timbrarConSII(): bool
    {
        // TODO: Implementar integración con SII cuando esté disponible
        // Por ahora, simulamos éxito para desarrollo
        
        $this->estado_cola = self::COLA_PROCESANDO;
        $this->save(false);
        
        // Simulación de respuesta exitosa del SII
        // En producción, aquí se haría la llamada real al API del SII
        $this->estado = self::ESTADO_EMITIDO;
        $this->estado_cola = self::COLA_COMPLETADO;
        $this->fecha_timbraje = new Expression('NOW()');
        $this->folio_sii = mt_rand(1000000, 9999999); // Folio simulado
        $this->datos_sii = json_encode([
            'timbre' => 'SIM-' . md5(time()),
            'fecha_timbraje' => date('Y-m-d H:i:s'),
            'folio' => $this->folio_sii,
        ]);
        
        return $this->save(false);
    }
    
    /**
     * Reintentar envío a SII (HU-025)
     * @return bool
     */
    public function reintentarEnvioSII(): bool
    {
        if ($this->estado_cola !== self::COLA_FALLIDO) {
            return false;
        }
        
        if ($this->intentos_envio >= 3) {
            return false; // Máximo de intentos alcanzado (HU-025)
        }
        
        $this->intentos_envio = ($this->intentos_envio ?? 0) + 1;
        $this->estado_cola = self::COLA_PENDIENTE;
        
        return $this->save(false) && $this->timbrarConSII();
    }
    
    /**
     * Anular documento (HU-010, HU-019)
     * @param int|null $usuarioId ID del usuario que anula
     * @param string|null $motivo Motivo de la anulación
     * @return bool
     */
    public function anular(?int $usuarioId = null, ?string $motivo = null): bool
    {
        if (!in_array($this->estado, [self::ESTADO_EMITIDO, self::ESTADO_BORRADOR])) {
            return false; // Solo se puede anular si está emitido o borrador
        }
        
        $this->estado = self::ESTADO_ANULADO;
        $this->observaciones = $motivo;
        
        if (!$this->save(false)) {
            return false;
        }
        
        // Registrar auditoría
        $auditLog = new AuditLog();
        $auditLog->usuario_id = $usuarioId;
        $auditLog->accion = 'UPDATE';
        $auditLog->modulo = 'documentos';
        $auditLog->entidad = 'documento_tributario';
        $auditLog->registro_id = $this->id;
        $auditLog->datos_antiguos = json_encode(['estado' => self::ESTADO_EMITIDO]);
        $auditLog->datos_nuevos = json_encode(['estado' => self::ESTADO_ANULADO, 'motivo' => $motivo]);
        $auditLog->save(false);
        
        return true;
    }
    
    /**
     * Emitir nota de crédito (HU-019)
     * @param int $ordenServicioId ID de la orden original
     * @param float $monto Monto de la nota de crédito
     * @param string $motivo Motivo de la nota de crédito
     * @param int|null $usuarioId ID del usuario que emite
     * @return self|null
     */
    public static function emitirNotaCredito(int $ordenServicioId, float $monto, string $motivo, ?int $usuarioId = null): ?self
    {
        $orden = OrdenServicio::findOne($ordenServicioId);
        if (!$orden) {
            return null;
        }
        
        $nota = new self();
        $nota->tipo = self::TIPO_NOTA_CREDITO;
        $nota->orden_servicio_id = $ordenServicioId;
        $nota->cliente_id = $orden->cliente_id;
        $nota->monto_total = $monto;
        $nota->monto_neto = $monto / 1.19; // Calcular neto (19% IVA)
        $nota->iva = $monto - $nota->monto_neto;
        $nota->observaciones = $motivo;
        
        if (!$nota->emitir($usuarioId)) {
            return null;
        }
        
        return $nota;
    }
    
    /**
     * Obtener documentos por orden (HU-011)
     * @param int $ordenId ID de la orden
     * @return self[]
     */
    public static function getDocumentosPorOrden(int $ordenId): array
    {
        return self::find()
            ->where(['orden_servicio_id' => $ordenId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
    
    /**
     * Procesar cola de documentos pendientes (HU-025)
     * Procesa automáticamente los documentos encolados cuando el SII vuelve
     * @return int Cantidad de documentos procesados
     */
    public static function procesarColaSII(): int
    {
        $documentos = self::find()
            ->where(['estado_cola' => self::COLA_PENDIENTE])
            ->andWhere(['<', 'intentos_envio', 3])
            ->limit(10)
            ->all();
        
        $procesados = 0;
        foreach ($documentos as $doc) {
            if ($doc->timbrarConSII()) {
                $procesados++;
            }
        }
        
        return $procesados;
    }
}
