<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;

/**
 * Widget para mostrar órdenes activas
 */
class OrdenesActivasWidget extends Widget
{
    /**
     * @var array Lista de órdenes activas
     */
    public array $ordenes = [];

    /**
     * @var string Título del widget
     */
    public string $titulo = 'Órdenes Activas';

    /**
     * @var int Límite de órdenes a mostrar
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
            $html .= '<a href="/orden-servicio" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ver todas →</a>';
            $html .= '</div>';

            // Contenido
            if (empty($this->ordenes)) {
                $html .= '<div class="p-6 text-center text-gray-500">';
                $html .= '<i class="fas fa-clipboard-check text-4xl text-gray-300 mb-3"></i>';
                $html .= '<p>No hay órdenes activas en este momento</p>';
                $html .= '</div>';
            } else {
                $html .= '<div class="overflow-x-auto">';
                $html .= '<table class="min-w-full divide-y divide-gray-200">';
                $html .= '<thead class="bg-gray-50">';
                $html .= '<tr>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehículo</th>';
                $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody class="bg-white divide-y divide-gray-200">';

                $ordenesMostrar = array_slice($this->ordenes, 0, $this->limite);
                
                foreach ($ordenesMostrar as $orden) {
                    $estadoClass = $this->getEstadoClass($orden['estado'] ?? '');
                    
                    $html .= '<tr class="hover:bg-gray-50">';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">#' 
                        . htmlspecialchars($orden['numero_orden'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' 
                        . htmlspecialchars($orden['cliente'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">' 
                        . htmlspecialchars($orden['vehiculo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td class="px-6 py-4 whitespace-nowrap">';
                    $html .= '<span class="px-2 py-1 text-xs font-medium rounded-full ' . htmlspecialchars($estadoClass, ENT_QUOTES, 'UTF-8') . '">' 
                        . htmlspecialchars(ucfirst(str_replace('_', ' ', $orden['estado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</span>';
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
     * Obtiene las clases CSS según el estado de la orden
     */
    private function getEstadoClass(string $estado): string
    {
        return match ($estado) {
            'abierto' => 'bg-blue-100 text-blue-800',
            'en_progreso' => 'bg-purple-100 text-purple-800',
            'esperando_repuestos' => 'bg-orange-100 text-orange-800',
            'listo_para_entrega' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Renderiza un widget de error
     */
    private function renderError(): string
    {
        return '<div class="bg-white rounded-xl shadow-sm border border-red-200 p-6" role="alert">'
            . '<div class="flex items-center text-red-600">'
            . '<i class="fas fa-exclamation-circle mr-2"></i>'
            . '<span>Error al cargar órdenes activas</span>'
            . '</div>'
            . '</div>';
    }
}
