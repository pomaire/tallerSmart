<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla permiso
 */
final class Permiso extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'permiso';
    }

    public function getRolPermisos(): array
    {
        return $this->hasMany(RolPermiso::class, ['permisoId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['nombre', 'modulo'], 'required'],
            [['nombre'], 'string', 'max' => 100],
            [['nombre'], 'unique'],
            [['modulo'], 'string', 'max' => 50],
            [['descripcion'], 'string'],
        ];
    }
}
