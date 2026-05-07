<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;

/**
 * Widget para mostrar accesos rápidos a operaciones frecuentes
 */
class AccesosRapidosWidget extends Widget
{
    /**
     * @var array Lista de accesos rápidos
     */
    public array $accesos = [];

    /**
     * @var string Título del widget
     */
    public string $titulo = 'Accesos Rápidos';

    /**
     * Mapeo de colores a clases de Tailwind CSS
     */
    private const COLOR_CLASSES = [
        'blue' => [
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-600',
            'hover' => 'hover:bg-blue-100',
        ],
        'green' => [
            'bg' => 'bg-green-50',
            'text' => 'text-green-600',
            'hover' => 'hover:bg-green-100',
        ],
        'purple' => [
            'bg' => 'bg-purple-50',
            'text' => 'text-purple-600',
            'hover' => 'hover:bg-purple-100',
        ],
        'orange' => [
            'bg' => 'bg-orange-50',
            'text' => 'text-orange-600',
            'hover' => 'hover:bg-orange-100',
        ],
        'red' => [
            'bg' => 'bg-red-50',
            'text' => 'text-red-600',
            'hover' => 'hover:bg-red-100',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        try {
            $html = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" role="region" aria-label="' . htmlspecialchars($this->titulo, ENT_QUOTES, 'UTF-8') . '">';
            
            // Header
            $html .= '<div class="px-6 py-4 border-b border-gray-200">';
            $html .= '<h3 class="text-lg font-semibold text-gray-900">' . htmlspecialchars($this->titulo, ENT_QUOTES, 'UTF-8') . '</h3>';
            $html .= '</div>';

            // Contenido
            $html .= '<div class="p-4">';
            
            if (empty($this->accesos)) {
                $html .= '<p class="text-gray-500 text-sm text-center py-4">No hay accesos disponibles</p>';
            } else {
                $html .= '<div class="grid grid-cols-2 gap-3">';
                
                foreach ($this->accesos as $acceso) {
                    $colors = self::COLOR_CLASSES[$acceso['color'] ?? 'blue'] ?? self::COLOR_CLASSES['blue'];
                    
                    $html .= '<a href="' . htmlspecialchars($acceso['url'] ?? '#', ENT_QUOTES, 'UTF-8') . '" '
                        . 'class="' . htmlspecialchars($colors['bg'], ENT_QUOTES, 'UTF-8') . ' '
                        . htmlspecialchars($colors['hover'], ENT_QUOTES, 'UTF-8') . ' '
                        . 'rounded-lg p-4 flex flex-col items-center justify-center text-center transition-colors duration-200"'
                        . 'aria-label="' . htmlspecialchars($acceso['label'] ?? 'Acceso rápido', ENT_QUOTES, 'UTF-8') . '">';
                    
                    // Ícono
                    $html .= '<div class="' . htmlspecialchars($colors['text'], ENT_QUOTES, 'UTF-8') . ' mb-2">';
                    $html .= '<i class="fas fa-' . htmlspecialchars($acceso['icon'] ?? 'fa-link', ENT_QUOTES, 'UTF-8') . ' text-2xl"></i>';
                    $html .= '</div>';
                    
                    // Label
                    $html .= '<span class="text-sm font-medium text-gray-700">' 
                        . htmlspecialchars($acceso['label'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
                    
                    $html .= '</a>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        } catch (\Throwable $e) {
            return $this->renderError();
        }
    }

    /**
     * Renderiza un widget de error
     */
    private function renderError(): string
    {
        return '<div class="bg-white rounded-xl shadow-sm border border-red-200 p-6" role="alert">'
            . '<div class="flex items-center text-red-600">'
            . '<i class="fas fa-exclamation-circle mr-2"></i>'
            . '<span>Error al cargar accesos rápidos</span>'
            . '</div>'
            . '</div>';
    }
}
