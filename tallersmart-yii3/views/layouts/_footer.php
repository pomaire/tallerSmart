<!-- Footer de la aplicación -->
<footer class="bg-white border-t border-gray-200 py-4 px-6">
    <div class="flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0">
        <!-- Información de copyright -->
        <div class="text-sm text-gray-600">
            &copy; <?= date('Y') ?> <?= Yii::$app->params['appName'] ?>. Todos los derechos reservados.
        </div>

        <!-- Versión y enlaces útiles -->
        <div class="flex items-center space-x-4 text-sm text-gray-500">
            <span>Versión <?= Yii::$app->params['appVersion'] ?></span>
            <span class="hidden md:inline">|</span>
            <a href="<?= \yii\helpers\Url::to(['/site/manual']) ?>" target="_blank" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-book mr-1"></i> Manual
            </a>
            <span class="hidden md:inline">|</span>
            <a href="mailto:<?= Yii::$app->params['supportEmail'] ?>" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-envelope mr-1"></i> Soporte
            </a>
        </div>
    </div>
</footer>
