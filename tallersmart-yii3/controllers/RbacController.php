<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Rol;
use app\models\Usuario;
use app\models\Permiso;

/**
 * Controlador para la gestión de RBAC (Roles, Permisos y Usuarios)
 */
class RbacController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex(): string
    {
        // Verificar permiso básico
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        return $this->render('index', [
            'pageTitle' => 'Gestión de Accesos (RBAC)',
        ]);
    }

    /**
     * Acción para detalles de un rol (opcional si se hace todo por AJAX/API)
     */
    public function actionVerRol(int $id): string
    {
        $model = Rol::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Rol no encontrado.');
        }

        return $this->renderPartial('_role_details', ['model' => $model]);
    }
}
