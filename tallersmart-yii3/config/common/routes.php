<?php

declare(strict_types=1);

use App\Api;
use Yiisoft\Router\Route;

/**
 * @var array $params
 */

return [
    Route::get('/')->action(Api\IndexAction::class)->name('app/index'),
    
    // Rutas web para autenticación
    Route::get('/site/login')->action(\app\controllers\web\SiteController::class)->name('site/login'),
    Route::post('/site/login')->action(\app\controllers\web\SiteController::class)->name('site/login-post'),
    Route::get('/site/logout')->action(\app\controllers\web\SiteController::class)->name('site/logout'),
    
    // Ruta para dashboard
    Route::get('/dashboard')->action(\app\controllers\web\DashboardController::class)->name('dashboard/index'),
];
