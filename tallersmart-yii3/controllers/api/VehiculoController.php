<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Vehiculo;
use app\models\AuditLog;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;
use Yii;

/**
 * Controlador REST API para Vehículos
 */
class VehiculoController extends BaseController
{
    public $modelClass = Vehiculo::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'][0]['actions'] = ['index', 'view', 'create', 'update', 'delete', 'upload-foto', 'export-csv', 'historial-servicios'];
        return $behaviors;
    }

    /**
     * Listar vehículos con paginación y filtros (HU-022: 12 por página)
     * GET /api/vehiculos
     */
    public function actionIndex(): ActiveDataProvider
    {
        $query = Vehiculo::find()
            ->joinWith('cliente')
            ->orderBy(['created_at' => SORT_DESC]);

        $request = Yii::$app->request;
        $clienteId = $request->get('cliente_id');
        $search = $request->get('search');
        $nombreCliente = $request->get('nombre_cliente'); // HU-003: Búsqueda por nombre del dueño

        if ($clienteId) {
            $query->andWhere(['vehiculo.cliente_id' => $clienteId]);
        }

        if ($search) {
            $query->andFilterWhere([
                'or',
                ['like', 'marca', $search],
                ['like', 'modelo', $search],
                ['like', 'placa', $search],
                ['like', 'vin', $search],
                ['like', 'cliente.nombre', $search], // HU-018: Búsqueda multi-campo incluye nombre cliente
            ]);
        }

        // HU-003: Búsqueda específica por nombre del dueño
        if ($nombreCliente) {
            $query->andFilterWhere(['like', 'cliente.nombre', $nombreCliente]);
        }

        // HU-022: Paginación con 12 items por página (grilla 3x4 en desktop)
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
        ]);
    }

    /**
     * Obtener vehículo por ID con información completa
     * GET /api/vehiculos/{id}
     */
    public function actionView($id): array
    {
        $model = $this->findModel($id);
        
        return [
            'success' => true,
            'data' => [
                ...$model->toArray(),
                'kilometraje_formateado' => $model->kilometrajeFormateado,
                'fecha_ultima_cita' => $model->fechaUltimaCita,
                'fecha_proxima_cita' => $model->fechaProximaCita,
                'tiene_ordenes_abiertas' => $model->tieneOrdenesAbiertas(),
            ],
        ];
    }

    /**
     * Crear nuevo vehículo
     * POST /api/vehiculos
     */
    public function actionCreate(): array
    {
        $request = Yii::$app->request;
        
        $model = new Vehiculo();
        $model->cliente_id = $request->post('cliente_id');
        $model->marca = $request->post('marca');
        $model->modelo = $request->post('modelo');
        $model->year = (int)$request->post('year', date('Y'));
        $model->placa = strtoupper($request->post('placa'));
        $model->vin = strtoupper($request->post('vin'));
        $model->color = $request->post('color');
        $model->kilometraje = (int)$request->post('kilometraje', 0);
        $model->motor = $request->post('motor');
        $model->notas = $request->post('notas');
        $model->activo = $request->post('activo', true);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Vehículo creado correctamente',
        ];
    }

    /**
     * Actualizar vehículo existente
     * PUT/PATCH /api/vehiculos/{id}
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $model->cliente_id = $request->post('cliente_id', $model->cliente_id);
        $model->marca = $request->post('marca', $model->marca);
        $model->modelo = $request->post('modelo', $model->modelo);
        $model->year = $request->post('year', $model->year);
        $model->placa = strtoupper($request->post('placa', $model->placa));
        $model->vin = strtoupper($request->post('vin', $model->vin));
        $model->color = $request->post('color', $model->color);
        $model->kilometraje = $request->post('kilometraje', $model->kilometraje);
        $model->motor = $request->post('motor', $model->motor);
        $model->notas = $request->post('notas', $model->notas);
        $model->activo = $request->post('activo', $model->activo);

        if (!$model->save()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }

        return [
            'success' => true,
            'data' => $model,
            'message' => 'Vehículo actualizado correctamente',
        ];
    }

    /**
     * Eliminar vehículo (HU-021: Proteger baja con servicios pendientes)
     * DELETE /api/vehiculos/{id}
     */
    public function actionDelete($id): array
    {
        $model = $this->findModel($id);
        
        // HU-021: Verificar si tiene órdenes abiertas antes de eliminar
        $canDelete = $model->canDelete();
        if (!$canDelete['success']) {
            throw new BadRequestHttpException($canDelete['message']);
        }
        
        $model->delete();

        return [
            'success' => true,
            'message' => 'Vehículo eliminado correctamente',
        ];
    }

    /**
     * HU-012: Subir foto del vehículo
     * POST /api/vehiculos/{id}/upload-foto
     */
    public function actionUploadFoto($id): array
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        
        $file = UploadedFile::getInstanceByName('foto');
        if (!$file) {
            throw new BadRequestHttpException('No se recibió ninguna imagen');
        }
        
        // Validar que sea una imagen
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->extension);
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new BadRequestHttpException('Formato no permitido. Extensiones válidas: ' . implode(', ', $allowedExtensions));
        }
        
        // Crear directorio para el vehículo
        $uploadPath = Yii::getAlias('@webroot/uploads/vehiculos/' . $model->id);
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $fileName = uniqid() . '.' . $extension;
        $filePath = $uploadPath . '/' . $fileName;
        
        if ($file->saveAs($filePath)) {
            // Guardar la ruta en la base de datos (asumiendo campo foto_url)
            $model->foto_url = '/uploads/vehiculos/' . $model->id . '/' . $fileName;
            $model->save(false);
            
            return [
                'success' => true,
                'data' => [
                    'url' => $model->foto_url,
                    'path' => $filePath,
                ],
                'message' => 'Foto subida correctamente',
            ];
        }
        
        throw new BadRequestHttpException('Error al guardar la imagen');
    }

    /**
     * HU-015: Ver historial de servicios del vehículo
     * GET /api/vehiculos/{id}/historial-servicios
     */
    public function actionHistorialServicios($id): array
    {
        $model = $this->findModel($id);
        
        $ordenes = $model->getOrdenesServicio()
            ->joinWith('detalles.servicio')
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        
        $historial = [];
        foreach ($ordenes as $orden) {
            $historial[] = [
                'id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
                'fecha' => date('d/m/Y', strtotime($orden->created_at)),
                'estado' => $orden->estado,
                'descripcion' => $orden->descripcion_problema,
                'diagnostico' => $orden->diagnostico,
                'total' => $orden->total,
                'servicios' => array_map(function($detalle) {
                    return [
                        'servicio' => $detalle->servicio?->nombre ?? 'N/A',
                        'cantidad' => $detalle->cantidad,
                        'costo' => $detalle->costo,
                    ];
                }, $orden->detalles),
            ];
        }
        
        return [
            'success' => true,
            'data' => $historial,
        ];
    }

    /**
     * HU-028: Exportar listado de vehículos a CSV
     * GET /api/vehiculos/export-csv
     */
    public function actionExportCsv(): \yii\web\Response
    {
        $vehiculos = Vehiculo::find()
            ->joinWith('cliente')
            ->orderBy(['placa' => SORT_ASC])
            ->all();
        
        // Crear archivo CSV temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'vehiculos_') . '.csv';
        $handle = fopen($tempFile, 'w');
        
        // Encabezados
        fputcsv($handle, ['Patente', 'Marca', 'Modelo', 'Año', 'Dueño', 'Último Km', 'VIN', 'Color']);
        
        // Datos
        foreach ($vehiculos as $vehiculo) {
            fputcsv($handle, [
                $vehiculo->placa,
                $vehiculo->marca,
                $vehiculo->modelo,
                $vehiculo->year,
                $vehiculo->cliente?->nombreCompleto ?? 'N/A',
                $vehiculo->kilometrajeFormateado,
                $vehiculo->vin,
                $vehiculo->color,
            ]);
        }
        
        fclose($handle);
        
        // Enviar respuesta con descarga
        $response = Yii::$app->response;
        $response->sendFile($tempFile, 'vehiculos_' . date('Y-m-d') . '.csv');
        
        // Eliminar archivo temporal después de enviar
        register_shutdown_function(function() use ($tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });
        
        return $response;
    }

    /**
     * Encontrar modelo por ID
     */
    protected function findModel($id): Vehiculo
    {
        $model = Vehiculo::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Vehículo no encontrado');
        }

        return $model;
    }
}
