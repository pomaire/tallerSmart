<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla calificacion
 */
class Calificacion extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'calificacion';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id'], 'required'],
            [['orden_servicio_id', 'puntaje'], 'integer'],
            [['comentario'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'puntaje' => 'Puntaje (1-5)',
            'comentario' => 'Comentario',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Fecha Actualización',
        ];
    }

    /**
     * Relación con OrdenServicio
     */
    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }
}
