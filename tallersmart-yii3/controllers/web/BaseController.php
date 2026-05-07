<?php

namespace app\controllers\web;

use Yiisoft\Yii\Http\Controller;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Validator\ValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\View\ViewContextInterface;
use Yiisoft\Yii\Http\Exception\ForbiddenException;

/**
 * Controlador base para todas las vistas web del frontend
 * Maneja autenticación, permisos y layout común
 */
class BaseController extends Controller implements ViewContextInterface
{
    protected SessionInterface $session;
    protected ValidatorInterface $validator;
    
    public function __construct(
        string $id,
        UrlGeneratorInterface $urlGenerator,
        SessionInterface $session,
        ValidatorInterface $validator
    ) {
        $this->session = $session;
        $this->validator = $validator;
        parent::__construct($id, $urlGenerator);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewPath(): string
    {
        return dirname(__DIR__, 2) . '/views/' . str_replace('\\', '/', $this->getControllerId());
    }

    /**
     * Registra un log de auditoría para las acciones del usuario
     */
    protected function auditLog(string $accion, string $descripcion, ?int $registroId = null): void
    {
        // Implementación pendiente - depende del sistema de logging de Yii3
    }
}
