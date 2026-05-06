<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla solicitud_dia_libre
 */
class SolicitudDiaLibre extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'solicitud_dia_libre';
    }

    public function rules(): array
    {
        return [
            [['tecnicoId', 'fecha_solicitud', 'tipo'], 'required'],
            [['tecnicoId'], 'integer'],
            [['fecha_solicitud', 'fecha_inicio', 'fecha_fin', 'fecha_aprobacion', 'fecha_respuesta'], 'safe'],
            [['tipo'], 'string', 'max' => 50], // vacaciones, personal, medico
            [['motivo'], 'string'],
            [['estado'], 'in', 'range' => ['pendiente', 'aprobada', 'rechazada']],
            [['comentarios_aprobacion'], 'string'],
            [['aprobado_por'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'tecnicoId' => 'Técnico',
            'fecha_solicitud' => 'Fecha de Solicitud',
            'fecha_inicio' => 'Fecha Inicio',
            'fecha_fin' => 'Fecha Fin',
            'tipo' => 'Tipo',
            'motivo' => 'Motivo',
            'estado' => 'Estado',
            'comentarios_aprobacion' => 'Comentarios',
            'aprobado_por' => 'Aprobado por',
            'fecha_aprobacion' => 'Fecha Aprobación',
            'fecha_respuesta' => 'Fecha Respuesta',
        ];
    }

    /**
     * Relación con Tecnico
     */
    public function getTecnico(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnicoId']);
    }

    /**
     * Relación con Usuario que aprobó
     */
    public function getAprobador(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'aprobado_por']);
    }
}
