-- BORRAR TODO PARA EMPEZAR DE CERO
DROP DATABASE IF EXISTS hotel_system;
CREATE DATABASE hotel_system;
USE hotel_system;

-- ==========================================
-- 1. SEGURIDAD
-- ==========================================
CREATE TABLE persona (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_documento VARCHAR(20),
    numero_documento VARCHAR(20) UNIQUE,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    -- Auditoría
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE usuario (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    persona_id BIGINT UNSIGNED,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    ultimo_acceso DATETIME,
    bloqueado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (persona_id) REFERENCES persona(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE rol (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE permiso (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    accion VARCHAR(50),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE modulo (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    ruta_base VARCHAR(100),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE vista (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    modulo_id BIGINT UNSIGNED,
    nombre VARCHAR(50),
    descripcion TEXT,
    ruta VARCHAR(100),
    FOREIGN KEY (modulo_id) REFERENCES modulo(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

-- Tablas intermedias de seguridad
CREATE TABLE usuario_rol (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED,
    rol_id BIGINT UNSIGNED,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (rol_id) REFERENCES rol(id)
) ENGINE=InnoDB;

CREATE TABLE rol_permiso (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rol_id BIGINT UNSIGNED,
    permiso_id BIGINT UNSIGNED,
    FOREIGN KEY (rol_id) REFERENCES rol(id),
    FOREIGN KEY (permiso_id) REFERENCES permiso(id)
) ENGINE=InnoDB;

CREATE TABLE modulo_vista (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    modulo_id BIGINT UNSIGNED,
    vista_id BIGINT UNSIGNED,
    FOREIGN KEY (modulo_id) REFERENCES modulo(id),
    FOREIGN KEY (vista_id) REFERENCES vista(id)
) ENGINE=InnoDB;

-- ==========================================
-- 2. PARAMETRIZACIÓN
-- ==========================================
CREATE TABLE empresa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    nit VARCHAR(20),
    razon_social VARCHAR(150),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    direccion VARCHAR(255),
    sitio_web VARCHAR(100),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE cliente (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_documento VARCHAR(20),
    numero_documento VARCHAR(20),
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    direccion VARCHAR(255),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE empleado (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    persona_id BIGINT UNSIGNED,
    cargo VARCHAR(50),
    fecha_ingreso DATE,
    telefono_laboral VARCHAR(20),
    correo_laboral VARCHAR(100),
    FOREIGN KEY (persona_id) REFERENCES persona(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE metodo_pago (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    requiere_referencia BOOLEAN,
    permite_pago_parcial BOOLEAN,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE informacion_legal (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED,
    tipo_documento_legal VARCHAR(50),
    numero_documento_legal VARCHAR(50),
    descripcion TEXT,
    fecha_expedicion DATE,
    fecha_vencimiento DATE,
    FOREIGN KEY (empresa_id) REFERENCES empresa(id)
) ENGINE=InnoDB;

CREATE TABLE tipo_dia (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    fecha DATE,
    aplica_temporada BOOLEAN,
    aplica_feriado BOOLEAN,
    aplica_especial BOOLEAN,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

-- ==========================================
-- 3. DISTRIBUCIÓN
-- ==========================================
CREATE TABLE sede (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED,
    nombre VARCHAR(100),
    direccion VARCHAR(255),
    ciudad VARCHAR(100),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    FOREIGN KEY (empresa_id) REFERENCES empresa(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE tipo_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    capacidad_base INT,
    capacidad_maxima INT,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE estado_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    permite_reserva BOOLEAN,
    permite_check_in BOOLEAN,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sede_id BIGINT UNSIGNED,
    tipo_habitacion_id BIGINT UNSIGNED,
    estado_habitacion_id BIGINT UNSIGNED,
    numero VARCHAR(10),
    piso INT,
    capacidad INT,
    descripcion TEXT,
    FOREIGN KEY (sede_id) REFERENCES sede(id),
    FOREIGN KEY (tipo_habitacion_id) REFERENCES tipo_habitacion(id),
    FOREIGN KEY (estado_habitacion_id) REFERENCES estado_habitacion(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE precio (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_habitacion_id BIGINT UNSIGNED,
    tipo_dia_id BIGINT UNSIGNED,
    valor DECIMAL(12,2),
    fecha_inicio DATE,
    fecha_fin DATE,
    condicion TEXT,
    FOREIGN KEY (tipo_habitacion_id) REFERENCES tipo_habitacion(id),
    FOREIGN KEY (tipo_dia_id) REFERENCES tipo_dia(id)
) ENGINE=InnoDB;

-- ==========================================
-- 4. PRESTACIÓN DE SERVICIO
-- ==========================================
CREATE TABLE reserva_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED,
    habitacion_id BIGINT UNSIGNED,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    cantidad_persona INT,
    estado_reserva VARCHAR(50),
    valor_estimado DECIMAL(12,2),
    FOREIGN KEY (cliente_id) REFERENCES cliente(id),
    FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE estadia (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reserva_habitacion_id BIGINT UNSIGNED,
    cliente_id BIGINT UNSIGNED,
    habitacion_id BIGINT UNSIGNED,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    estado_estadia VARCHAR(50),
    FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id),
    FOREIGN KEY (cliente_id) REFERENCES cliente(id),
    FOREIGN KEY (habitacion_id) REFERENCES habitacion(id)
) ENGINE=InnoDB;

CREATE TABLE check_in (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reserva_habitacion_id BIGINT UNSIGNED,
    empleado_id BIGINT UNSIGNED,
    fecha_hora_ingreso DATETIME,
    observacion TEXT,
    FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id),
    FOREIGN KEY (empleado_id) REFERENCES empleado(id)
) ENGINE=InnoDB;

CREATE TABLE check_out (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estadia_id BIGINT UNSIGNED,
    empleado_id BIGINT UNSIGNED,
    fecha_hora_salida DATETIME,
    observacion TEXT,
    valor_total DECIMAL(12,2),
    FOREIGN KEY (estadia_id) REFERENCES estadia(id),
    FOREIGN KEY (empleado_id) REFERENCES empleado(id)
) ENGINE=InnoDB;

CREATE TABLE cancelacion_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reserva_habitacion_id BIGINT UNSIGNED,
    motivo TEXT,
    fecha_cancelacion DATETIME,
    aplica_penalidad BOOLEAN,
    valor_penalidad DECIMAL(12,2),
    FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id)
) ENGINE=InnoDB;

CREATE TABLE disponibilidad_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    habitacion_id BIGINT UNSIGNED,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    disponible BOOLEAN,
    motivo_no_disponible TEXT,
    FOREIGN KEY (habitacion_id) REFERENCES habitacion(id)
) ENGINE=InnoDB;

CREATE TABLE catalogo_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    habitacion_id BIGINT UNSIGNED,
    titulo VARCHAR(100),
    descripcion TEXT,
    precio_base DECIMAL(12,2),
    visible BOOLEAN,
    FOREIGN KEY (habitacion_id) REFERENCES habitacion(id)
) ENGINE=InnoDB;

-- ==========================================
-- 5. INVENTARIO
-- ==========================================
CREATE TABLE proveedor (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    nit VARCHAR(20),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    direccion VARCHAR(255),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE producto (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    proveedor_id BIGINT UNSIGNED,
    nombre VARCHAR(100),
    descripcion TEXT,
    valor_venta DECIMAL(12,2),
    stock_actual INT,
    stock_minimo INT,
    FOREIGN KEY (proveedor_id) REFERENCES proveedor(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE servicio (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    descripcion TEXT,
    valor_venta DECIMAL(12,2),
    disponible BOOLEAN,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE seguimiento_producto (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    producto_id BIGINT UNSIGNED,
    tipo_movimiento VARCHAR(50),
    cantidad INT,
    fecha_movimiento DATETIME,
    observacion TEXT,
    FOREIGN KEY (producto_id) REFERENCES producto(id)
) ENGINE=InnoDB;

CREATE TABLE disponibilidad_inventario (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    producto_id BIGINT UNSIGNED,
    servicio_id BIGINT UNSIGNED,
    cantidad_disponible INT,
    disponible BOOLEAN,
    observacion TEXT,
    FOREIGN KEY (producto_id) REFERENCES producto(id),
    FOREIGN KEY (servicio_id) REFERENCES servicio(id)
) ENGINE=InnoDB;

CREATE TABLE venta_producto (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estadia_id BIGINT UNSIGNED,
    producto_id BIGINT UNSIGNED,
    cantidad INT,
    valor_unitario DECIMAL(12,2),
    valor_total DECIMAL(12,2),
    FOREIGN KEY (estadia_id) REFERENCES estadia(id),
    FOREIGN KEY (producto_id) REFERENCES producto(id)
) ENGINE=InnoDB;

CREATE TABLE venta_servicio (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estadia_id BIGINT UNSIGNED,
    servicio_id BIGINT UNSIGNED,
    cantidad INT,
    valor_unitario DECIMAL(12,2),
    valor_total DECIMAL(12,2),
    FOREIGN KEY (estadia_id) REFERENCES estadia(id),
    FOREIGN KEY (servicio_id) REFERENCES servicio(id)
) ENGINE=InnoDB;

-- ==========================================
-- 6. FACTURACIÓN
-- ==========================================
CREATE TABLE factura (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED,
    estadia_id BIGINT UNSIGNED,
    numero_factura VARCHAR(50) UNIQUE,
    fecha_emision DATETIME,
    subtotal DECIMAL(12,2),
    impuesto DECIMAL(12,2),
    descuento DECIMAL(12,2),
    total DECIMAL(12,2),
    estado_factura VARCHAR(50),
    FOREIGN KEY (cliente_id) REFERENCES cliente(id),
    FOREIGN KEY (estadia_id) REFERENCES estadia(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE pre_factura (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estadia_id BIGINT UNSIGNED,
    reserva_habitacion_id BIGINT UNSIGNED,
    cliente_id BIGINT UNSIGNED,
    subtotal DECIMAL(12,2),
    impuesto DECIMAL(12,2),
    descuento DECIMAL(12,2),
    total DECIMAL(12,2),
    FOREIGN KEY (estadia_id) REFERENCES estadia(id),
    FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id),
    FOREIGN KEY (cliente_id) REFERENCES cliente(id)
) ENGINE=InnoDB;

CREATE TABLE pago_parcial (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reserva_habitacion_id BIGINT UNSIGNED,
    factura_id BIGINT UNSIGNED,
    metodo_pago_id BIGINT UNSIGNED,
    valor DECIMAL(12,2),
    fecha_pago DATETIME,
    referencia_pago VARCHAR(100),
    FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id),
    FOREIGN KEY (factura_id) REFERENCES factura(id),
    FOREIGN KEY (metodo_pago_id) REFERENCES metodo_pago(id)
) ENGINE=InnoDB;

CREATE TABLE detalle_compra (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    factura_id BIGINT UNSIGNED,
    producto_id BIGINT UNSIGNED,
    servicio_id BIGINT UNSIGNED,
    descripcion TEXT,
    cantidad INT,
    valor_unitario DECIMAL(12,2),
    valor_total DECIMAL(12,2),
    FOREIGN KEY (factura_id) REFERENCES factura(id),
    FOREIGN KEY (producto_id) REFERENCES producto(id),
    FOREIGN KEY (servicio_id) REFERENCES servicio(id)
) ENGINE=InnoDB;

-- ==========================================
-- 7. NOTIFICACIÓN
-- ==========================================
CREATE TABLE promocion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100),
    descripcion TEXT,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    canal VARCHAR(50),
    activa BOOLEAN,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE alerta (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED,
    reserva_habitacion_id BIGINT UNSIGNED,
    titulo VARCHAR(100),
    mensaje TEXT,
    canal VARCHAR(50),
    fecha_envio DATETIME,
    FOREIGN KEY (cliente_id) REFERENCES cliente(id),
    FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id)
) ENGINE=InnoDB;

CREATE TABLE termino_condicion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100),
    contenido TEXT,
    version VARCHAR(20),
    fecha_vigencia DATE,
    obligatorio BOOLEAN,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE fidelizacion_cliente (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED,
    nivel VARCHAR(50),
    puntos INT,
    fecha_ultima_interaccion DATETIME,
    observacion TEXT,
    FOREIGN KEY (cliente_id) REFERENCES cliente(id)
) ENGINE=InnoDB;

-- ==========================================
-- 8. MANTENIMIENTO
-- ==========================================
CREATE TABLE mantenimiento_habitacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    habitacion_id BIGINT UNSIGNED,
    empleado_id BIGINT UNSIGNED,
    tipo_mantenimiento VARCHAR(100),
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    estado_mantenimiento VARCHAR(50),
    observacion TEXT,
    FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
    FOREIGN KEY (empleado_id) REFERENCES empleado(id),
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB;

CREATE TABLE mantenimiento_uso (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mantenimiento_habitacion_id BIGINT UNSIGNED,
    motivo_uso TEXT,
    detalle_actividad TEXT,
    FOREIGN KEY (mantenimiento_habitacion_id) REFERENCES mantenimiento_habitacion(id)
) ENGINE=InnoDB;

CREATE TABLE mantenimiento_remodelacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mantenimiento_habitacion_id BIGINT UNSIGNED,
    descripcion_remodelacion TEXT,
    presupuesto_estimado DECIMAL(12,2),
    FOREIGN KEY (mantenimiento_habitacion_id) REFERENCES mantenimiento_habitacion(id)
) ENGINE=InnoDB;

CREATE TABLE dashboard_mantenimiento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sede_id BIGINT UNSIGNED,
    total_habitacion INT,
    habitacion_disponible INT,
    habitacion_ocupada INT,
    habitacion_mantenimiento INT,
    fecha_corte DATETIME,
    FOREIGN KEY (sede_id) REFERENCES sede(id)
) ENGINE=InnoDB;