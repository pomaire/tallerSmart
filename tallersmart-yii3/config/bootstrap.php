<?php

declare(strict_types=1);

use App\Environment;
use Yiisoft\Config\Config;
use Yiisoft\Definitions\Container;
use Yiisoft\Definitions\Helpers\DynamicReference;

// Load environment variables
require_once __DIR__ . '/../src/bootstrap.php';

// Build configuration
$config = new Config(
    sourcePath: __DIR__,
    cachePath: dirname(__DIR__) . '/runtime/cache',
    environment: Environment::appEnv(),
);

// Create container with web configuration
$container = new Container(
    $config->get('di'),
    $config->get('di-web'),
    [],
    $config->get('di-delegates'),
    $config->get('di-delegates-web'),
    $config->get('di-providers'),
    $config->get('di-providers-web'),
);

// Store config in container for later use
$container->set(Config::class, $config);

// Run bootstrap callbacks
$bootstrapCallbacks = array_merge(
    $config->get('bootstrap'),
    $config->get('bootstrap-web'),
);

foreach ($bootstrapCallbacks as $callback) {
    $callback($container);
}

return $container;
