<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Categoria;
use app\models\Servicio;
use app\models\InventoryItem;
use app\models\AuditLog;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\db\Expression;
use Yii;

/**
 * Controlador REST API para Categorías
 */
class CategoriaController extends BaseController
{
    public $modelClass = Categoria::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = [
            'index', 'view', 'create', 'update', 'delete',
            'duplicate', 'merge', 'export', 'import',
            'stats', 'items', 'toggle-estado'
        ];
        return $behaviors;
    }

    /**
     * Listar categorías con paginación y filtros
     * GET /api/categoria
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Categoria::find()
            ->select([
                'categoria.*',
                '(SELECT COUNT(*) FROM servicio WHERE servicio.categoriaId = categoria.id) as servicios_count',
                '(SELECT COUNT(*) FROM inventory_item WHERE inventory_item.categoria_id = categoria.id) as inventario_count'
            ])
            ->orderBy(['orden' => SORT_ASC, 'nombre' => SORT_ASC]);

        $request = Yii::$app->request;
        $search = $request->get('search');
        $activo = $request->get('activo');
        $tipo = $request->get('tipo');
        $padreId = $request->get('padreId');

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'nombre', $search],
                ['like', 'descripcion', $search],
            ]);
        }

        if ($activo !== null) {
            $query->andWhere(['activo' => (bool)$activo]);
        }

        if ($tipo) {
            $query->andWhere(['tipo' => $tipo]);
        }

        if ($padreId !== null) {
            if ($padreId === '') {
                $query->andWhere(['padreId' => null]);
            } else {
                $query->andWhere(['padreId' => (int)$padreId]);
            }
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);
    }

    /**
     * Obtener categoría por ID
     * GET /api/categoria/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        return [
            'success' => true,
            'data' => $model,
        ];
    }

    /**
     * Crear nueva categoría
     * POST /api/categoria
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        
        // Validar nombre único
        $nombre = $request->post('nombre');
        if ($this->existeNombre($nombre)) {
            throw new BadRequestHttpException(json_encode([
                'nombre' => ['El nombre "' . $nombre . '" ya existe. Use otro nombre.']
            ]));
        }

        $model = new Categoria();
        $model->nombre = $nombre;
        $model->descripcion = $request->post('descripcion');
        $model->tipo = $request->post('tipo', 'ambos');
        $model->icono = $request->post('icono');
        $model->color = $request->post('color', '#3B82F6');
        $model->orden = (int)$request->post('orden', 0);
        $model->padreId = $request->post('padreId') ?: null;
        $model->activo = $request->post('activo', true);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        // Registrar auditoría
        $this->registrarAuditoria('CREATE', $model);

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Categoría creada correctamente',
        ];
    }

    /**
     * Actualizar categoría existente
     * PUT/PATCH /api/categoria/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $nombre = $request->post('nombre', $model->nombre);
        
        // Validar nombre único (excluyendo el actual)
        if ($nombre !== $model->nombre && $this->existeNombre($nombre, $id)) {
            throw new BadRequestHttpException(json_encode([
                'nombre' => ['El nombre "' . $nombre . '" ya existe. Use otro nombre.']
            ]));
        }

        $oldAttributes = $model->getOldAttributes();

        $model->nombre = $nombre;
        $model->descripcion = $request->post('descripcion', $model->descripcion);
        $model->tipo = $request->post('tipo', $model->tipo);
        $model->icono = $request->post('icono', $model->icono);
        $model->color = $request->post('color', $model->color);
        $model->orden = (int)$request->post('orden', $model->orden);
        $model->padreId = $request->post('padreId') ?: null;
        $model->activo = $request->post('activo', $model->activo);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        // Registrar auditoría si hubo cambios
        $newAttributes = $model->getAttributes();
        if ($oldAttributes !== $newAttributes) {
            $this->registrarAuditoria('UPDATE', $model, $oldAttributes, $newAttributes);
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Categoría actualizada correctamente',
        ];
    }

    /**
     * Eliminar categoría (solo si no tiene items asociados)
     * DELETE /api/categoria/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);

        // Verificar si tiene servicios asociados
        $serviciosCount = Servicio::find()->where(['categoriaId' => $id])->count();
        if ($serviciosCount > 0) {
            throw new BadRequestHttpException(json_encode([
                'error' => 'No se puede eliminar la categoría porque tiene ' . $serviciosCount . ' servicio(s) asociado(s).'
            ]));
        }

        // Verificar si tiene items de inventario asociados
        $inventarioCount = InventoryItem::find()->where(['categoria_id' => $id])->count();
        if ($inventarioCount > 0) {
            throw new BadRequestHttpException(json_encode([
                'error' => 'No se puede eliminar la categoría porque tiene ' . $inventarioCount . ' item(s) de inventario asociado(s).'
            ]));
        }

        $model->delete();

        // Registrar auditoría
        $this->registrarAuditoria('DELETE', null, ['id' => $id, 'nombre' => $model->nombre]);

        return [
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
        ];
    }

    /**
     * Duplicar categoría como plantilla
     * POST /api/categoria/{id}/duplicate
     */
    public function actionDuplicate($id): array
    {
        $original = $this->findModel($id);

        $model = new Categoria();
        $model->nombre = $original->nombre . ' (Copia)';
        $model->descripcion = $original->descripcion;
        $model->tipo = $original->tipo;
        $model->icono = $original->icono;
        $model->color = $original->color;
        $model->orden = $original->orden + 1;
        $model->padreId = $original->padreId;
        $model->activo = false; // Inactiva por defecto

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        $this->registrarAuditoria('DUPLICATE', $model, ['original_id' => $id]);

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Categoría duplicada correctamente',
        ];
    }

    /**
     * Merge de dos categorías
     * POST /api/categoria/merge
     */
    public function actionMerge(): array
    {
        $request = Yii::$app->request;
        $origenId = (int)$request->post('origenId');
        $destinoId = (int)$request->post('destinoId');

        if (!$origenId || !$destinoId || $origenId === $destinoId) {
            throw new BadRequestHttpException(json_encode([
                'error' => 'Debe seleccionar dos categorías diferentes'
            ]));
        }

        $origen = $this->findModel($origenId);
        $destino = $this->findModel($destinoId);

        // Mover servicios
        Servicio::updateAll(['categoriaId' => $destinoId], ['categoriaId' => $origenId]);
        
        // Mover items de inventario
        InventoryItem::updateAll(['categoria_id' => $destinoId], ['categoria_id' => $origenId]);

        // Eliminar categoría origen
        $origen->delete();

        $this->registrarAuditoria('MERGE', $destino, [
            'origen_id' => $origenId,
            'origen_nombre' => $origen->nombre
        ]);

        return [
            'success' => true,
            'data' => $destino,
            'message' => 'Categorías fusionadas correctamente',
        ];
    }

    /**
     * Exportar categorías a CSV
     * GET /api/categoria/export
     */
    public function actionExport()
    {
        $categorias = Categoria::find()
            ->orderBy(['orden' => SORT_ASC, 'nombre' => SORT_ASC])
            ->all();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="categorias_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, ['ID', 'Nombre', 'Descripción', 'Tipo', 'Icono', 'Color', 'Orden', 'Activo', 'Padre ID', 'Creado', 'Actualizado']);

        foreach ($categorias as $cat) {
            fputcsv($output, [
                $cat->id,
                $cat->nombre,
                $cat->descripcion,
                $cat->tipo,
                $cat->icono,
                $cat->color,
                $cat->orden,
                $cat->activo ? 'Sí' : 'No',
                $cat->padreId ?? '',
                $cat->created_at,
                $cat->updated_at
            ]);
        }

        fclose($output);
        Yii::$app->end();
    }

    /**
     * Importar categorías desde CSV
     * POST /api/categoria/import
     */
    public function actionImport(): array
    {
        $request = Yii::$app->request;
        $file = $request->post('file');
        $sobrescribir = (bool)$request->post('sobrescribir', false);

        if (!$file) {
            throw new BadRequestHttpException(json_encode([
                'error' => 'No se proporcionó archivo CSV'
            ]));
        }

        // Decodificar base64 si viene así
        if (strpos($file, 'base64') !== false) {
            $file = base64_decode(preg_replace('/^data:text\/csv;base64,/', '', $file));
        }

        $lines = str_getcsv($file, "\n");
        $creadas = 0;
        $omitidas = 0;
        $errores = [];

        // Saltar encabezado
        array_shift($lines);

        foreach ($lines as $lineNum => $line) {
            $data = str_getcsv($line);
            if (count($data) < 2) continue;

            $nombre = trim($data[1] ?? '');
            $descripcion = trim($data[2] ?? '');

            if (empty($nombre)) {
                $errores[] = "Línea " . ($lineNum + 2) . ": Nombre vacío";
                continue;
            }

            // Verificar duplicados
            if ($this->existeNombre($nombre)) {
                if ($sobrescribir) {
                    $existing = Categoria::findOne(['nombre' => $nombre]);
                    if ($existing) {
                        $existing->descripcion = $descripcion;
                        $existing->save();
                        $creadas++;
                    }
                } else {
                    $omitidas++;
                }
                continue;
            }

            $model = new Categoria();
            $model->nombre = $nombre;
            $model->descripcion = $descripcion;
            $model->tipo = trim($data[3] ?? 'ambos');
            $model->icono = trim($data[4] ?? null);
            $model->color = trim($data[5] ?? '#3B82F6');
            $model->orden = (int)($data[6] ?? 0);
            $model->activo = (trim($data[7] ?? 'Sí') === 'Sí');

            if ($model->save()) {
                $creadas++;
            } else {
                $errores[] = "Línea " . ($lineNum + 2) . ": " . json_encode($model->getErrors());
            }
        }

        return [
            'success' => true,
            'data' => [
                'creadas' => $creadas,
                'omitidas' => $omitidas,
                'errores' => $errores
            ],
            'message' => "Importación completada: {$creadas} creadas, {$omitidas} omitidas",
        ];
    }

    /**
     * Obtener estadísticas de uso de una categoría
     * GET /api/categoria/{id}/stats
     */
    public function actionStats($id): array
    {
        $model = $this->findModel($id);

        // Contar servicios
        $serviciosCount = Servicio::find()->where(['categoriaId' => $id])->count();
        
        // Contar items de inventario
        $inventarioCount = InventoryItem::find()->where(['categoria_id' => $id])->count();

        // Estadísticas temporales (últimos 30 días)
        $fechaInicio = date('Y-m-d', strtotime('-30 days'));
        
        $serviciosRecientes = Servicio::find()
            ->where(['categoriaId' => $id])
            ->andWhere(['>=', 'createdAt', $fechaInicio])
            ->count();

        return [
            'success' => true,
            'data' => [
                'categoria_id' => $id,
                'nombre' => $model->nombre,
                'total_servicios' => $serviciosCount,
                'total_inventario' => $inventarioCount,
                'servicios_ultimos_30_dias' => $serviciosRecientes,
                'tendencia' => $serviciosRecientes > 0 ? 'creciente' : 'estable'
            ]
        ];
    }

    /**
     * Obtener items (servicios/inventario) de una categoría
     * GET /api/categoria/{id}/items
     */
    public function actionItems($id): array
    {
        $model = $this->findModel($id);

        $servicios = Servicio::find()
            ->where(['categoriaId' => $id])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $inventario = InventoryItem::find()
            ->where(['categoria_id' => $id])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        return [
            'success' => true,
            'data' => [
                'categoria' => $model,
                'servicios' => $servicios,
                'inventario' => $inventario
            ]
        ];
    }

    /**
     * Toggle estado activo/inactivo
     * PATCH /api/categoria/{id}/toggle-estado
     */
    public function actionToggleEstado($id): array
    {
        $model = $this->findModel($id);
        $model->activo = !$model->activo;

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        $this->registrarAuditoria('TOGGLE_ESTADO', $model, ['activo' => !$model->activo]);

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Estado actualizado correctamente',
        ];
    }

    /**
     * Mover items entre categorías
     * POST /api/categoria/move-items
     */
    public function actionMoveItems(): array
    {
        $request = Yii::$app->request;
        $itemIds = $request->post('itemIds', []);
        $tipo = $request->post('tipo', 'servicio'); // 'servicio' o 'inventario'
        $categoriaDestinoId = (int)$request->post('categoriaDestinoId');

        if (!$categoriaDestinoId || empty($itemIds)) {
            throw new BadRequestHttpException(json_encode([
                'error' => 'Debe especificar categoría destino y items'
            ]));
        }

        $destino = $this->findModel($categoriaDestinoId);
        $movidos = 0;

        if ($tipo === 'servicio') {
            $movidos = Servicio::updateAll(
                ['categoriaId' => $categoriaDestinoId],
                ['id' => $itemIds]
            );
        } else {
            $movidos = InventoryItem::updateAll(
                ['categoria_id' => $categoriaDestinoId],
                ['id' => $itemIds]
            );
        }

        $this->registrarAuditoria('MOVE_ITEMS', $destino, [
            'tipo' => $tipo,
            'ids' => $itemIds,
            'cantidad' => $movidos
        ]);

        return [
            'success' => true,
            'data' => ['movidos' => $movidos],
            'message' => "{$movidos} items movidos correctamente",
        ];
    }

    /**
     * Obtener historial de auditoría de una categoría
     * GET /api/categoria/{id}/history
     */
    public function actionHistory($id): array
    {
        $model = $this->findModel($id);

        $logs = AuditLog::find()
            ->where(['tabla' => 'categoria', 'registro_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(50)
            ->all();

        return [
            'success' => true,
            'data' => [
                'categoria' => $model,
                'historial' => $logs
            ]
        ];
    }

    /**
     * Obtener plantillas predefinidas
     * GET /api/categoria/templates
     */
    public function actionTemplates(): array
    {
        $templates = [
            [
                'nombre' => 'Servicios de Mantenimiento',
                'descripcion' => 'Servicios periódicos de mantenimiento del vehículo',
                'tipo' => 'servicio',
                'icono' => 'wrench',
                'color' => '#3B82F6'
            ],
            [
                'nombre' => 'Sistema de Frenos',
                'descripcion' => 'Reparación y mantenimiento del sistema de frenos',
                'tipo' => 'servicio',
                'icono' => 'disc',
                'color' => '#EF4444'
            ],
            [
                'nombre' => 'Suspensión y Dirección',
                'descripcion' => 'Servicios relacionados con suspensión y dirección',
                'tipo' => 'servicio',
                'icono' => 'cog',
                'color' => '#F59E0B'
            ],
            [
                'nombre' => 'Aceites y Lubricantes',
                'descripcion' => 'Aceites, lubricantes y fluidos',
                'tipo' => 'inventario',
                'icono' => 'droplet',
                'color' => '#10B981'
            ],
            [
                'nombre' => 'Filtros',
                'descripcion' => 'Filtros de aire, aceite, combustible',
                'tipo' => 'inventario',
                'icono' => 'filter',
                'color' => '#8B5CF6'
            ],
            [
                'nombre' => 'Neumáticos',
                'descripcion' => 'Neumáticos y ruedas',
                'tipo' => 'ambos',
                'icono' => 'circle',
                'color' => '#6B7280'
            ],
        ];

        return [
            'success' => true,
            'data' => $templates
        ];
    }

    /**
     * Crear desde plantilla
     * POST /api/categoria/from-template
     */
    public function actionFromTemplate(): array
    {
        $request = Yii::$app->request;
        $templateIndex = (int)$request->post('templateIndex');

        $templates = [
            [
                'nombre' => 'Servicios de Mantenimiento',
                'descripcion' => 'Servicios periódicos de mantenimiento del vehículo',
                'tipo' => 'servicio',
                'icono' => 'wrench',
                'color' => '#3B82F6'
            ],
            [
                'nombre' => 'Sistema de Frenos',
                'descripcion' => 'Reparación y mantenimiento del sistema de frenos',
                'tipo' => 'servicio',
                'icono' => 'disc',
                'color' => '#EF4444'
            ],
            [
                'nombre' => 'Suspensión y Dirección',
                'descripcion' => 'Servicios relacionados con suspensión y dirección',
                'tipo' => 'servicio',
                'icono' => 'cog',
                'color' => '#F59E0B'
            ],
            [
                'nombre' => 'Aceites y Lubricantes',
                'descripcion' => 'Aceites, lubricantes y fluidos',
                'tipo' => 'inventario',
                'icono' => 'droplet',
                'color' => '#10B981'
            ],
            [
                'nombre' => 'Filtros',
                'descripcion' => 'Filtros de aire, aceite, combustible',
                'tipo' => 'inventario',
                'icono' => 'filter',
                'color' => '#8B5CF6'
            ],
            [
                'nombre' => 'Neumáticos',
                'descripcion' => 'Neumáticos y ruedas',
                'tipo' => 'ambos',
                'icono' => 'circle',
                'color' => '#6B7280'
            ],
        ];

        if (!isset($templates[$templateIndex])) {
            throw new BadRequestHttpException(json_encode([
                'error' => 'Plantilla no válida'
            ]));
        }

        $template = $templates[$templateIndex];

        // Validar nombre único
        if ($this->existeNombre($template['nombre'])) {
            throw new BadRequestHttpException(json_encode([
                'nombre' => ['Ya existe una categoría con este nombre']
            ]));
        }

        $model = new Categoria();
        $model->nombre = $template['nombre'];
        $model->descripcion = $template['descripcion'];
        $model->tipo = $template['tipo'];
        $model->icono = $template['icono'];
        $model->color = $template['color'];
        $model->activo = true;

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        $this->registrarAuditoria('CREATE_FROM_TEMPLATE', $model, ['template' => $template['nombre']]);

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Categoría creada desde plantilla',
        ];
    }

    /**
     * Obtener árbol de categorías (jerarquía)
     * GET /api/categoria/tree
     */
    public function actionTree(): array
    {
        $categorias = Categoria::find()
            ->where(['activo' => true])
            ->orderBy(['orden' => SORT_ASC, 'nombre' => SORT_ASC])
            ->all();

        $tree = [];
        $children = [];

        // Agrupar hijos por padreId
        foreach ($categorias as $cat) {
            if ($cat->padreId) {
                $children[$cat->padreId][] = $cat;
            } else {
                $tree[] = $cat;
            }
        }

        // Función recursiva para construir árbol
        $buildTree = function(&$nodes) use (&$buildTree, &$children) {
            foreach ($nodes as &$node) {
                if (isset($children[$node->id])) {
                    $node->hijos = $children[$node->id];
                    $buildTree($node->hijos);
                } else {
                    $node->hijos = [];
                }
            }
            return $nodes;
        };

        $buildTree($tree);

        return [
            'success' => true,
            'data' => $tree
        ];
    }

    /**
     * Helper: verificar si existe nombre (opcionalmente excluyendo un ID)
     */
    private function existeNombre(string $nombre, ?int $excludeId = null): bool
    {
        $query = Categoria::find()->where(['nombre' => $nombre]);
        if ($excludeId) {
            $query->andWhere(['!=', 'id', $excludeId]);
        }
        return $query->exists();
    }

    /**
     * Helper: registrar auditoría
     */
    private function registrarAuditoria(string $accion, ?Categoria $model, array $datosAdicionales = []): void
    {
        try {
            $log = new AuditLog();
            $log->usuario_id = Yii::$app->user->id ?? null;
            $log->tabla = 'categoria';
            $log->registro_id = $model?->id ?? null;
            $log->accion = $accion;
            $log->datos_viejos = $datosAdicionales['datos_viejos'] ?? null;
            $log->datos_nuevos = $datosAdicionales['datos_nuevos'] ?? null;
            $log->datos_adicionales = json_encode($datosAdicionales);
            $log->ip = Yii::$app->request->userIP;
            $log->created_at = date('Y-m-d H:i:s');
            $log->save(false);
        } catch (\Exception $e) {
            // Silenciar errores de auditoría
            Yii::error('Error registrando auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Categoria
    {
        $model = Categoria::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Categoría no encontrada');
        }

        return $model;
    }
}
