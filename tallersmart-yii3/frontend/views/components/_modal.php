<?php

declare(strict_types=1);

/**
 * Componente de Modal Reutilizable
 * 
 * @var string $id - ID del modal
 * @var string $title - Título del modal
 * @var string $content - Contenido del modal
 * @var bool $showFooter - Mostrar footer (default: true)
 * @var string $size - Tamaño: 'sm', 'md', 'lg', 'xl' (default: 'md')
 * @var bool $static - Modal estático (no se cierra al hacer click fuera)
 * @var array $footerButtons - Botones personalizados para el footer
 */

$id = $id ?? 'modal-' . uniqid();
$title = $title ?? 'Modal';
$content = $content ?? '';
$showFooter = $showFooter ?? true;
$size = $size ?? 'md';
$static = $static ?? false;
$footerButtons = $footerButtons ?? [];

$sizeClass = match($size) {
    'sm' => 'modal-sm',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
    default => ''
};

$backdrop = $static ? 'static' : 'true';
$keyboard = $static ? 'false' : 'true';

?>

<div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-labelledby="<?= $id ?>Label" aria-hidden="true" 
     data-bs-backdrop="<?= $backdrop ?>" data-bs-keyboard="<?= $keyboard ?>">
    <div class="modal-dialog <?= $sizeClass ?> modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $id ?>Label">
                    <i class="fas fa-fw me-2"></i>
                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <?= $content ?>
            </div>
            
            <?php if ($showFooter): ?>
            <div class="modal-footer">
                <?php if (empty($footerButtons)): ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cerrar
                    </button>
                    <button type="button" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>Guardar
                    </button>
                <?php else: ?>
                    <?php foreach ($footerButtons as $button): ?>
                        <?= $button ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('<?= $id ?>');
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            modalElement.remove();
        });
    }
});
</script>
