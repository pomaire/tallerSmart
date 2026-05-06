<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla horario_tecnico
 */
class HorarioTecnico extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'horario_tecnico';
    }

    public function rules(): array
    {
        return [
            [['tecnicoId', 'dia_semana'], 'required'],
            [['tecnicoId'], 'integer'],
            [['dia_semana'], 'integer', 'min' => 0, 'max' => 6], // 0=Domingo, 6=Sábado
            [['hora_entrada', 'hora_salida'], 'safe'],
            [['activo'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'tecnicoId' => 'Técnico',
            'dia_semana' => 'Día de la Semana',
            'hora_entrada' => 'Hora Entrada',
            'hora_salida' => 'Hora Salida',
            'activo' => 'Activo',
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
     * Obtiene el nombre del día
     */
    public function getNombreDia(): string
    {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return $dias[$this->dia_semana] ?? 'Desconocido';
    }
}
