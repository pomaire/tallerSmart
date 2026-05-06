<?php

namespace app\models;

use yii\base\Model;

/**
 * Formulario para solicitar recuperación de contraseña (HU-016)
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Correo Electrónico',
        ];
    }
}
