<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla audit_log
 * 
 * @property int $id
 * @property int|null $usuarioId
 * @property string $accion
 * @property string $modulo
 * @property string|null $entidad
 * @property int|null $registroId
 * @property array|null $datosAntiguos
 * @property array|null $datosNuevos
 * @property string|null $ipAddress
 * @property string|null $userAgent
 * @property string $createdAt
 * 
 * @property Usuario $usuario
 */
final class AuditLog extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'audit_log';
    }

    public function getUsuario(): \Yiisoft\Db\Query\QueryInterface|array|null
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuarioId']);
    }

    public function rules(): array
    {
        return [
            [['accion', 'modulo'], 'required'],
            [['usuarioId', 'registroId'], 'integer'],
            [['datosAntiguos', 'datosNuevos'], 'json'],
            [['accion'], 'in', 'range' => ['CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'IMPORT']],
            [['modulo'], 'string', 'max' => 50],
            [['entidad'], 'string', 'max' => 100],
            [['ipAddress'], 'ip'],
            [['userAgent'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuarioId' => 'Usuario',
            'accion' => 'Acción',
            'modulo' => 'Módulo',
            'entidad' => 'Entidad',
            'registroId' => 'Registro ID',
            'datosAntiguos' => 'Datos Antiguos',
            'datosNuevos' => 'Datos Nuevos',
            'ipAddress' => 'IP',
            'userAgent' => 'User Agent',
            'createdAt' => 'Fecha',
        ];
    }
}
