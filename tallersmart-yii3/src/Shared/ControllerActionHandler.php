<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yii;

/**
 * Handler para ejecutar acciones de controladores Yii2 desde rutas Yii3
 */
final class ControllerActionHandler implements RequestHandlerInterface
{
    public function __construct(
        private string $controllerClass,
        private string $actionId,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Crear instancia del controlador
        $controller = Yii::createObject([
            'class' => $this->controllerClass,
            'id' => explode('\\', $this->controllerClass)[count(explode('\\', $this->controllerClass)) - 1],
            'module' => null,
        ]);

        // Ejecutar la acción
        return $controller->run($this->actionId, $request);
    }
}
