<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Formulario de login para el frontend web
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = false;

    private $_user = false;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'email'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
            ['password', 'string', 'min' => 10, 'message' => 'La contraseña debe tener al menos 10 caracteres.'], // HU-010
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Correo Electrónico',
            'password' => 'Contraseña',
            'rememberMe' => 'Recordarme',
        ];
    }

    /**
     * Valida la contraseña
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            
            // Verificar si el usuario existe y está activo
            if (!$user) {
                $this->addError($attribute, 'Credenciales inválidas.');
                return;
            }
            
            // Verificar si el usuario está activo
            if (!$user->activo) {
                $this->addError($attribute, 'Credenciales inválidas.');
                return;
            }
            
            // Verificar si el usuario está bloqueado
            if ($user->isBlocked()) {
                $tiempoRestante = $this->getRemainingBlockTime($user);
                $this->addError($attribute, "Cuenta bloqueada temporalmente. Intente en {$tiempoRestante}.");
                return;
            }
            
            // Verificar contraseña
            if (!$user->validatePassword($this->password)) {
                // Incrementar intentos fallidos
                $user->incrementFailedAttempts();
                
                // Verificar si se alcanzó el límite de intentos
                if ($user->intentosFallidos >= 5) {
                    $user->blockAccount();
                    $this->addError($attribute, 'Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intente en 15 minutos.');
                } else {
                    $this->addError($attribute, 'Credenciales inválidas.');
                }
                return;
            }
            
            // Resetear intentos fallidos al iniciar sesión exitosamente
            $user->resetFailedAttempts();
        }
    }
    
    /**
     * Obtiene el tiempo restante de bloqueo en formato legible
     */
    private function getRemainingBlockTime(Usuario $user): string
    {
        $bloqueadoHasta = new \DateTime($user->bloqueadoHasta);
        $ahora = new \DateTime();
        $diferencia = $bloqueadoHasta->diff($ahora);
        
        if ($diferencia->i > 0) {
            return "{$diferencia->i} minutos";
        }
        return "{$diferencia->s} segundos";
    }

    /**
     * Inicia sesión del usuario
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            
            // Regenerar session ID para prevenir session fixation (HU-027)
            Yii::$app->session->regenerateID(true);
            
            return Yii::$app->user->login($user, $this->rememberMe ? 3600*24*7 : 0);
        }
        return false;
    }

    /**
     * Busca al usuario por email
     */
    protected function getUser(): ?Usuario
    {
        if ($this->_user === false) {
            $this->_user = Usuario::findByUsername($this->email);
        }
        return $this->_user;
    }
}
