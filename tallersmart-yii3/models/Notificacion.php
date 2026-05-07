<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Modelo para la tabla notificacion
 * Soporta todas las HU de notificaciones
 */
class Notificacion extends ActiveRecord
{
    // Tipos de notificación (HU-012)
    const TIPO_INFO = 'info';
    const TIPO_WARNING = 'warning';
    const TIPO_SUCCESS = 'success';
    const TIPO_ERROR = 'error';
    
    // Tipos por contexto
    const TIPO_CITA = 'cita';
    const TIPO_ORDEN = 'orden';
    const TIPO_INVENTARIO = 'inventario';
    const TIPO_PAGO = 'pago';
    const TIPO_SISTEMA = 'sistema';
    
    // Prioridades (HU-013)
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_BAJA = 'baja';
    
    // Estados de lectura
    const ESTADO_NO_LEIDA = 0;
    const ESTADO_LEIDA = 1;
    
    public static function tableName(): string
    {
        return 'notificacion';
    }

    public function rules(): array
    {
        return [
            [['usuario_id', 'titulo', 'mensaje', 'tipo'], 'required'],
            [['usuario_id', 'entidad_id', 'created_by', 'leida'], 'integer'],
            [['titulo'], 'string', 'max' => 255],
            [['mensaje'], 'string'],
            [['tipo'], 'string', 'max' => 50],
            [['tipo'], 'in', 'range' => array_keys(self::getTipos())],
            [['prioridad'], 'string', 'max' => 20],
            [['prioridad'], 'default', 'value' => self::PRIORIDAD_MEDIA],
            [['prioridad'], 'in', 'range' => array_keys(self::getPrioridades())],
            [['entidad_tipo'], 'string', 'max' => 50],
            [['url_relacionada'], 'string', 'max' => 500],
            [['agrupable'], 'boolean', 'default' => false],
            [['grupo_key'], 'string', 'max' => 255],
            [['email_enviado'], 'boolean', 'default' => false],
            [['push_enviado'], 'boolean', 'default' => false],
            [['leida'], 'default', 'value' => self::ESTADO_NO_LEIDA],
            [['fecha_programada', 'leida_en', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario',
            'titulo' => 'Título',
            'mensaje' => 'Mensaje',
            'tipo' => 'Tipo',
            'prioridad' => 'Prioridad',
            'entidad_id' => 'Entidad ID',
            'entidad_tipo' => 'Entidad Tipo',
            'url_relacionada' => 'URL Relacionada',
            'agrupable' => 'Agrupable',
            'grupo_key' => 'Clave de Grupo',
            'leida' => 'Leída',
            'leida_en' => 'Leída En',
            'email_enviado' => 'Email Enviado',
            'push_enviado' => 'Push Enviado',
            'fecha_programada' => 'Fecha Programada',
            'created_by' => 'Creado Por',
            'created_at' => 'Creado En',
            'updated_at' => 'Actualizado En',
        ];
    }
    
    /**
     * HU-012: Obtener lista de tipos de notificación
     */
    public static function getTipos(): array
    {
        return [
            self::TIPO_INFO => 'Información',
            self::TIPO_WARNING => 'Advertencia',
            self::TIPO_SUCCESS => 'Éxito',
            self::TIPO_ERROR => 'Error',
            self::TIPO_CITA => 'Cita',
            self::TIPO_ORDEN => 'Orden',
            self::TIPO_INVENTARIO => 'Inventario',
            self::TIPO_PAGO => 'Pago',
            self::TIPO_SISTEMA => 'Sistema',
        ];
    }
    
    /**
     * HU-012: Obtener color según tipo
     */
    public static function getColorPorTipo(string $tipo): string
    {
        $colores = [
            self::TIPO_INFO => 'blue',
            self::TIPO_WARNING => 'orange',
            self::TIPO_SUCCESS => 'green',
            self::TIPO_ERROR => 'red',
            self::TIPO_CITA => 'blue',
            self::TIPO_ORDEN => 'orange',
            self::TIPO_INVENTARIO => 'red',
            self::TIPO_PAGO => 'green',
            self::TIPO_SISTEMA => 'purple',
        ];
        return $colores[$tipo] ?? 'gray';
    }
    
    /**
     * HU-012: Obtener icono según tipo
     */
    public static function getIconoPorTipo(string $tipo): string
    {
        $iconos = [
            self::TIPO_INFO => 'fa-info-circle',
            self::TIPO_WARNING => 'fa-exclamation-triangle',
            self::TIPO_SUCCESS => 'fa-check-circle',
            self::TIPO_ERROR => 'fa-times-circle',
            self::TIPO_CITA => 'fa-calendar',
            self::TIPO_ORDEN => 'fa-wrench',
            self::TIPO_INVENTARIO => 'fa-box',
            self::TIPO_PAGO => 'fa-money-bill',
            self::TIPO_SISTEMA => 'fa-cog',
        ];
        return $iconos[$tipo] ?? 'fa-bell';
    }
    
    /**
     * HU-013: Obtener lista de prioridades
     */
    public static function getPrioridades(): array
    {
        return [
            self::PRIORIDAD_ALTA => 'Alta',
            self::PRIORIDAD_MEDIA => 'Media',
            self::PRIORIDAD_BAJA => 'Baja',
        ];
    }
    
    /**
     * HU-013: Obtener orden de prioridad para sorting
     */
    public static function getOrdenPrioridad(string $prioridad): int
    {
        $orden = [
            self::PRIORIDAD_ALTA => 1,
            self::PRIORIDAD_MEDIA => 2,
            self::PRIORIDAD_BAJA => 3,
        ];
        return $orden[$prioridad] ?? 2;
    }
    
    /**
     * Verifica si la notificación está leída
     */
    public function getEstaLeida(): bool
    {
        return $this->leida === self::ESTADO_LEIDA;
    }
    
    /**
     * Verifica si la notificación no está leída
     */
    public function getEstaNoLeida(): bool
    {
        return $this->leida === self::ESTADO_NO_LEIDA;
    }
    
    /**
     * HU-003: Marcar como leída
     */
    public function marcarComoLeida(): bool
    {
        if ($this->leida === self::ESTADO_LEIDA) {
            return true;
        }
        
        $this->leida = self::ESTADO_LEIDA;
        $this->leida_en = new Expression('NOW()');
        return $this->save(false);
    }
    
    /**
     * HU-003: Marcar como no leída
     */
    public function marcarComoNoLeida(): bool
    {
        $this->leida = self::ESTADO_NO_LEIDA;
        $this->leida_en = null;
        return $this->save(false);
    }
    
    /**
     * HU-014: Marcar email como enviado
     */
    public function marcarEmailEnviado(): bool
    {
        $this->email_enviado = true;
        return $this->save(false);
    }
    
    /**
     * HU-021: Marcar push como enviado
     */
    public function marcarPushEnviado(): bool
    {
        $this->push_enviado = true;
        return $this->save(false);
    }
    
    /**
     * HU-001: Crear notificación con plantilla
     * @param int $usuarioId ID del usuario destinatario
     * @param string $titulo Título de la notificación
     * @param string $mensaje Mensaje de la notificación
     * @param string $tipo Tipo de notificación
     * @param string|null $prioridad Prioridad (alta, media, baja)
     * @param int|null $entidadId ID de la entidad relacionada
     * @param string|null $entidadTipo Tipo de entidad (Cita, OrdenServicio, etc.)
     * @param string|null $urlRelacionada URL para navegar a la entidad
     * @param int|null $createdBy ID del usuario que crea la notificación
     * @param bool $enviarEmail Si debe enviar email además
     * @param bool $enviarPush Si debe enviar push notification
     * @return Notificacion|false
     */
    public static function crear(
        int $usuarioId,
        string $titulo,
        string $mensaje,
        string $tipo = self::TIPO_INFO,
        ?string $prioridad = null,
        ?int $entidadId = null,
        ?string $entidadTipo = null,
        ?string $urlRelacionada = null,
        ?int $createdBy = null,
        bool $enviarEmail = false,
        bool $enviarPush = false
    ): self|false {
        $notificacion = new self();
        $notificacion->usuario_id = $usuarioId;
        $notificacion->titulo = $titulo;
        $notificacion->mensaje = $mensaje;
        $notificacion->tipo = $tipo;
        $notificacion->prioridad = $prioridad ?? self::PRIORIDAD_MEDIA;
        $notificacion->entidad_id = $entidadId;
        $notificacion->entidad_tipo = $entidadTipo;
        $notificacion->url_relacionada = $urlRelacionada;
        $notificacion->created_by = $createdBy;
        $notificacion->email_enviado = false;
        $notificacion->push_enviado = false;
        $notificacion->leida = self::ESTADO_NO_LEIDA;
        
        if (!$notificacion->save(false)) {
            return false;
        }
        
        // HU-014: Enviar email si corresponde
        if ($enviarEmail) {
            NotificacionService::enviarEmail($notificacion);
        }
        
        // HU-021: Enviar push si corresponde
        if ($enviarPush) {
            NotificacionService::enviarPush($notificacion);
        }
        
        return $notificacion;
    }
    
    /**
     * HU-004: Marcar todas como leídas para un usuario
     * @param int $usuarioId ID del usuario
     * @param string|null $tipo Filtrar por tipo (opcional)
     * @return int Número de notificaciones actualizadas
     */
    public static function marcarTodasLeidas(int $usuarioId, ?string $tipo = null): int
    {
        $query = self::updateAll([
            'leida' => self::ESTADO_LEIDA,
            'leida_en' => new Expression('NOW()'),
        ], ['usuario_id' => $usuarioId, 'leida' => self::ESTADO_NO_LEIDA]);
        
        return $query;
    }
    
    /**
     * HU-005: Eliminar notificación de un usuario
     * @param int $id ID de la notificación
     * @param int $usuarioId ID del usuario (para verificar propiedad)
     * @return bool
     */
    public static function eliminarParaUsuario(int $id, int $usuarioId): bool
    {
        $notificacion = self::findOne(['id' => $id, 'usuario_id' => $usuarioId]);
        if ($notificacion) {
            return $notificacion->delete() > 0;
        }
        return false;
    }
    
    /**
     * HU-016: Obtener contador de no leídas para un usuario
     * @param int $usuarioId ID del usuario
     * @param string|null $tipo Filtrar por tipo (opcional)
     * @return int
     */
    public static function contarNoLeidas(int $usuarioId, ?string $tipo = null): int
    {
        $query = self::find()
            ->where(['usuario_id' => $usuarioId, 'leida' => self::ESTADO_NO_LEIDA]);
        
        if ($tipo !== null) {
            $query->andWhere(['tipo' => $tipo]);
        }
        
        return (int)$query->count();
    }
    
    /**
     * HU-022: Obtener historial paginado para un usuario
     * @param int $usuarioId ID del usuario
     * @param int $pagina Página actual
     * @param int $porPagina Elementos por página
     * @param array $filtros Filtros adicionales
     * @return array [items, total, totalPages]
     */
    public static function obtenerHistorial(int $usuarioId, int $pagina = 1, int $porPagina = 20, array $filtros = []): array
    {
        $query = self::find()->where(['usuario_id' => $usuarioId]);
        
        // Aplicar filtros
        if (isset($filtros['tipo']) && $filtros['tipo'] !== '') {
            $query->andWhere(['tipo' => $filtros['tipo']]);
        }
        if (isset($filtros['estado'])) {
            if ($filtros['estado'] === 'no_leida') {
                $query->andWhere(['leida' => self::ESTADO_NO_LEIDA]);
            } elseif ($filtros['estado'] === 'leida') {
                $query->andWhere(['leida' => self::ESTADO_LEIDA]);
            }
        }
        if (isset($filtros['prioridad']) && $filtros['prioridad'] !== '') {
            $query->andWhere(['prioridad' => $filtros['prioridad']]);
        }
        if (isset($filtros['busqueda']) && $filtros['busqueda'] !== '') {
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $query->andWhere(['or', ['like', 'titulo', $busqueda], ['like', 'mensaje', $busqueda]]);
        }
        
        $total = (int)$query->count();
        $totalPages = max(1, (int)ceil($total / $porPagina));
        
        $query->orderBy(['created_at' => SORT_DESC])
            ->limit($porPagina)
            ->offset(($pagina - 1) * $porPagina);
        
        $items = $query->all();
        
        return [
            'items' => $items,
            'total' => $total,
            'totalPages' => $totalPages,
            'pagina' => $pagina,
        ];
    }
    
    /**
     * HU-024: Obtener clave de agrupación para notificaciones similares
     */
    public static function obtenerGrupoKey(string $tipo, ?int $entidadId = null, ?string $entidadTipo = null): string
    {
        return md5($tipo . '_' . ($entidadTipo ?? '') . '_' . ($entidadId ?? ''));
    }
    
    /**
     * HU-024: Agrupar notificaciones similares
     * @param array $notificaciones Lista de notificaciones
     * @return array Notificaciones agrupadas
     */
    public static function agruparSimilares(array $notificaciones): array
    {
        $agrupadas = [];
        
        foreach ($notificaciones as $notif) {
            $grupoKey = self::obtenerGrupoKey($notif->tipo, $notif->entidad_id, $notif->entidad_tipo);
            
            if (!isset($agrupadas[$grupoKey])) {
                $agrupadas[$grupoKey] = [
                    'principal' => $notif,
                    'cantidad' => 1,
                    'similares' => [],
                ];
            } else {
                $agrupadas[$grupoKey]['cantidad']++;
                $agrupadas[$grupoKey]['similares'][] = $notif;
            }
        }
        
        return array_values($agrupadas);
    }
    
    /**
     * Before save - actualizar timestamps
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new Expression('NOW()');
            }
            $this->updated_at = new Expression('NOW()');
            return true;
        }
        return false;
    }
    
    /**
     * Relación con Usuario destinatario
     */
    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
    
    /**
     * Relación con Usuario creador
     */
    public function getCreador(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Usuario::class, ['id' => 'created_by']);
    }
}
