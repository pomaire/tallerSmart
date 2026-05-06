<?php

declare(strict_types=1);

/**
 * @var string $content
 * @var \Yiisoft\View\WebView $this
 */

use Yiisoft\Html\Html;

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => 'utf-8'], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerMetaTag(['name' => 'description', 'content' => 'TallerSmart - Inicio de Sesión']);
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
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .auth-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-wrench"></i>
            <h2 class="mb-0">TallerSmart</h2>
            <p class="mb-0 mt-2 opacity-75">Sistema de Gestión de Talleres</p>
        </div>
        
        <div class="auth-body">
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
        </div>
    </div>
    
    <div class="text-center mt-4 text-white">
        <small>&copy; <?= date('Y') ?> TallerSmart. Todos los derechos reservados.</small>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/js/app.js"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
