<?php
declare(strict_types=1);

use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\ServerRequestFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar contenedor de dependencias desde config
$container = require __DIR__ . '/../config/bootstrap.php';

// Crear la aplicación Yii3
$application = $container->get(Application::class);

// Manejar la solicitud y enviar respuesta
$request = $container->get(ServerRequestFactory::class)->createFromGlobals();
$response = $application->handle($request);
$response->emit();

$application->shutdown();
