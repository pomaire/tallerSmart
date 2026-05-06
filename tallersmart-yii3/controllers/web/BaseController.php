<?php

namespace app\controllers\web;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

/**
 * Controlador base para todas las vistas web del frontend
 * Maneja autenticación, permisos y layout común
 */
class BaseController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'view', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Configurar layout por defecto para todas las vistas web
        $this->layout = 'main';

        return true;
    }

    /**
     * Registra un log de auditoría para las acciones del usuario
     */
    protected function auditLog(string $accion, string $descripcion, ?int $registroId = null): void
    {
        $usuario = Yii::$app->user->identity;
        if ($usuario) {
            \app\models\AuditLog::create([
                'usuario_id' => $usuario->id,
                'accion' => $accion,
                'descripcion' => $descripcion,
                'tabla_afectada' => $this->id,
                'registro_id' => $registroId,
                'ip' => Yii::$app->request->userIP,
                'user_agent' => Yii::$app->request->userAgent,
            ]);
        }
    }
}
