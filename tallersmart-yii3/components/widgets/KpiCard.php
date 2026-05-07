<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;

/**
 * Widget para mostrar tarjetas de KPI en el dashboard
 */
class KpiCard extends Widget
{
    /**
     * @var string Título del KPI
     */
    public string $titulo = '';

    /**
     * @var mixed Valor del KPI (puede ser número, string o HTML)
     */
    public mixed $valor = 0;

    /**
     * @var string Ícono de Font Awesome (sin el prefijo 'fa-')
     */
    public string $icono = 'chart-bar';

    /**
     * @var string Color del widget ('primary', 'accent', 'destructive', 'green', 'blue', 'orange', 'purple')
     */
    public string $color = 'primary';

    /**
     * @var string|null Subtítulo o descripción adicional
     */
    public ?string $subtitulo = null;

    /**
     * @var bool Mostrar indicador visual crítico (badge rojo)
     */
    public bool $mostrarCritico = false;

    /**
     * @var string|null URL para enlace opcional
     */
    public ?string $url = null;

    /**
     * @var string Texto alternativo para accesibilidad
     */
    public string $ariaLabel = '';

    /**
     * Mapeo de colores a clases de Tailwind CSS
     */
    private const COLOR_CLASSES = [
        'primary' => [
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-600',
            'border' => 'border-blue-500',
        ],
        'accent' => [
            'bg' => 'bg-orange-100',
            'text' => 'text-orange-600',
            'border' => 'border-orange-500',
        ],
        'destructive' => [
            'bg' => 'bg-red-100',
            'text' => 'text-red-600',
            'border' => 'border-red-500',
        ],
        'green' => [
            'bg' => 'bg-green-100',
            'text' => 'text-green-600',
            'border' => 'border-green-500',
        ],
        'blue' => [
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-600',
            'border' => 'border-blue-500',
        ],
        'orange' => [
            'bg' => 'bg-orange-100',
            'text' => 'text-orange-600',
            'border' => 'border-orange-500',
        ],
        'purple' => [
            'bg' => 'bg-purple-100',
            'text' => 'text-purple-600',
            'border' => 'border-purple-500',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        try {
            $colors = self::COLOR_CLASSES[$this->color] ?? self::COLOR_CLASSES['primary'];
            $ariaLabel = $this->ariaLabel ?: "{$this->titulo}: {$this->valor}";

            $html = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow" role="region" aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '">';
            
            // Header con ícono y badge crítico
            $html .= '<div class="flex items-center justify-between mb-4">';
            $html .= '<div class="' . htmlspecialchars($colors['bg'], ENT_QUOTES, 'UTF-8') . ' rounded-lg p-3">';
            $html .= '<i class="fas fa-' . htmlspecialchars($this->icono, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($colors['text'], ENT_QUOTES, 'UTF-8') . ' w-6 h-6"></i>';
            $html .= '</div>';
            
            if ($this->mostrarCritico) {
                $html .= '<span class="px-2 py-1 text-xs font-bold bg-red-600 text-white rounded-full animate-pulse">CRÍTICO</span>';
            }
            
            $html .= '</div>';

            // Título
            $html .= '<h3 class="text-gray-600 text-sm font-medium">' . htmlspecialchars($this->titulo, ENT_QUOTES, 'UTF-8') . '</h3>';

            // Valor
            $html .= '<p class="text-3xl font-bold text-gray-900 mt-1">' . (is_string($this->valor) ? $this->valor : htmlspecialchars((string)$this->valor, ENT_QUOTES, 'UTF-8')) . '</p>';

            // Subtítulo
            if ($this->subtitulo !== null) {
                $html .= '<p class="text-xs text-gray-500 mt-2">' . htmlspecialchars($this->subtitulo, ENT_QUOTES, 'UTF-8') . '</p>';
            }

            // Enlace opcional
            if ($this->url !== null) {
                $html .= '<a href="' . htmlspecialchars($this->url, ENT_QUOTES, 'UTF-8') . '" class="mt-3 inline-block text-sm text-blue-600 hover:text-blue-700 font-medium">Ver detalles →</a>';
            }

            $html .= '</div>';

            return $html;
        } catch (\Throwable $e) {
            // Manejo de errores: mostrar widget de error sin romper el dashboard
            return $this->renderError();
        }
    }

    /**
     * Renderiza un widget de error cuando ocurre una excepción
     */
    private function renderError(): string
    {
        return '<div class="bg-white rounded-xl shadow-sm border border-red-200 p-6" role="alert" aria-label="Error al cargar KPI">'
            . '<div class="flex items-center justify-between mb-4">'
            . '<div class="bg-red-100 rounded-lg p-3">'
            . '<i class="fas fa-exclamation-circle text-red-600 w-6 h-6"></i>'
            . '</div>'
            . '</div>'
            . '<h3 class="text-gray-600 text-sm font-medium">' . htmlspecialchars($this->titulo, ENT_QUOTES, 'UTF-8') . '</h3>'
            . '<p class="text-red-600 text-sm mt-2">No se pudo cargar este indicador</p>'
            . '</div>';
    }
}
