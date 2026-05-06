<?php

declare(strict_types=1);

/**
 * Componente de Tarjeta de Estadística
 * 
 * @var string $title - Título/etiqueta de la estadística
 * @var string|number $value - Valor de la estadística
 * @var string|null $icon - Icono FontAwesome (ej: 'fa-users')
 * @var string $color - Color: 'primary', 'success', 'warning', 'danger', 'info' (default: 'primary')
 * @var string|null $subtitle - Subtítulo o descripción adicional
 * @var string|null $trend - Tendencia: 'up', 'down', 'neutral'
 * @var string|null $trendValue - Valor de la tendencia (ej: '+12%')
 * @var string|null $url - URL para hacer la tarjeta clicable
 */

$title = $title ?? '';
$value = $value ?? '0';
$icon = $icon ?? 'fa-chart-bar';
$color = $color ?? 'primary';
$subtitle = $subtitle ?? null;
$trend = $trend ?? null;
$trendValue = $trendValue ?? null;
$url = $url ?? null;

// Colores disponibles
$colors = [
    'primary' => ['bg' => 'bg-primary', 'text' => 'text-primary', 'light' => 'bg-primary-subtle'],
    'success' => ['bg' => 'bg-success', 'text' => 'text-success', 'light' => 'bg-success-subtle'],
    'warning' => ['bg' => 'bg-warning', 'text' => 'text-warning', 'light' => 'bg-warning-subtle'],
    'danger' => ['bg' => 'bg-danger', 'text' => 'text-danger', 'light' => 'bg-danger-subtle'],
    'info' => ['bg' => 'bg-info', 'text' => 'text-info', 'light' => 'bg-info-subtle'],
];

$colorConfig = $colors[$color] ?? $colors['primary'];

// Formatear valor si es numérico
if (is_numeric($value)) {
    if ($value >= 1000000) {
        $formattedValue = '$' . number_format($value / 1000000, 1) . 'M';
    } elseif ($value >= 1000) {
        $formattedValue = number_format($value);
    } else {
        $formattedValue = $value;
    }
} else {
    $formattedValue = $value;
}

$cardContent = '
<div class="card stat-card ' . $color . ' shadow-sm h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted text-uppercase mb-1 stat-label">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>
                <h3 class="mb-0 stat-value">' . $formattedValue . '</h3>';

if ($subtitle) {
    $cardContent .= '<small class="text-muted">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</small>';
}

if ($trend && $trendValue) {
    $trendIcon = $trend === 'up' ? 'fa-arrow-up' : ($trend === 'down' ? 'fa-arrow-down' : 'fa-minus');
    $trendColor = $trend === 'up' ? 'success' : ($trend === 'down' ? 'danger' : 'secondary');
    $cardContent .= '
                <div class="mt-2">
                    <span class="badge bg-' . $trendColor . '">
                        <i class="fas ' . $trendIcon . ' me-1"></i>' . htmlspecialchars($trendValue, ENT_QUOTES, 'UTF-8') . '
                    </span>
                    <span class="text-muted ms-1">vs mes anterior</span>
                </div>';
}

$cardContent .= '
            </div>
            <div class="stat-icon ' . $colorConfig['text'] . '">
                <i class="fas ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' fa-3x"></i>
            </div>
        </div>
    </div>
</div>';

if ($url) {
    echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="text-decoration-none">' . $cardContent . '</a>';
} else {
    echo $cardContent;
}
