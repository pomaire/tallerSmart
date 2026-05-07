<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla cierre_caja
 * Soporta: HU-008, HU-009, HU-016, HU-020
 */
class CierreCaja extends ActiveRecord
{
    const ESTADO_ABIERTO = 'abierto';
    const ESTADO_CERRADO = 'cerrado';

    public static function tableName(): string
    {
        return 'cierre_caja';
    }

    public function rules(): array
    {
        return [
            [['usuario_id', 'fecha_inicio', 'monto_inicial'], 'required'],
            [['usuario_id', 'monto_inicial', 'monto_final', 'ingresos_efectivo', 
              'ingresos_tarjeta', 'ingresos_transferencia', 'egresos'], 'number'],
            [['estado'], 'string', 'max' => 50],
            [['estado'], 'default', 'value' => self::ESTADO_ABIERTO],
            [['fecha_inicio', 'fecha_fin', 'observaciones'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario',
            'fecha_inicio' => 'Fecha Inicio',
            'fecha_fin' => 'Fecha Fin',
            'monto_inicial' => 'Monto Inicial',
            'monto_final' => 'Monto Final',
            'ingresos_efectivo' => 'Ingresos Efectivo',
            'ingresos_tarjeta' => 'Ingresos Tarjeta',
            'ingresos_transferencia' => 'Ingresos Transferencia',
            'egresos' => 'Egresos',
            'observaciones' => 'Observaciones',
            'estado' => 'Estado',
            'created_at' => 'Creado en',
        ];
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
    
    /**
     * Lista de estados (HU-008)
     */
    public static function getEstadosList(): array
    {
        return [
            self::ESTADO_ABIERTO => 'Abierto',
            self::ESTADO_CERRADO => 'Cerrado',
        ];
    }
    
    /**
     * Verifica si el cierre está abierto
     */
    public function getEstaAbierto(): bool
    {
        return $this->estado === self::ESTADO_ABIERTO;
    }
    
    /**
     * Verifica si el cierre está cerrado (HU-008)
     */
    public function getEstaCerrado(): bool
    {
        return $this->estado === self::ESTADO_CERRADO;
    }
    
    /**
     * Calcular total de ingresos (HU-009)
     */
    public function getTotalIngresos(): float
    {
        return ($this->ingresos_efectivo ?? 0) + 
               ($this->ingresos_tarjeta ?? 0) + 
               ($this->ingresos_transferencia ?? 0);
    }
    
    /**
     * Calcular balance final (HU-008)
     */
    public function getBalanceFinal(): float
    {
        return ($this->monto_inicial ?? 0) + $this->totalIngresos - ($this->egresos ?? 0);
    }
    
    /**
     * Formatear monto en formato chileno
     */
    public function getMontoInicialFormateado(): string
    {
        return '$' . number_format($this->monto_inicial ?? 0, 0, ',', '.');
    }
    
    public function getMontoFinalFormateado(): string
    {
        return '$' . number_format($this->monto_final ?? 0, 0, ',', '.');
    }
    
    /**
     * Obtener cierre de caja actual (abierto)
     */
    public static function getCierreActual(): ?self
    {
        return self::findOne(['estado' => self::ESTADO_ABIERTO]);
    }
    
    /**
     * Obtener todos los cierres cerrados (HU-008)
     */
    public static function getCierresCerrados(): array
    {
        return self::find()
            ->where(['estado' => self::ESTADO_CERRADO])
            ->orderBy(['fecha_fin' => SORT_DESC])
            ->all();
    }
    
    /**
     * Cerrar caja (HU-008)
     * @param float $montoFinal Monto final contado
     * @param string|null $observaciones Observaciones del cierre
     * @return bool
     */
    public function cerrar(float $montoFinal, ?string $observaciones = null): bool
    {
        if ($this->estado === self::ESTADO_CERRADO) {
            return false; // Ya está cerrado
        }
        
        $this->monto_final = $montoFinal;
        $this->fecha_fin = new Expression('NOW()');
        $this->estado = self::ESTADO_CERRADO;
        $this->observaciones = $observaciones;
        
        return $this->save(false);
    }
    
    /**
     * Generar resumen del día (HU-008, HU-009)
     * @param string $fecha Fecha en formato Y-m-d
     * @return array Resumen con totales por método de pago
     */
    public static function generarResumenDia(string $fecha): array
    {
        $pagos = Pago::find()
            ->where(['>=', 'fecha_pago', $fecha . ' 00:00:00'])
            ->andWhere(['<=', 'fecha_pago', $fecha . ' 23:59:59'])
            ->andWhere(['estado' => Pago::ESTADO_APROBADO])
            ->all();
        
        $totales = [
            'efectivo' => 0,
            'transferencia' => 0,
            'tarjeta_credito' => 0,
            'tarjeta_debito' => 0,
            'total' => 0,
            'cantidad_pagos' => count($pagos),
        ];
        
        foreach ($pagos as $pago) {
            $metodo = $pago->metodo_pago;
            if (isset($totales[$metodo])) {
                $totales[$metodo] += $pago->monto;
            }
            $totales['total'] += $pago->monto;
        }
        
        return $totales;
    }
    
    /**
     * Obtener reporte mensual (HU-017)
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @return array Totales por día
     */
    public static function getReporteMensual(int $mes, int $anio): array
    {
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $reporte = [];
        
        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            $resumen = self::generarResumenDia($fecha);
            
            if ($resumen['cantidad_pagos'] > 0) {
                $reporte[] = [
                    'fecha' => $fecha,
                    'dia' => $dia,
                    'total' => $resumen['total'],
                    'cantidad_pagos' => $resumen['cantidad_pagos'],
                ];
            }
        }
        
        return $reporte;
    }
}
