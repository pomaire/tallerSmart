-- Script de inicialización de la base de datos para TallerSmart
-- Compatible con MySQL 8.0

CREATE DATABASE IF NOT EXISTS tallersmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tallersmart;

-- Tabla: roles
CREATE TABLE IF NOT EXISTS rol (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    jerarquia INT DEFAULT 0 COMMENT 'Nivel de jerarquía del rol (mayor = más privilegios)',
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: permisos
CREATE TABLE IF NOT EXISTS permiso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    modulo VARCHAR(50) NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: rol_permisos (relación muchos a muchos)
CREATE TABLE IF NOT EXISTS rol_permiso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rolId INT NOT NULL,
    permisoId INT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rolId) REFERENCES rol(id) ON DELETE CASCADE,
    FOREIGN KEY (permisoId) REFERENCES permiso(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rol_permiso (rolId, permisoId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: usuarios
CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    rolId INT,
    telefono VARCHAR(20),
    idioma VARCHAR(10) DEFAULT 'es',
    activo BOOLEAN DEFAULT TRUE,
    bloqueadoHasta DATETIME NULL,
    intentosFallidos INT DEFAULT 0,
    debeCambiarPassword BOOLEAN DEFAULT FALSE,
    avatarUrl VARCHAR(500),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rolId) REFERENCES rol(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sesiones
CREATE TABLE IF NOT EXISTS sesion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ipAddress VARCHAR(45),
    userAgent VARCHAR(500),
    activa BOOLEAN DEFAULT TRUE,
    expiraEn DATETIME NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: login_attempts
CREATE TABLE IF NOT EXISTS login_attempt (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ipAddress VARCHAR(45),
    exito BOOLEAN DEFAULT FALSE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_createdAt (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: audit_logs
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT,
    accion ENUM('CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'IMPORT') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    entidad VARCHAR(100),
    registroId INT,
    datosAntiguos JSON,
    datosNuevos JSON,
    ipAddress VARCHAR(45),
    userAgent VARCHAR(500),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_modulo (modulo),
    INDEX idx_accion (accion),
    INDEX idx_usuarioId (usuarioId),
    INDEX idx_createdAt (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: categorias
CREATE TABLE IF NOT EXISTS categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    padreId INT NULL,
    tipo ENUM('servicio', 'inventario', 'ambos') DEFAULT 'ambos',
    icono VARCHAR(50),
    color VARCHAR(20),
    orden INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (padreId) REFERENCES categoria(id) ON DELETE SET NULL,
    INDEX idx_padreId (padreId),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: servicios
CREATE TABLE IF NOT EXISTS servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    duracionMinutos INT DEFAULT 60,
    categoriaId INT,
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoriaId) REFERENCES categoria(id) ON DELETE SET NULL,
    INDEX idx_categoriaId (categoriaId),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: clientes
CREATE TABLE IF NOT EXISTS cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    email VARCHAR(255),
    telefono VARCHAR(20) NOT NULL,
    documento VARCHAR(50),
    direccion TEXT,
    ciudad VARCHAR(100),
    pais VARCHAR(100) DEFAULT 'México',
    notas TEXT,
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_telefono (telefono),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: vehiculos
CREATE TABLE IF NOT EXISTS vehiculo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clienteId INT NOT NULL,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    year INT,
    color VARCHAR(50),
    placa VARCHAR(20) NOT NULL,
    vin VARCHAR(50),
    kilometraje INT DEFAULT 0,
    tipoCombustible ENUM('gasolina', 'diesel', 'electrico', 'hibrido') DEFAULT 'gasolina',
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clienteId) REFERENCES cliente(id) ON DELETE CASCADE,
    INDEX idx_clienteId (clienteId),
    INDEX idx_placa (placa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: citas
CREATE TABLE IF NOT EXISTS cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clienteId INT NOT NULL,
    vehiculoId INT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'en_progreso', 'completada', 'cancelada', 'no_show') DEFAULT 'pendiente',
    notas TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clienteId) REFERENCES cliente(id) ON DELETE CASCADE,
    FOREIGN KEY (vehiculoId) REFERENCES vehiculo(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_clienteId (clienteId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cita_servicio (relación muchos a muchos)
CREATE TABLE IF NOT EXISTS cita_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citaId INT NOT NULL,
    servicioId INT NOT NULL,
    cantidad INT DEFAULT 1,
    precioUnitario DECIMAL(10,2),
    FOREIGN KEY (citaId) REFERENCES cita(id) ON DELETE CASCADE,
    FOREIGN KEY (servicioId) REFERENCES servicio(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cita_servicio (citaId, servicioId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tecnicos
CREATE TABLE IF NOT EXISTS tecnico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT,
    especialidad VARCHAR(100),
    nivel ENUM('junior', 'semi-senior', 'senior', 'master') DEFAULT 'junior',
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: ordenes_servicio
CREATE TABLE IF NOT EXISTS orden_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) NOT NULL UNIQUE,
    clienteId INT NOT NULL,
    vehiculoId INT,
    tecnicoId INT,
    estado ENUM('abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega', 'entregada', 'cancelada') DEFAULT 'abierto',
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    subtotal DECIMAL(10,2) DEFAULT 0,
    descuento DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    notasInternas TEXT,
    notasCliente TEXT,
    fechaEntregaEstimada DATETIME,
    fechaEntregaReal DATETIME,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clienteId) REFERENCES cliente(id) ON DELETE CASCADE,
    FOREIGN KEY (vehiculoId) REFERENCES vehiculo(id) ON DELETE SET NULL,
    FOREIGN KEY (tecnicoId) REFERENCES tecnico(id) ON DELETE SET NULL,
    INDEX idx_folio (folio),
    INDEX idx_estado (estado),
    INDEX idx_clienteId (clienteId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: orden_servicio_detalle
CREATE TABLE IF NOT EXISTS orden_servicio_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ordenId INT NOT NULL,
    servicioId INT,
    descripcion TEXT,
    cantidad INT DEFAULT 1,
    precioUnitario DECIMAL(10,2) NOT NULL,
    precioOriginal DECIMAL(10,2) COMMENT 'Precio original del catálogo al momento de crear',
    subtotal DECIMAL(10,2) NOT NULL,
    tipo VARCHAR(50) DEFAULT 'servicio' COMMENT 'Tipo de detalle: servicio, repuesto, otro',
    notas TEXT COMMENT 'Notas adicionales sobre este servicio en la orden',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ordenId) REFERENCES orden_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (servicioId) REFERENCES servicio(id) ON DELETE SET NULL,
    INDEX idx_ordenId (ordenId),
    INDEX idx_servicioId (servicioId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: inventory_items
CREATE TABLE IF NOT EXISTS inventory_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoriaId INT,
    cantidad INT DEFAULT 0,
    stockMinimo INT DEFAULT 5,
    costoUnitario DECIMAL(10,2) DEFAULT 0,
    precioVenta DECIMAL(10,2) DEFAULT 0,
    ubicacion VARCHAR(100),
    proveedor VARCHAR(200),
    activo BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoriaId) REFERENCES categoria(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: inventory_movements
CREATE TABLE IF NOT EXISTS inventory_movement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    itemId INT NOT NULL,
    tipo ENUM('entrada', 'salida', 'ajuste', 'venta') NOT NULL,
    cantidad INT NOT NULL,
    referencia VARCHAR(100),
    motivo TEXT,
    usuarioId INT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (itemId) REFERENCES inventory_item(id) ON DELETE CASCADE,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_itemId (itemId),
    INDEX idx_createdAt (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: pagos
CREATE TABLE IF NOT EXISTS pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) NOT NULL UNIQUE,
    ordenId INT,
    clienteId INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodoPago ENUM('efectivo', 'tarjeta_credito', 'tarjeta_debito', 'transferencia', 'cheque', 'otro') DEFAULT 'efectivo',
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'reembolsado') DEFAULT 'pendiente',
    referenciaExterna VARCHAR(100),
    notas TEXT,
    usuarioId INT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ordenId) REFERENCES orden_servicio(id) ON DELETE SET NULL,
    FOREIGN KEY (clienteId) REFERENCES cliente(id) ON DELETE CASCADE,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_folio (folio),
    INDEX idx_estado (estado),
    INDEX idx_createdAt (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: pago_asignacion (para pagos que cubren múltiples órdenes)
CREATE TABLE IF NOT EXISTS pago_asignacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pagoId INT NOT NULL,
    ordenId INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pagoId) REFERENCES pago(id) ON DELETE CASCADE,
    FOREIGN KEY (ordenId) REFERENCES orden_servicio(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: notificaciones
CREATE TABLE IF NOT EXISTS notificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    leido BOOLEAN DEFAULT FALSE,
    leidoEn DATETIME NULL,
    url VARCHAR(500),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE CASCADE,
    INDEX idx_usuarioId (usuarioId),
    INDEX idx_leido (leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: parametros_sistema
CREATE TABLE IF NOT EXISTS parametro_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    descripcion TEXT,
    categoria VARCHAR(50),
    editable BOOLEAN DEFAULT TRUE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cierre_caja
CREATE TABLE IF NOT EXISTS cierre_caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT NOT NULL,
    fechaInicio DATETIME NOT NULL,
    fechaFin DATETIME,
    montoInicial DECIMAL(10,2) DEFAULT 0,
    montoFinal DECIMAL(10,2),
    ingresosEfectivo DECIMAL(10,2) DEFAULT 0,
    ingresosTarjeta DECIMAL(10,2) DEFAULT 0,
    ingresosTransferencia DECIMAL(10,2) DEFAULT 0,
    egresos DECIMAL(10,2) DEFAULT 0,
    observaciones TEXT,
    estado ENUM('abierto', 'cerrado') DEFAULT 'abierto',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuario(id) ON DELETE CASCADE,
    INDEX idx_fechaInicio (fechaInicio),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Roles iniciales
INSERT INTO rol (nombre, descripcion, jerarquia) VALUES
('Administrador', 'Administrador principal del sistema', 100),
('Gerente', 'Gerente del taller', 80),
('Recepcionista', 'Recepción y atención al cliente', 50),
('Técnico', 'Técnico mecánico', 30);

-- Permisos base
INSERT INTO permiso (nombre, descripcion, modulo) VALUES
('dashboard.view', 'Ver dashboard', 'dashboard'),
('citas.create', 'Crear citas', 'citas'),
('citas.view', 'Ver citas', 'citas'),
('citas.update', 'Actualizar citas', 'citas'),
('citas.delete', 'Eliminar citas', 'citas'),
('ordenes.create', 'Crear órdenes', 'ordenes'),
('ordenes.view', 'Ver órdenes', 'ordenes'),
('ordenes.update', 'Actualizar órdenes', 'ordenes'),
('ordenes.delete', 'Eliminar órdenes', 'ordenes'),
('clientes.create', 'Crear clientes', 'clientes'),
('clientes.view', 'Ver clientes', 'clientes'),
('clientes.update', 'Actualizar clientes', 'clientes'),
('clientes.delete', 'Eliminar clientes', 'clientes'),
('usuarios.create', 'Crear usuarios', 'usuarios'),
('usuarios.view', 'Ver usuarios', 'usuarios'),
('usuarios.update', 'Actualizar usuarios', 'usuarios'),
('usuarios.delete', 'Eliminar usuarios', 'usuarios'),
('configuracion.view', 'Ver configuración', 'configuracion'),
('configuracion.update', 'Actualizar configuración', 'configuracion');

-- Asignar todos los permisos al rol Administrador
INSERT INTO rol_permiso (rolId, permisoId)
SELECT r.id, p.id FROM rol r, permiso p WHERE r.nombre = 'Administrador';

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuario (nombre, email, passwordHash, rolId, debeCambiarPassword) VALUES
('Administrador', 'admin@tallersmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE);

-- Parámetros del sistema
INSERT INTO parametro_sistema (clave, valor, tipo, descripcion, categoria) VALUES
('sistema.nombre', 'TallerSmart', 'string', 'Nombre del sistema', 'general'),
('sistema.moneda', 'MXN', 'string', 'Moneda principal', 'general'),
('sistema.zona_horaria', 'America/Mexico_City', 'string', 'Zona horaria', 'general'),
('inventario.alerta_stock_minimo', 'true', 'boolean', 'Alertar cuando stock sea mínimo', 'inventario'),
('citas.duracion_por_defecto', '60', 'number', 'Duración predeterminada en minutos', 'citas'),
('ordenes.folio_prefijo', 'OS-', 'string', 'Prefijo para folios de orden', 'ordenes');

-- Tabla para historial de cambios de estado (HU-020)
CREATE TABLE IF NOT EXISTS orden_servicio_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_servicio_id INT NOT NULL,
    estado_anterior VARCHAR(50) NOT NULL,
    estado_nuevo VARCHAR(50) NOT NULL,
    usuario_id INT,
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    comentarios TEXT,
    FOREIGN KEY (orden_servicio_id) REFERENCES orden_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_orden_servicio (orden_servicio_id),
    INDEX idx_fecha_cambio (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLAS PARA MÓDULO DE PAGOS (HU-010, HU-019, HU-024)
-- ============================================

-- Tabla: documento_tributario (Boletas, Notas de Crédito, Facturas)
CREATE TABLE IF NOT EXISTS documento_tributario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('boleta', 'nota_credito', 'factura') NOT NULL,
    folio VARCHAR(50) NOT NULL UNIQUE,
    folio_sii INT,
    orden_servicio_id INT,
    cliente_id INT NOT NULL,
    usuario_id INT,
    monto_neto DECIMAL(10,2) DEFAULT 0,
    iva DECIMAL(10,2) DEFAULT 0,
    monto_total DECIMAL(10,2) NOT NULL,
    estado ENUM('borrador', 'pendiente_sii', 'emitido', 'anulado', 'fallido') DEFAULT 'borrador',
    estado_cola ENUM('pendiente', 'procesando', 'completado', 'fallido') DEFAULT 'pendiente',
    intentos_envio INT DEFAULT 0,
    rut_emisor VARCHAR(50),
    rut_receptor VARCHAR(50),
    razon_social_receptor VARCHAR(200),
    fecha_emision DATETIME,
    fecha_timbraje DATETIME,
    datos_sii JSON,
    codigo_barras VARCHAR(255),
    hash_documento VARCHAR(255),
    observaciones TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_servicio_id) REFERENCES orden_servicio(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL,
    INDEX idx_folio (folio),
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo),
    INDEX idx_orden_servicio (orden_servicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar tabla pago para agregar campos faltantes
ALTER TABLE pago 
ADD COLUMN IF NOT EXISTS cliente_id INT AFTER ordenId,
ADD COLUMN IF NOT EXISTS estado ENUM('pendiente', 'aprobado', 'rechazado', 'anulado', 'reembolsado') DEFAULT 'pendiente' AFTER metodoPago,
ADD COLUMN IF NOT EXISTS referencia_tarjeta VARCHAR(100) AFTER referencia,
ADD COLUMN IF NOT EXISTS folio VARCHAR(50) AFTER id,
ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
ADD FOREIGN KEY IF NOT EXISTS (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE;

-- Índices para mejorar rendimiento en consultas de pagos
CREATE INDEX IF NOT EXISTS idx_pago_estado ON pago(estado);
CREATE INDEX IF NOT EXISTS idx_pago_metodo ON pago(metodo_pago);
CREATE INDEX IF NOT EXISTS idx_pago_fecha ON pago(fecha_pago);
