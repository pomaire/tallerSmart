<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla sesion
 */
final class Sesion extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'sesion';
    }

    public function getUsuario(): \Yiisoft\Db\Query\QueryInterface|array|null
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuarioId']);
    }

    public function rules(): array
    {
        return [
            [['usuarioId', 'token', 'expiraEn'], 'required'],
            [['usuarioId'], 'integer'],
            [['token'], 'string', 'max' => 255],
            [['ipAddress'], 'ip'],
            [['userAgent'], 'string', 'max' => 500],
            [['activa'], 'boolean'],
            [['expiraEn', 'createdAt'], 'datetime'],
        ];
    }

    /**
     * Verifica si la sesión está expirada
     */
    public function isExpired(): bool
    {
        return new \DateTime($this->expiraEn) < new \DateTime();
    }

    /**
     * Verifica si la sesión es válida
     */
    public function isValid(): bool
    {
        return $this->activa && !$this->isExpired();
    }
}
