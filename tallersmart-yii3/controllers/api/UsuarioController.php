<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Usuario;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use Yii;

/**
 * Controlador REST API para Usuarios
 */
class UsuarioController extends BaseController
{
    public $modelClass = Usuario::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Configurar acciones permitidas
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete'];

        return $behaviors;
    }

    /**
     * Listar usuarios con paginación y filtros
     * GET /api/usuarios
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Usuario::find()
            ->joinWith('rol')
            ->orderBy(['created_at' => SORT_DESC]);

        // Filtros opcionales
        $request = Yii::$app->request;
        $activo = $request->get('activo');
        $rolId = $request->get('rol_id');
        $search = $request->get('search');

        if ($activo !== null) {
            $query->andWhere(['usuario.activo' => (bool)$activo]);
        }

        if ($rolId) {
            $query->andWhere(['usuario.rol_id' => $rolId]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'usuario.nombre', $search],
                ['like', 'usuario.email', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    /**
     * Obtener usuario por ID
     * GET /api/usuarios/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        return [
            'success' => true,
            'data' => $model,
        ];
    }

    /**
     * Crear nuevo usuario
     * POST /api/usuarios
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        
        $model = new Usuario();
        $model->nombre = $request->post('nombre');
        $model->email = $request->post('email');
        $model->rol_id = $request->post('rol_id');
        $model->activo = $request->post('activo', true);
        
        $password = $request->post('password');
        if (!empty($password)) {
            $model->setPassword($password);
        }

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        $this->registrarAuditLog($model->id, 'CREATE_USUARIO', 'Usuario creado: ' . $model->nombre);

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Usuario creado correctamente',
        ];
    }

    /**
     * Actualizar usuario existente
     * PUT/PATCH /api/usuarios/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->nombre = $request->post('nombre', $model->nombre);
        $model->email = $request->post('email', $model->email);
        $model->rol_id = $request->post('rol_id', $model->rol_id);
        $model->activo = $request->post('activo', $model->activo);

        $password = $request->post('password');
        if (!empty($password)) {
            $model->setPassword($password);
        }

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        $this->registrarAuditLog($model->id, 'UPDATE_USUARIO', 'Usuario actualizado: ' . $model->nombre);

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Usuario actualizado correctamente',
        ];
    }

    /**
     * Eliminar usuario
     * DELETE /api/usuarios/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        $nombre = $model->nombre;
        
        $model->delete();

        $this->registrarAuditLog(Yii::$app->user->id ?? 0, 'DELETE_USUARIO', 'Usuario eliminado: ' . $nombre);

        return [
            'success' => true,
            'message' => 'Usuario eliminado correctamente',
        ];
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Usuario
    {
        $model = Usuario::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Usuario no encontrado');
        }

        return $model;
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
