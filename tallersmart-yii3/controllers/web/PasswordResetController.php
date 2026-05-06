<?php

namespace app\controllers\web;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Usuario;
use yii\mail\MailerInterface;

/**
 * Controlador para recuperación de contraseña (HU-016)
 */
class PasswordResetController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][] = [
            'actions' => ['request', 'reset'],
            'allow' => true,
            'roles' => ['?'],
        ];
        return $behaviors;
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function actionRequest()
    {
        $model = new \app\models\PasswordResetRequestForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $user = Usuario::find()->where(['email' => strtolower($model->email), 'activo' => true])->one();
            
            if ($user) {
                // Generar token de reset
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Guardar token en el usuario (necesitamos agregar campos a la BD)
                // Por ahora usamos una tabla separada o cache
                Yii::$app->cache->set('password_reset_' . $token, $user->id, 3600);
                
                // Enviar email
                $this->sendResetEmail($user->email, $token);
                
                Yii::$app->session->setFlash('success', 'Se ha enviado un enlace de recuperación a su correo electrónico.');
                return $this->redirect(['site/login']);
            }
            
            // Mensaje genérico por seguridad
            Yii::$app->session->setFlash('success', 'Si el correo existe en nuestro sistema, recibirá un enlace de recuperación.');
            return $this->redirect(['site/login']);
        }
        
        return $this->render('password-reset/request', [
            'model' => $model,
        ]);
    }
    
    /**
     * Resetear contraseña con token
     */
    public function actionReset($token)
    {
        $userId = Yii::$app->cache->get('password_reset_' . $token);
        
        if (!$userId) {
            Yii::$app->session->setFlash('error', 'El enlace de recuperación es inválido o ha expirado.');
            return $this->redirect(['site/login']);
        }
        
        $user = Usuario::findOne($userId);
        
        if (!$user || !$user->activo) {
            Yii::$app->session->setFlash('error', 'El enlace de recuperación es inválido.');
            return $this->redirect(['site/login']);
        }
        
        $model = new \app\models\ResetPasswordForm();
        $model->token = $token;
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Validar mínimo 10 caracteres (HU-010)
            if (strlen($model->password) < 10) {
                $model->addError('password', 'La contraseña debe tener al menos 10 caracteres.');
                return $this->render('password-reset/reset', [
                    'model' => $model,
                    'token' => $token,
                ]);
            }
            
            $user->setPassword($model->password);
            $user->resetFailedAttempts();
            $user->save(false);
            
            // Invalidar el token
            Yii::$app->cache->delete('password_reset_' . $token);
            
            Yii::$app->session->setFlash('success', 'Su contraseña ha sido actualizada. Ahora puede iniciar sesión.');
            return $this->redirect(['site/login']);
        }
        
        return $this->render('password-reset/reset', [
            'model' => $model,
            'token' => $token,
        ]);
    }
    
    /**
     * Envía el email de recuperación
     */
    private function sendResetEmail(string $email, string $token): void
    {
        $resetLink = Yii::$app->urlManager->createAbsoluteUrl(['password-reset/reset', 'token' => $token]);
        
        Yii::$app->mailer->compose('passwordReset', ['resetLink' => $resetLink])
            ->setTo($email)
            ->setSubject('Recuperación de contraseña - TallerSmart')
            ->send();
    }
}
