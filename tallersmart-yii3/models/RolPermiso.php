<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla rol_permiso
 */
final class RolPermiso extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'rol_permiso';
    }

    public function getRol(): \Yiisoft\Db\Query\QueryInterface|array|null
    {
        return $this->hasOne(Rol::class, ['id' => 'rolId']);
    }

    public function getPermiso(): \Yiisoft\Db\Query\QueryInterface|array|null
    {
        return $this->hasOne(Permiso::class, ['id' => 'permisoId']);
    }

    public function rules(): array
    {
        return [
            [['rolId', 'permisoId'], 'required'],
            [['rolId', 'permisoId'], 'integer'],
        ];
    }
}
