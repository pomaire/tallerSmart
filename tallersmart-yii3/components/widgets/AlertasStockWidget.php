<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;

/**
 * Widget para mostrar alertas de stock bajo
 */
class AlertasStockWidget extends Widget
{
    /**
     * @var array Lista de alertas de stock
     */
    public array $alertas = [];

    /**
     * @var string Título del widget
     */
    public string $titulo = 'Alertas de Stock';

    /**
     * @var int Límite de alertas a mostrar
     */
    public int $limite = 10;

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        try {
            $html = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" role="region" aria-label="' . htmlspecialchars($this->titulo, ENT_QUOTES, 'UTF-8') . '">';
            
            // Header
            $html .= '<div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">';
            $html .= '<h3 class="text-lg font-semibold text-gray-900">' . htmlspecialchars($this->titulo, ENT_QUOTES, 'UTF-8') . '</h3>';
            $html .= '<a href="/inventario" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ver inventario →</a>';
            $html .= '</div>';

            // Contenido
            if (empty($this->alertas)) {
                $html .= '<div class="p-6 text-center text-gray-500">';
                $html .= '<i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>';
                $html .= '<p>Todo el inventario está en niveles adecuados</p>';
                $html .= '</div>';
            } else {
                $html .= '<div class="overflow-x-auto">';
                $html .= '<table class="min-w-full divide-y divide-gray-200">';
                $html .= '<thead class="bg-gray-50">';
                $html .= '<tr>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mínimo</th>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody class="bg-white divide-y divide-gray-200">';

                $alertasMostrar = array_slice($this->alertas, 0, $this->limite);
                
                foreach ($alertasMostrar as $alerta) {
                    $esCritico = $alerta['es_critico'] ?? ($alerta['stock_actual'] ?? 1) === 0;
                    $estadoClass = $esCritico ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800';
                    $estadoTexto = $esCritico ? 'Crítico' : 'Bajo';
                    
                    $html .= '<tr class="hover:bg-gray-50">';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap">';
                    $html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($alerta['nombre'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</div>';
                    if (!empty($alerta['codigo'])) {
                        $html .= '<div class="text-xs text-gray-500">' . htmlspecialchars($alerta['codigo'], ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                    $html .= '</td>';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($esCritico ? 'text-red-600 font-bold' : 'text-gray-900') . '">' 
                        . ($alerta['stock_actual'] ?? 'N/A') . '</td>';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">' 
                        . ($alerta['stock_minimo'] ?? 'N/A') . '</td>';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap">';
                    $html .= '<span class="px-2 py-1 text-xs font-medium rounded-full ' . htmlspecialchars($estadoClass, ENT_QUOTES, 'UTF-8') . '">' 
                        . $estadoTexto . '</span>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</div>';
            }

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
            . '<span>Error al cargar alertas de stock</span>'
            . '</div>'
            . '</div>';
    }
}
