<!-- Header superior -->
<header class="bg-white shadow-sm border-b border-gray-200 h-16">
    <div class="h-full px-4 flex items-center justify-between">
        <!-- Izquierda: Toggle sidebar móvil y título de página -->
        <div class="flex items-center space-x-4">
            <!-- Botón toggle sidebar (móvil) -->
            <button 
                @click="sidebarOpen = true" 
                class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none"
            >
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <!-- Título de la página (dinámico) -->
            <h1 class="text-xl font-semibold text-gray-800 hidden sm:block">
                <?= $this->title ?? 'Dashboard' ?>
            </h1>
        </div>

        <!-- Derecha: Acciones del header -->
        <div class="flex items-center space-x-4">
            <!-- Botón de notificaciones -->
            <div class="relative" x-data="{ count: 3 }">
                <button 
                    @click="notificationsOpen = !notificationsOpen" 
                    class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                    <i class="fas fa-bell text-xl"></i>
                    <?php if (!Yii::$app->user->isGuest): ?>
                        <span 
                            x-show="count > 0" 
                            class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                            x-text="count"
                        ></span>
                    <?php endif; ?>
                </button>

                <!-- Dropdown de notificaciones -->
                <div 
                    x-show="notificationsOpen" 
                    @click.away="notificationsOpen = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                    style="display: none;"
                >
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">Notificaciones</h3>
                            <a href="<?= \yii\helpers\Url::to(['/notificacion/index']) ?>" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <!-- Notificaciones de ejemplo -->
                        <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-calendar-check text-blue-500 mt-1"></i>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">Nueva cita programada</p>
                                    <p class="text-xs text-gray-500 mt-1">Hace 5 minutos</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-box text-yellow-500 mt-1"></i>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">Stock bajo en repuestos</p>
                                    <p class="text-xs text-gray-500 mt-1">Hace 1 hora</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 hover:bg-gray-50 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-wrench text-green-500 mt-1"></i>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">Orden de servicio completada</p>
                                    <p class="text-xs text-gray-500 mt-1">Hace 2 horas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Separador vertical -->
            <div class="h-8 w-px bg-gray-300"></div>

            <!-- Menú de usuario -->
            <div class="relative" x-data="{ open: false }">
                <button 
                    @click="open = !open" 
                    class="flex items-center space-x-2 focus:outline-none"
                >
                    <?php if (!Yii::$app->user->isGuest): ?>
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm">
                            <?= strtoupper(substr(Yii::$app->user->identity->nombre, 0, 1)) ?><?= strtoupper(substr(Yii::$app->user->identity->apellido, 0, 1)) ?>
                        </div>
                        <span class="text-sm font-medium text-gray-700 hidden md:block">
                            <?= Yii::$app->user->identity->nombre ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>

                <!-- Dropdown de usuario -->
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                    style="display: none;"
                >
                    <div class="p-3 border-b border-gray-200">
                        <?php if (!Yii::$app->user->isGuest): ?>
                            <p class="text-sm font-medium text-gray-800"><?= Yii::$app->user->identity->nombreCompleto ?></p>
                            <p class="text-xs text-gray-500 truncate"><?= Yii::$app->user->identity->email ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="py-1">
                        <a href="<?= \yii\helpers\Url::to(['/usuario/profile']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i>Mi Perfil
                        </a>
                        <a href="<?= \yii\helpers\Url::to(['/site/manual']) ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-book mr-2"></i>Manual
                        </a>
                        <hr class="my-1">
                        <a href="<?= \yii\helpers\Url::to(['/site/logout']) ?>" data-method="post" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
