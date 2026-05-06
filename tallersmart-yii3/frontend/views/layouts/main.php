<?php

declare(strict_types=1);

/**
 * @var string $content
 * @var \Yiisoft\View\WebView $this
 */

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Meta;

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => 'utf-8'], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerMetaTag(['name' => 'description', 'content' => 'Sistema de Gestión de Talleres Mecánicos - TallerSmart']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => '/favicon.ico']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <title><?= Html::encode($this->title ?? 'TallerSmart') ?></title>
    <?php $this->head() ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php $this->beginBody() ?>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar bg-dark text-white">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <h4 class="m-0"><i class="fas fa-wrench me-2"></i>TallerSmart</h4>
    </div>
    
    <nav class="sidebar-nav mt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="/" class="nav-link text-white <?= $this->context->route === 'site/index' ? 'active' : '' ?>">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="/citas" class="nav-link text-white <?= str_contains($this->context->route, 'cita') ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt me-2"></i>Citas
                </a>
            </li>
            <li class="nav-item">
                <a href="/ordenes" class="nav-link text-white <?= str_contains($this->context->route, 'orden') ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list me-2"></i>Órdenes
                </a>
            </li>
            <li class="nav-item">
                <a href="/clientes" class="nav-link text-white <?= str_contains($this->context->route, 'cliente') ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i>Clientes
                </a>
            </li>
            <li class="nav-item">
                <a href="/vehiculos" class="nav-link text-white <?= str_contains($this->context->route, 'vehiculo') ? 'active' : '' ?>">
                    <i class="fas fa-car me-2"></i>Vehículos
                </a>
            </li>
            <li class="nav-item">
                <a href="/servicios" class="nav-link text-white <?= str_contains($this->context->route, 'servicio') ? 'active' : '' ?>">
                    <i class="fas fa-tools me-2"></i>Servicios
                </a>
            </li>
            <li class="nav-item">
                <a href="/inventario" class="nav-link text-white <?= str_contains($this->context->route, 'inventario') ? 'active' : '' ?>">
                    <i class="fas fa-boxes me-2"></i>Inventario
                </a>
            </li>
            <li class="nav-item">
                <a href="/usuarios" class="nav-link text-white <?= str_contains($this->context->route, 'usuario') ? 'active' : '' ?>">
                    <i class="fas fa-user-shield me-2"></i>Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a href="/reportes" class="nav-link text-white <?= str_contains($this->context->route, 'reporte') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar me-2"></i>Reportes
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer mt-auto p-3 border-top border-secondary">
        <small class="text-muted">&copy; <?= date('Y') ?> TallerSmart</small>
    </div>
</aside>

<!-- Main Content -->
<div id="main-content" class="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <button id="sidebar-toggle" class="btn btn-outline-secondary me-3">
                <i class="fas fa-bars"></i>
            </button>
            
            <span class="navbar-text ms-auto">
                <i class="fas fa-user-circle me-2"></i>
                <span id="user-name"><?= Yii::$app->user->identity->nombre ?? 'Usuario' ?></span>
                <a href="/auth/logout" class="btn btn-sm btn-outline-danger ms-2">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </span>
        </div>
    </nav>
    
    <!-- Page Content -->
    <main class="container-fluid px-4">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= Yii::$app->session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= Yii::$app->session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?= $content ?>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/js/app.js"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
