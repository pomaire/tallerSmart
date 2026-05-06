<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Usuario;
use app\models\Sesion;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use yii\filters\Cors;
use Yii;

/**
 * Controlador de autenticación REST API
 */
class AuthController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Configuración de CORS
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        // Negociación de contenido (JSON por defecto)
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    /**
     * Login de usuario
     * POST /api/auth/login
     */
    public function actionLogin(): array
    {
        $request = Yii::$app->request;
        
        $email = $request->post('email');
        $password = $request->post('password');

        if (empty($email) || empty($password)) {
            throw new BadRequestHttpException('Email y contraseña son requeridos');
        }

        $usuario = Usuario::findByEmail($email);

        if (!$usuario || !$usuario->validatePassword($password)) {
            // Registrar intento fallido
            $this->registrarIntentoFallido($email);
            throw new UnauthorizedHttpException('Credenciales inválidas');
        }

        if (!$usuario->activo) {
            throw new ForbiddenHttpException('Usuario inactivo');
        }

        // Crear sesión/token
        $token = bin2hex(random_bytes(32));
        $sesion = new Sesion();
        $sesion->usuario_id = $usuario->id;
        $sesion->token = $token;
        $sesion->ip_address = $request->userIP;
        $sesion->user_agent = $request->userAgent;
        $sesion->expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        if (!$sesion->save()) {
            throw new \yii\web\ServerErrorHttpException('Error al crear sesión');
        }

        // Registrar log de auditoría
        $this->registrarAuditLog($usuario->id, 'LOGIN', 'Inicio de sesión exitoso');

        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'rol_id' => $usuario->rol_id,
                ],
            ],
        ];
    }

    /**
     * Logout de usuario
     * POST /api/auth/logout
     */
    public function actionLogout(): array
    {
        $token = Yii::$app->request->headers->get('Authorization');
        
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            Sesion::deleteAll(['token' => $token]);
        }

        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ];
    }

    /**
     * Obtener usuario actual
     * GET /api/auth/me
     */
    public function actionMe(): array
    {
        $token = Yii::$app->request->headers->get('Authorization');
        
        if (!$token) {
            throw new UnauthorizedHttpException('Token no proporcionado');
        }

        $token = str_replace('Bearer ', '', $token);
        $sesion = Sesion::findOne(['token' => $token]);

        if (!$sesion || strtotime($sesion->expires_at) < time()) {
            throw new UnauthorizedHttpException('Sesión inválida o expirada');
        }

        $usuario = Usuario::findOne($sesion->usuario_id);

        if (!$usuario) {
            throw new UnauthorizedHttpException('Usuario no encontrado');
        }

        // Obtener permisos del usuario
        $permisos = $usuario->getPermisos()->select('permiso.nombre')->column();

        return [
            'success' => true,
            'data' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol_id' => $usuario->rol_id,
                'activo' => $usuario->activo,
                'permisos' => $permisos,
            ],
        ];
    }

    /**
     * Cambiar contraseña
     * POST /api/auth/change-password
     */
    public function actionChangePassword(): array
    {
        $token = Yii::$app->request->headers->get('Authorization');
        
        if (!$token) {
            throw new UnauthorizedHttpException('Token no proporcionado');
        }

        $token = str_replace('Bearer ', '', $token);
        $sesion = Sesion::findOne(['token' => $token]);

        if (!$sesion) {
            throw new UnauthorizedHttpException('Sesión inválida');
        }

        $usuario = Usuario::findOne($sesion->usuario_id);
        $request = Yii::$app->request;

        $passwordActual = $request->post('password_actual');
        $passwordNuevo = $request->post('password_nuevo');

        if (empty($passwordActual) || empty($passwordNuevo)) {
            throw new BadRequestHttpException('Todas las contraseñas son requeridas');
        }

        if (!$usuario->validatePassword($passwordActual)) {
            throw new BadRequestHttpException('Contraseña actual incorrecta');
        }

        if (strlen($passwordNuevo) < 6) {
            throw new BadRequestHttpException('La nueva contraseña debe tener al menos 6 caracteres');
        }

        $usuario->setPassword($passwordNuevo);
        
        if (!$usuario->save()) {
            throw new \yii\web\ServerErrorHttpException('Error al cambiar contraseña');
        }

        $this->registrarAuditLog($usuario->id, 'CHANGE_PASSWORD', 'Cambio de contraseña exitoso');

        return [
            'success' => true,
            'message' => 'Contraseña cambiada correctamente',
        ];
    }

    /**
     * Registrar intento de login fallido
     */
    private function registrarIntentoFallido(string $email): void
    {
        // Implementación opcional para tracking de intentos fallidos
        Yii::info("Intento fallido de login para: {$email}");
    }

    /**
     * Registrar log de auditoría
     */
    private function registrarAuditLog(int $usuarioId, string $accion, string $descripcion): void
    {
        $log = new \app\models\AuditLog();
        $log->usuario_id = $usuarioId;
        $log->accion = $accion;
        $log->descripcion = $descripcion;
        $log->ip_address = Yii::$app->request->userIP;
        $log->user_agent = Yii::$app->request->userAgent;
        $log->created_at = date('Y-m-d H:i:s');
        $log->save(false);
    }
}
