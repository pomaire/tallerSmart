<?php

declare(strict_types=1);

/**
 * Componente de Card Reutilizable
 * 
 * @var string $title - Título de la card
 * @var string|null $subtitle - Subtítulo opcional
 * @var string $content - Contenido principal
 * @var string|null $footer - Contenido del footer
 * @var array $actions - Botones de acción en el header
 * @var string $size - Tamaño: 'sm', 'md', 'lg', 'full' (default: 'md')
 * @var string|null $icon - Icono FontAwesome (ej: 'fa-users')
 * @var bool $collapsible - Si es colapsable (default: false)
 * @var bool $collapsed - Iniciar colapsado (default: false)
 */

$title = $title ?? '';
$subtitle = $subtitle ?? null;
$content = $content ?? '';
$footer = $footer ?? null;
$actions = $actions ?? [];
$size = $size ?? 'md';
$icon = $icon ?? null;
$collapsible = $collapsible ?? false;
$collapsed = $collapsed ?? false;

$sizeClass = match($size) {
    'sm' => 'col-md-6 col-lg-4',
    'md' => 'col-md-8 col-lg-6',
    'lg' => 'col-md-10 col-lg-8',
    'full' => 'col-12',
    default => 'col-12'
};

$cardId = 'card-' . uniqid();
?>

<div class="card shadow-sm mb-4 <?= $sizeClass ?>">
    <?php if ($title || !empty($actions)): ?>
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <?php if ($icon): ?>
                <i class="fas <?= $icon ?> me-2 text-primary"></i>
            <?php endif; ?>
            
            <?php if ($collapsible): ?>
                <a href="#<?= $cardId ?>Body" data-bs-toggle="collapse" class="text-decoration-none text-dark">
                    <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
                    <i class="fas fa-chevron-down ms-2 collapse-icon"></i>
                </a>
            <?php else: ?>
                <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
            
            <?php if ($subtitle): ?>
                <br><small class="text-muted"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($actions)): ?>
            <div class="btn-group btn-group-sm">
                <?php foreach ($actions as $action): ?>
                    <?= $action ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div id="<?= $cardId ?>Body" class="card-body <?= $collapsed ? 'collapse' : '' ?> <?= $collapsible && $collapsed ? 'show' : '' ?>">
        <?= $content ?>
    </div>
    
    <?php if ($footer): ?>
    <div class="card-footer bg-light">
        <?= $footer ?>
    </div>
    <?php endif; ?>
</div>
