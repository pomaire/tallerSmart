<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla tecnico
 */
class Tecnico extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'tecnico';
    }

    public function rules(): array
    {
        return [
            [['usuarioId'], 'integer'],
            [['especialidad'], 'string', 'max' => 100],
            [['nivel'], 'in', 'range' => ['junior', 'semi-senior', 'senior', 'master']],
            [['activo'], 'boolean'],
            [['createdAt', 'updatedAt'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuarioId' => 'Usuario',
            'especialidad' => 'Especialidad',
            'nivel' => 'Nivel',
            'activo' => 'Activo',
            'createdAt' => 'Fecha Creación',
            'updatedAt' => 'Fecha Actualización',
        ];
    }

    /**
     * Relación con Usuario
     */
    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuarioId']);
    }

    /**
     * Relación con Órdenes de Servicio asignadas
     */
    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['tecnicoId' => 'id']);
    }

    /**
     * Relación con Órdenes de Servicio activas (no entregadas/canceladas)
     */
    public function getOrdenesActivas(): \yii\db\ActiveQuery
    {
        return $this->getOrdenesServicio()
            ->andWhere(['NOT IN', 'estado', ['entregada', 'cancelada']]);
    }

    /**
     * Obtiene la cantidad de órdenes activas asignadas al técnico
     */
    public function getCantidadOrdenesActivas(): int
    {
        return $this->getOrdenesActivas()->count();
    }

    /**
     * Verifica si el técnico está disponible (activo y sin órdenes o con pocas)
     */
    public function getDisponible(): bool
    {
        if (!$this->activo) {
            return false;
        }
        
        // Un técnico está disponible si tiene menos de 3 órdenes activas
        return $this->cantidadOrdenesActivas < 3;
    }

    /**
     * Obtiene el estado del técnico (disponible/ocupado)
     */
    public function getEstado(): string
    {
        if (!$this->activo) {
            return 'inactivo';
        }
        
        return $this->disponible ? 'disponible' : 'ocupado';
    }

    /**
     * Calcula las horas trabajadas en un período
     */
    public function getHorasTrabajadas(?string $fechaDesde = null, ?string $fechaHasta = null): float
    {
        $query = $this->getOrdenesServicio()
            ->andWhere(['estado' => ['entregada', 'listo_para_entrega']]);

        if ($fechaDesde) {
            $query->andWhere(['>=', 'fechaEntregaReal', $fechaDesde]);
        }
        
        if ($fechaHasta) {
            $query->andWhere(['<=', 'fechaEntregaReal', $fechaHasta]);
        }

        $ordenes = $query->all();
        $totalMinutos = 0;

        foreach ($ordenes as $orden) {
            if ($orden->fechaEntregaReal && $orden->created_at) {
                $diff = strtotime($orden->fechaEntregaReal) - strtotime($orden->created_at);
                $totalMinutos += max(0, $diff / 60);
            }
        }

        return round($totalMinutos / 60, 2);
    }

    /**
     * Obtiene historial de trabajos completados
     */
    public function getHistorialTrabajos(int $limite = 50): array
    {
        return $this->getOrdenesServicio()
            ->andWhere(['estado' => ['entregada', 'listo_para_entrega']])
            ->orderBy(['fechaEntregaReal' => SORT_DESC])
            ->limit($limite)
            ->all();
    }

    /**
     * Calcula la productividad del técnico
     */
    public function getProductividad(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $query = $this->getOrdenesServicio()
            ->andWhere(['estado' => ['entregada', 'listo_para_entrega']]);

        if ($fechaDesde) {
            $query->andWhere(['>=', 'created_at', $fechaDesde]);
        }
        
        if ($fechaHasta) {
            $query->andWhere(['<=', 'created_at', $fechaHasta]);
        }

        $ordenes = $query->all();
        
        $totalOrdenes = count($ordenes);
        $totalHoras = $this->horasTrabajadas($fechaDesde, $fechaHasta);
        $eficiencia = 0;

        if ($totalOrdenes > 0 && $totalHoras > 0) {
            $eficiencia = round(($totalOrdenes / $totalHoras) * 100, 2);
        }

        return [
            'ordenes_completadas' => $totalOrdenes,
            'horas_trabajadas' => $totalHoras,
            'eficiencia' => $eficiencia,
        ];
    }

    /**
     * Obtiene calificación promedio del técnico
     */
    public function getCalificacionPromedio(): float
    {
        $calificaciones = Calificacion::find()
            ->joinWith('ordenServicio')
            ->where(['orden_servicio.tecnicoId' => $this->id])
            ->andWhere(['!=', 'calificacion.puntaje', null])
            ->all();

        if (empty($calificaciones)) {
            return 0;
        }

        $suma = array_sum(array_column($calificaciones, 'puntaje'));
        return round($suma / count($calificaciones), 2);
    }

    /**
     * Verifica si el técnico tiene múltiples especialidades
     */
    public function getEsMultiespecialidad(): bool
    {
        if (empty($this->especialidad)) {
            return false;
        }
        
        // Si la especialidad contiene comas o punto y coma, es multiespecialidad
        return str_contains($this->especialidad, ',') || str_contains($this->especialidad, ';');
    }

    /**
     * Obtiene lista de especialidades separadas
     */
    public function getListaEspecialidades(): array
    {
        if (empty($this->especialidad)) {
            return [];
        }

        $separador = str_contains($this->especialidad, ';') ? ';' : ',';
        $especialidades = explode($separador, $this->especialidad);
        
        return array_map('trim', $especialidades);
    }

    /**
     * Before save - actualizar timestamp
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->createdAt = new Expression('NOW()');
            }
            $this->updatedAt = new Expression('NOW()');
            return true;
        }
        return false;
    }
}
