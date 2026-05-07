<?php
/** @var Yiisoft\View\WebView $this */
/** @var app\models\LoginForm $model */

use Yiisoft\Html\Html;
use Yiisoft\Yii\Form\Widget\Form;
use Yiisoft\Yii\Form\Widget\Field;

$this->setTitle('Iniciar Sesión');
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Logo y título -->
        <div class="text-center mb-8">
            <div class="flex justify-center">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-wrench text-3xl text-white"></i>
                </div>
            </div>
            <h1 class="mt-4 text-3xl font-bold text-gray-900">TallerSmart</h1>
            <p class="mt-2 text-sm text-gray-600">Sistema de Gestión de Talleres Mecánicos</p>
        </div>

        <!-- Formulario de login -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Iniciar Sesión</h2>

            <?= Form::widget()
                ->actionUrl(['/site/login'])
                ->id('login-form')
                ->config([
                    'fieldClass' => Field::class,
                    'fieldConfig' => [
                        'inputOptions()' => [['class' => 'form-control']],
                        'labelOptions()' => [['class' => 'form-label']],
                        'errorOptions()' => [['class' => 'form-error']],
                    ],
                    'options' => ['class' => 'space-y-6']
                ])
                ->open() 
            ?>

                <?= Field::widget()
                    ->name('email')
                    ->value($model->getEmail() ?? '')
                    ->textInput([
                        'autofocus' => true,
                        'placeholder' => 'tu@correo.com',
                        'type' => 'email',
                        'required' => true,
                    ])
                    ->label('Correo Electrónico')
                ?>

                <?= Field::widget()
                    ->name('password')
                    ->value('')
                    ->passwordInput([
                        'placeholder' => '••••••••',
                        'required' => true,
                    ])
                    ->label('Contraseña')
                ?>

                <?= Field::widget()
                    ->name('rememberMe')
                    ->value($model->getRememberMe() ? 1 : 0)
                    ->checkbox([
                        'class' => 'rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50'
                    ])
                    ->label('Recordarme')
                ?>

                <div>
                    <button type="submit" class="w-full btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Ingresar
                    </button>
                </div>

            <?= Form::end() ?>

            <!-- Enlaces adicionales -->
            <div class="mt-6 text-center">
                <a href="#" class="text-sm text-blue-600 hover:text-blue-800">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
        </div>

        <!-- Información de prueba -->
        <div class="mt-6 bg-white/50 backdrop-blur-sm rounded-xl p-4 text-center">
            <p class="text-xs text-gray-600">
                <strong>Credenciales de prueba:</strong><br>
                Admin: admin@tallersmart.com / admin123<br>
                Usuario: usuario@tallersmart.com / user123
            </p>
        </div>
    </div>
</div>

<?php
// Estilos específicos para esta página
$css = <<<CSS
<style>
    .form-control {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    .form-error {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        color: #ef4444;
    }
    input[type="checkbox"] {
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
        border: 1px solid #d1d5db;
        color: #3b82f6;
    }
</style>
CSS;

$this->registerCss($css);
?>
