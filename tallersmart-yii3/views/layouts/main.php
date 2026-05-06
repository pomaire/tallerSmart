<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $this->title ?? 'TallerSmart' ?> - <?= Yii::$app->params['appName'] ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= \yii\helpers\Url::to('/favicon.ico') ?>" type="image/x-icon">
    
    <!-- Alpine.js para interactividad ligera -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= \yii\helpers\Url::to('/css/app.css') ?>">
    
    <?php $this->head() ?>
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: false, userMenuOpen: false, notificationsOpen: false }">
    
    <!-- Sidebar -->
    <?= $this->render('_sidebar') ?>

    <!-- Contenido principal -->
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        
        <!-- Header -->
        <?= $this->render('_header') ?>

        <!-- Contenido de la página -->
        <main class="flex-1 p-6 overflow-y-auto">
            <!-- Breadcrumbs -->
            <?php if (isset($this->blocks['breadcrumbs'])): ?>
                <nav class="mb-4 text-sm text-gray-600">
                    <?= $this->blocks['breadcrumbs'] ?>
                </nav>
            <?php endif; ?>

            <!-- Alertas y notificaciones flash -->
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="mb-4 p-4 rounded-lg border-l-4 <?= $type === 'success' ? 'bg-green-50 border-green-500 text-green-800' : '' ?> <?= $type === 'error' ? 'bg-red-50 border-red-500 text-red-800' : '' ?> <?= $type === 'warning' ? 'bg-yellow-50 border-yellow-500 text-yellow-800' : '' ?> <?= $type === 'info' ? 'bg-blue-50 border-blue-500 text-blue-800' : '' ?>">
                        <div class="flex items-center">
                            <i class="fas <?= $type === 'success' ? 'fa-check-circle' : '' ?> <?= $type === 'error' ? 'fa-exclamation-circle' : '' ?> <?= $type === 'warning' ? 'fa-exclamation-triangle' : '' ?> <?= $type === 'info' ? 'fa-info-circle' : '' ?> mr-2"></i>
                            <span><?= $message ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <!-- Contenido principal -->
            <?= $content ?>
        </main>

        <!-- Footer -->
        <?= $this->render('_footer') ?>
    </div>

    <!-- Scripts -->
    <script src="<?= \yii\helpers\Url::to('/js/app.js') ?>"></script>
    <?php $this->endBody() ?>
</body>
</html>
