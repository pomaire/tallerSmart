<?php

declare(strict_types=1);

/**
 * Componente de Tabla Reutilizable
 * 
 * @var array $columns - Columnas [['label' => 'Nombre', 'attribute' => 'nombre'], ...]
 * @var array $data - Datos de la tabla (array de arrays o objetos)
 * @var string|null $emptyMessage - Mensaje cuando no hay datos
 * @var bool $striped - Filas alternas (default: true)
 * @var bool $hover - Efecto hover (default: true)
 * @var bool $bordered - Bordes (default: false)
 * @var bool $sm - Tamaño pequeño (default: false)
 * @var array $actions - Botones de acción por fila
 * @var callable|null $rowClass - Función para clase personalizada por fila
 * @var bool $responsive - Tabla responsive (default: true)
 * @var string|null $id - ID de la tabla
 */

$columns = $columns ?? [];
$data = $data ?? [];
$emptyMessage = $emptyMessage ?? 'No se encontraron registros';
$striped = $striped ?? true;
$hover = $hover ?? true;
$bordered = $bordered ?? false;
$sm = $sm ?? false;
$actions = $actions ?? [];
$rowClass = $rowClass ?? null;
$responsive = $responsive ?? true;
$id = $id ?? 'table-' . uniqid();

$tableClasses = ['table'];
if ($striped) $tableClasses[] = 'table-striped';
if ($hover) $tableClasses[] = 'table-hover';
if ($bordered) $tableClasses[] = 'table-bordered';
if ($sm) $tableClasses[] = 'table-sm';

$tableClass = implode(' ', $tableClasses);
?>

<?php if ($responsive): ?>
<div class="table-responsive">
<?php endif; ?>

<table id="<?= $id ?>" class="<?= $tableClass ?>">
    <?php if (!empty($columns)): ?>
    <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th scope="col" <?= !empty($column['sortable']) ? 'data-sortable' : '' ?>>
                    <?= htmlspecialchars($column['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </th>
            <?php endforeach; ?>
            
            <?php if (!empty($actions)): ?>
                <th scope="col" class="text-end">Acciones</th>
            <?php endif; ?>
        </tr>
    </thead>
    <?php endif; ?>
    
    <tbody>
        <?php if (empty($data)): ?>
            <tr>
                <td colspan="<?= count($columns) + (empty($actions) ? 0 : 1) ?>" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <br>
                    <?= htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $index => $row): ?>
                <tr <?= is_callable($rowClass) ? 'class="' . call_user_func($rowClass, $row, $index) . '"' : '' ?>>
                    <?php foreach ($columns as $column): ?>
                        <td>
                            <?php
                            $attribute = $column['attribute'] ?? null;
                            $value = '';
                            
                            if (is_callable($column['value'] ?? null)) {
                                $value = call_user_func($column['value'], $row, $index);
                            } elseif ($attribute && is_array($row) && isset($row[$attribute])) {
                                $value = $row[$attribute];
                            } elseif ($attribute && is_object($row) && isset($row->$attribute)) {
                                $value = $row->$attribute;
                            }
                            
                            // Formatear según tipo
                            if (!empty($column['format'])) {
                                switch ($column['format']) {
                                    case 'currency':
                                        $value = '$' . number_format((float)$value, 2);
                                        break;
                                    case 'date':
                                        $value = date('d/m/Y', strtotime($value));
                                        break;
                                    case 'datetime':
                                        $value = date('d/m/Y H:i', strtotime($value));
                                        break;
                                    case 'boolean':
                                        $value = $value ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>';
                                        break;
                                    case 'status':
                                        $statusColors = [
                                            'active' => 'success',
                                            'pending' => 'warning',
                                            'inactive' => 'danger',
                                            'completed' => 'info',
                                            'cancelled' => 'secondary'
                                        ];
                                        $color = $statusColors[$value] ?? 'secondary';
                                        $value = '<span class="badge bg-' . $color . '">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</span>';
                                        break;
                                }
                            }
                            
                            echo $value;
                            ?>
                        </td>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($actions)): ?>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <?php foreach ($actions as $action): ?>
                                    <?php
                                    $url = $action['url'] ?? '#';
                                    $label = $action['label'] ?? '';
                                    $icon = $action['icon'] ?? '';
                                    $class = $action['class'] ?? 'btn-outline-secondary';
                                    
                                    if (is_callable($url)) {
                                        $url = call_user_func($url, $row);
                                    }
                                    ?>
                                    <a href="<?= $url ?>" class="btn <?= $class ?>" 
                                       <?= !empty($action['confirm']) ? 'onclick="return confirm(\'' . htmlspecialchars($action['confirm'], ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>
                                       <?= !empty($action['target']) ? 'target="' . $action['target'] . '"' : '' ?>>
                                        <?php if ($icon): ?>
                                            <i class="fas <?= $icon ?>"></i>
                                        <?php endif; ?>
                                        <?php if ($label): ?>
                                            <?= $label ?>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($responsive): ?>
</div>
<?php endif; ?>
