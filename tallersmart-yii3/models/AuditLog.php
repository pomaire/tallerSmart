<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use Yii;

/**
 * Modelo para la tabla audit_log
 */
class AuditLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'audit_log';
    }

    public function rules(): array
    {
        return [
            [['accion', 'modulo'], 'required'],
            [['usuario_id', 'registro_id'], 'integer'],
            [['datos_antiguos', 'datos_nuevos'], 'string'],
            [['accion'], 'in', 'range' => ['CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'IMPORT', 'ROLLBACK']],
            [['modulo'], 'string', 'max' => 50],
            [['entidad'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
            [['created_at'], 'safe'],
            [['duracion_ms'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario',
            'accion' => 'Acción',
            'modulo' => 'Módulo',
            'entidad' => 'Entidad',
            'registro_id' => 'Registro ID',
            'datos_antiguos' => 'Datos Antiguos',
            'datos_nuevos' => 'Datos Nuevos',
            'ip_address' => 'IP',
            'user_agent' => 'User Agent',
            'created_at' => 'Fecha',
            'duracion_ms' => 'Duración (ms)',
        ];
    }

    /**
     * HU-023: Registrar acción de auditoría
     * HU-028: Ver duración de acciones
     */
    public static function registrarAccion(
        string $accion,
        string $modulo,
        ?int $registroId = null,
        ?string $datosAntiguos = null,
        ?string $datosNuevos = null,
        ?string $entidad = null,
        ?int $duracionMs = null
    ): bool {
        $audit = new self();
        $audit->accion = $accion;
        $audit->modulo = $modulo;
        $audit->registro_id = $registroId;
        $audit->entidad = $entidad;
        $audit->datos_antiguos = $datosAntiguos;
        $audit->datos_nuevos = $datosNuevos;
        $audit->ip_address = Yii::$app->request->userIP ?? null;
        $audit->user_agent = Yii::$app->request->userAgent ?? null;
        $audit->usuario_id = Yii::$app->user->id ?? null;
        $audit->created_at = date('Y-m-d H:i:s');
        
        // HU-028: Registrar duración si está disponible
        if ($duracionMs !== null) {
            $audit->duracion_ms = $duracionMs;
        }
        
        return $audit->save();
    }

    /**
     * HU-029: Auditoría de APIs
     */
    public static function registrarApiRequest(
        string $endpoint,
        string $metodo,
        ?array $parametros = null,
        ?int $statusCode = null,
        ?int $responseTime = null
    ): bool {
        return self::registrarAccion(
            'API_REQUEST',
            'api',
            null,
            null,
            json_encode([
                'endpoint' => $endpoint,
                'metodo' => $metodo,
                'parametros' => $parametros,
                'status_code' => $statusCode,
                'response_time_ms' => $responseTime,
            ]),
            'api_request'
        );
    }

    /**
     * HU-016: Auditoría de login
     */
    public static function registrarLogin(?int $usuarioId, bool $exitoso, ?string $email = null, ?string $ip = null): bool
    {
        $audit = new self();
        $audit->accion = $exitoso ? 'LOGIN' : 'LOGIN_FAILED';
        $audit->modulo = 'auth';
        $audit->usuario_id = $usuarioId;
        $audit->entidad = 'usuario';
        $audit->datos_nuevos = json_encode([
            'email' => $email,
            'ip' => $ip,
            'exitoso' => $exitoso,
        ]);
        $audit->ip_address = $ip ?? Yii::$app->request->userIP;
        $audit->user_agent = Yii::$app->request->userAgent;
        $audit->created_at = date('Y-m-d H:i:s');
        
        return $audit->save();
    }

    /**
     * HU-022: Auditoría de permisos
     */
    public static function registrarCambioPermiso(
        int $usuarioQueCambia,
        int $usuarioAfectado,
        string $accion,
        array $detalles
    ): bool {
        return self::registrarAccion(
            'PERMISO_' . strtoupper($accion),
            'rbac',
            $usuarioAfectado,
            json_encode(['usuario_afectado_id' => $usuarioAfectado]),
            json_encode($detalles),
            'permiso'
        );
    }

    /**
     * HU-019: Alertas de cambios sensibles
     */
    public static function verificarCambioSensible(string $modulo, array $datosNuevos, ?array $datosAntiguos = null): void
    {
        $camposSensibles = ['precio', 'costo', 'rol_id', 'permisos', 'password', 'saldo', 'descuento'];
        
        foreach ($camposSensibles as $campo) {
            if (isset($datosNuevos[$campo]) && (!isset($datosAntiguos[$campo]) || $datosAntiguos[$campo] !== $datosNuevos[$campo])) {
                // Enviar notificación a administradores
                $notificacion = new Notificacion();
                $notificacion->titulo = 'Cambio sensible detectado';
                $notificacion->mensaje = "El campo '{$campo}' fue modificado en {$modulo}";
                $notificacion->tipo = 'alerta';
                $notificacion->prioridad = 'alta';
                $notificacion->save();
                
                // También registrar en log
                self::registrarAccion(
                    'ALERTA_SENSIBLE',
                    $modulo,
                    null,
                    json_encode($datosAntiguos),
                    json_encode($datosNuevos),
                    $modulo
                );
            }
        }
    }

    /**
     * HU-014: Rotación de logs - Archivar logs antiguos
     */
    public static function archivarLogsAntiguos(int $dias = 365): int
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        
        // Mover logs antiguos a tabla de archivo
        $logsArchivados = self::find()
            ->where(['<', 'created_at', $fechaLimite])
            ->count();
        
        if ($logsArchivados > 0) {
            // Insertar en tabla de archivo
            Yii::$app->db->createCommand("
                INSERT INTO audit_log_archive 
                SELECT * FROM audit_log WHERE created_at < '{$fechaLimite}'
            ")->execute();
            
            // Eliminar logs archivados de la tabla principal
            self::deleteAll(['<', 'created_at', $fechaLimite]);
        }
        
        return $logsArchivados;
    }

    /**
     * HU-026: Comprimir datos históricos
     */
    public static function comprimirDatosHistoricos(int $meses = 6): int
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$meses} months"));
        
        $logs = self::find()
            ->where(['<', 'created_at', $fechaLimite])
            ->andWhere(['NOT', ['datos_antiguos' => null]])
            ->all();
        
        $comprimidos = 0;
        foreach ($logs as $log) {
            // Comprimir datos usando gzip
            $log->datos_antiguos = gzcompress($log->datos_antiguos, 9);
            $log->datos_nuevos = gzcompress($log->datos_nuevos, 9);
            if ($log->save(false)) {
                $comprimidos++;
            }
        }
        
        return $comprimidos;
    }

    /**
     * HU-030: Retener logs según normativa
     */
    public static function obtenerLogsParaEliminar(int $anosRetencion = 5): array
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$anosRetencion} years"));
        
        return self::find()
            ->where(['<', 'created_at', $fechaLimite])
            ->select(['id', 'created_at', 'modulo', 'accion'])
            ->asArray()
            ->all();
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
    
    /**
     * HU-028: Obtener duración formateada
     */
    public function getDuracionFormateada(): string
    {
        if (!$this->duracion_ms) {
            return 'N/A';
        }
        
        if ($this->duracion_ms < 1000) {
            return "{$this->duracion_ms} ms";
        }
        
        return sprintf('%.2f s', $this->duracion_ms / 1000);
    }
    
    /**
     * HU-012, HU-025: Obtener diff de cambios
     */
    public function getDiff(): array
    {
        $antiguos = json_decode($this->datos_antiguos ?? '{}', true) ?? [];
        $nuevos = json_decode($this->datos_nuevos ?? '{}', true) ?? [];
        
        $camposModificados = [];
        $todosLosCampos = array_unique(array_merge(array_keys($antiguos), array_keys($nuevos)));
        
        foreach ($todosLosCampos as $campo) {
            $valorAntiguo = $antiguos[$campo] ?? null;
            $valorNuevo = $nuevos[$campo] ?? null;
            
            if ($valorAntiguo !== $valorNuevo) {
                $camposModificados[$campo] = [
                    'anterior' => $valorAntiguo,
                    'nuevo' => $valorNuevo,
                    'modificado' => true,
                ];
            }
        }
        
        return [
            'campos_modificados' => $camposModificados,
            'total_cambios' => count($camposModificados),
        ];
    }
    
    /**
     * HU-015: Buscar en datos
     */
    public static function buscarEnDatos(string $termino): array
    {
        return self::find()
            ->where([
                'or',
                ['like', 'datos_antiguos', $termino],
                ['like', 'datos_nuevos', $termino]
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(100)
            ->all();
    }
}
