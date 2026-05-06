<!-- Sidebar de navegación -->
<aside 
    class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transform transition-transform duration-300 ease-in-out md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    x-show="true"
    x-transition:enter="transition ease-in-out duration-300"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in-out duration-300"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
>
    <!-- Logo y título -->
    <div class="flex items-center justify-between h-16 px-4 bg-slate-800 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <i class="fas fa-wrench text-2xl text-blue-400"></i>
            <span class="text-xl font-bold"><?= Yii::$app->params['appName'] ?></span>
        </div>
        <!-- Botón cerrar sidebar (móvil) -->
        <button 
            @click="sidebarOpen = false" 
            class="md:hidden text-gray-400 hover:text-white focus:outline-none"
        >
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Menú de navegación -->
    <nav class="mt-4 px-2 overflow-y-auto h-[calc(100vh-4rem)]">
        <?php foreach (Yii::$app->params['menuItems'] as $menuItem): ?>
            <?php if (isset($menuItem['items'])): ?>
                <!-- Menú con submenús -->
                <div class="mb-2" x-data="{ open: false }">
                    <button 
                        @click="open = !open" 
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left text-gray-300 hover:bg-slate-800 hover:text-white transition-colors duration-150 group"
                    >
                        <div class="flex items-center space-x-3">
                            <i class="fas <?= $menuItem['icon'] ?? 'fa-circle' ?> w-5 text-center text-gray-400 group-hover:text-white"></i>
                            <span class="font-medium"><?= $menuItem['label'] ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    
                    <!-- Submenú -->
                    <div 
                        x-show="open" 
                        x-collapse
                        class="mt-1 ml-4 space-y-1"
                    >
                        <?php foreach ($menuItem['items'] as $subItem): ?>
                            <a 
                                href="<?= \yii\helpers\Url::to($subItem['url']) ?>" 
                                class="flex items-center px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-slate-800 hover:text-white transition-colors duration-150"
                            >
                                <i class="fas <?= $subItem['icon'] ?? 'fa-circle' ?> w-4 text-center mr-2"></i>
                                <span><?= $subItem['label'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Menú simple sin submenús -->
                <a 
                    href="<?= \yii\helpers\Url::to($menuItem['url']) ?>" 
                    class="flex items-center px-3 py-2.5 mb-1 rounded-lg text-gray-300 hover:bg-slate-800 hover:text-white transition-colors duration-150 group"
                    <?= isset($menuItem['target']) ? 'target="' . $menuItem['target'] . '"' : '' ?>
                >
                    <i class="fas <?= $menuItem['icon'] ?? 'fa-circle' ?> w-5 text-center text-gray-400 group-hover:text-white"></i>
                    <span class="font-medium ml-3"><?= $menuItem['label'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Información de usuario en sidebar -->
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-slate-800 border-t border-slate-700">
        <?php if (!Yii::$app->user->isGuest): ?>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                    <?= strtoupper(substr(Yii::$app->user->identity->nombre, 0, 1)) ?><?= strtoupper(substr(Yii::$app->user->identity->apellido, 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= Yii::$app->user->identity->nombreCompleto ?></p>
                    <p class="text-xs text-gray-400 truncate"><?= Yii::$app->user->identity->email ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Overlay para móvil -->
<div 
    x-show="sidebarOpen" 
    @click="sidebarOpen = false"
    x-transition:enter="ease-in-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in-out duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
></div>
