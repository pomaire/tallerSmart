<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla rol
 */
final class Rol extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'rol';
    }

    public function getRolPermisos(): array
    {
        return $this->hasMany(RolPermiso::class, ['rolId' => 'id']);
    }

    public function getPermisos(): array
    {
        return $this->hasMany(Permiso::class, ['id' => 'permisoId'])
            ->via('rolPermisos');
    }

    public function rules(): array
    {
        return [
            [['nombre'], 'required'],
            [['nombre'], 'string', 'max' => 100],
            [['nombre'], 'unique'],
            [['jerarquia'], 'integer'],
            [['activo'], 'boolean'],
            [['descripcion'], 'string'],
        ];
    }
}
