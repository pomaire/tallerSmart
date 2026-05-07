<?php

namespace app\controllers\web;

use Yii;
use yii\web\BadRequestHttpException;
use app\models\Usuario;
use app\models\Sesion;

/**
 * Controlador de autenticación para el frontend web
 * Maneja login, logout y recuperación de contraseña
 */
class SiteController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Agregar rate limiting (HU-015)
        $behaviors['rateLimiter'] = [
            'class' => \app\behaviors\RateLimitBehavior::class,
            'maxRequests' => 20,
            'period' => 60,
        ];
        
        $behaviors['access']['rules'][] = [
            'actions' => ['login'],
            'allow' => true,
            'roles' => ['?'], // Solo usuarios invitados
        ];
        return $behaviors;
    }

    /**
     * Muestra el dashboard principal (redirige a login si no está autenticado)
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl(['site/login']));
        }
        
        return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl(['dashboard/index']));
    }

    /**
     * Página de login
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new \app\models\LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Registrar log de auditoría
            $this->auditLog('LOGIN', 'Usuario inició sesión', Yii::$app->user->id);
            
            return $this->goBack();
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        $usuarioId = Yii::$app->user->id;
        
        Yii::$app->user->logout();
        
        // Registrar log de auditoría
        $this->auditLog('LOGOUT', 'Usuario cerró sesión', $usuarioId);

        return $this->goHome();
    }

    /**
     * Manual de usuario
     */
    public function actionManual()
    {
        return $this->render('site/manual');
    }

    /**
     * Página de error
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            return $this->render('site/error', ['exception' => $exception]);
        }
    }
}
