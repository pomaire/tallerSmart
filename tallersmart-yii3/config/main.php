<?php

return [
    'id' => 'tallersmart-api',
    'name' => 'TallerSmart API',
    'language' => 'es',
    'sourceLanguage' => 'es',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'tablePrefix' => '',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ],
        
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        
        'request' => [
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'cookieValidationKey' => getenv('APP_KEY') ?: 'tallersmart-secret-key-change-in-production',
        ],
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                // Reglas para API REST
                'POST api/auth/login' => 'api/auth/login',
                'POST api/auth/logout' => 'api/auth/logout',
                'GET api/auth/me' => 'api/auth/me',
                'POST api/auth/change-password' => 'api/auth/change-password',
                
                // Dashboard
                'GET api/dashboard/stats' => 'api/dashboard/stats',
                'GET api/dashboard/proximas-citas' => 'api/dashboard/proximas-citas',
                'GET api/dashboard/ordenes-recientes' => 'api/dashboard/ordenes-recientes',
                'GET api/dashboard/stock-bajo' => 'api/dashboard/stock-bajo',
                'GET api/dashboard/ingresos-meses' => 'api/dashboard/ingresos-meses',
                
                // Inventario - acciones especiales
                'POST api/inventario/<id>/adjust' => 'api/inventario/adjust',
                'GET api/inventario/<id>/movements' => 'api/inventario/movements',
                
                // Órdenes de servicio - acciones especiales
                'POST api/ordenes-servicio/<id>/finalizar' => 'api/ordenes-servicio/finalizar',
                
                // Citas - acciones especiales
                'POST api/citas/<id>/cancel' => 'api/citas/cancel',
                
                // Reglas REST por defecto para todos los controladores
                'GET,HEAD api/<controller:\w+>' => '<controller>/index',
                'POST api/<controller:\w+>' => '<controller>/create',
                'GET,HEAD api/<controller:\w+>/<id:\d+>' => '<controller>/view',
                'PUT,PATCH api/<controller:\w+>/<id:\d+>' => '<controller>/update',
                'DELETE api/<controller:\w+>/<id:\d+>' => '<controller>/delete',
            ],
        ],
        
        'user' => [
            'identityClass' => 'app\models\Usuario',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
    ],
    
    'params' => [
        'adminEmail' => 'admin@tallersmart.com',
        'supportEmail' => 'soporte@tallersmart.com',
    ],
];
