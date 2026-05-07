<?php

declare(strict_types=1);

namespace App\Model;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Modelo ActiveRecord para la tabla usuario
 * 
 * @property int $id
 * @property string $nombre
 * @property string $email
 * @property string $passwordHash
 * @property int|null $rolId
 * @property string|null $telefono
 * @property string $idioma
 * @property bool $activo
 * @property string|null $bloqueadoHasta
 * @property int $intentosFallidos
 * @property bool $debeCambiarPassword
 * @property string|null $avatarUrl
 * @property string $createdAt
 * @property string $updatedAt
 * 
 * @property Rol $rol
 * @property Sesion[] $sesiones
 */
final class Usuario extends ActiveRecord
{
    public function getTableName(): string
    {
        return 'usuario';
    }

    public function getRol(): \Yiisoft\Db\Query\QueryInterface|array|null
    {
        return $this->hasOne(Rol::class, ['id' => 'rolId']);
    }

    public function getSesiones(): array
    {
        return $this->hasMany(Sesion::class, ['usuarioId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['nombre', 'email', 'passwordHash'], 'required'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['rolId', 'intentosFallidos'], 'integer'],
            [['nombre'], 'string', 'max' => 200],
            [['email'], 'string', 'max' => 255],
            [['passwordHash'], 'string', 'max' => 255],
            [['telefono'], 'string', 'max' => 20],
            [['idioma'], 'string', 'max' => 10],
            [['activo', 'debeCambiarPassword'], 'boolean'],
            [['bloqueadoHasta', 'createdAt', 'updatedAt'], 'datetime'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'email' => 'Email',
            'rolId' => 'Rol',
            'telefono' => 'Teléfono',
            'idioma' => 'Idioma',
            'activo' => 'Activo',
            'bloqueadoHasta' => 'Bloqueado Hasta',
            'intentosFallidos' => 'Intentos Fallidos',
            'debeCambiarPassword' => 'Debe Cambiar Password',
            'createdAt' => 'Fecha Creación',
            'updatedAt' => 'Fecha Actualización',
        ];
    }

    /**
     * Verifica si una contraseña coincide con el hash almacenado
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Establece el hash de la contraseña
     */
    public function setPassword(string $password): void
    {
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifica si el usuario está bloqueado
     */
    public function isBlocked(): bool
    {
        if ($this->bloqueadoHasta === null) {
            return false;
        }
        return new \DateTime($this->bloqueadoHasta) > new \DateTime();
    }
    
    /**
     * Incrementa los intentos fallidos de login
     */
    public function incrementFailedAttempts(): void
    {
        $this->intentosFallidos++;
        $this->save(false);
    }
    
    /**
     * Resetea los intentos fallidos a cero
     */
    public function resetFailedAttempts(): void
    {
        $this->intentosFallidos = 0;
        $this->bloqueadoHasta = null;
        $this->save(false);
    }
    
    /**
     * Bloquea la cuenta por 15 minutos
     */
    public function blockAccount(): void
    {
        $bloqueadoHasta = new \DateTime();
        $bloqueadoHasta->modify('+15 minutes');
        $this->bloqueadoHasta = $bloqueadoHasta->format('Y-m-d H:i:s');
        $this->save(false);
    }
    
    /**
     * Verifica si el usuario es administrador
     */
    public function esAdministrador(): bool
    {
        if (!$this->rolId) {
            return false;
        }
        
        $rol = $this->rol;
        return $rol && in_array(strtolower($rol->nombre), ['administrador', 'admin', 'superadmin']);
    }
}
