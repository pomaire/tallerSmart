<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use Yii;

/**
 * Modelo para la tabla audit_log
 */
class AuditLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'audit_log';
    }

    public function rules(): array
    {
        return [
            [['accion', 'modulo'], 'required'],
            [['usuario_id', 'registro_id'], 'integer'],
            [['datos_antiguos', 'datos_nuevos'], 'string'],
            [['accion'], 'in', 'range' => ['CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'IMPORT']],
            [['modulo'], 'string', 'max' => 50],
            [['entidad'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario',
            'accion' => 'Acción',
            'modulo' => 'Módulo',
            'entidad' => 'Entidad',
            'registro_id' => 'Registro ID',
            'datos_antiguos' => 'Datos Antiguos',
            'datos_nuevos' => 'Datos Nuevos',
            'ip_address' => 'IP',
            'user_agent' => 'User Agent',
            'created_at' => 'Fecha',
        ];
    }

    /**
     * HU-023: Registrar acción de auditoría
     */
    public static function registrarAccion(
        string $accion,
        string $modulo,
        ?int $registroId = null,
        ?string $datosAntiguos = null,
        ?string $datosNuevos = null,
        ?string $entidad = null
    ): bool {
        $audit = new self();
        $audit->accion = $accion;
        $audit->modulo = $modulo;
        $audit->registro_id = $registroId;
        $audit->entidad = $entidad;
        $audit->datos_antiguos = $datosAntiguos;
        $audit->datos_nuevos = $datosNuevos;
        $audit->ip_address = Yii::$app->request->userIP ?? null;
        $audit->user_agent = Yii::$app->request->userAgent ?? null;
        $audit->usuario_id = Yii::$app->user->id ?? null;
        
        return $audit->save();
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
