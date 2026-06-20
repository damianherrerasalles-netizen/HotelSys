-- =============================================================================
--  HotelSys — Hotel Plaza Hostal, Yarumal, Antioquia
--  Script SQL DEFINITIVO — Semana 4, Días 1 y 2
--  Actividad III: Diseño de BD MySQL adaptada al Hotel Plaza Hostal
--  Aprendiz : Robinson Damian Herrera Betancurt | C.C. 1.042.769.035
--  Instructor: Carlos Andrés Yánez Díaz | SENA Ficha 3262266
--  Motor     : MySQL 8.0  |  Charset: utf8mb4
--  Versión   : 1.0.0 — Semana 4
-- =============================================================================
--  INSTRUCCIONES DE USO:
--  1. Abrir phpMyAdmin en XAMPP (http://localhost/phpmyadmin)
--  2. Clic en "SQL" en la barra superior
--  3. Pegar este script completo y clic en "Continuar"
--  4. Verificar que aparecen 6 tablas en la BD hotelsys_plaza
-- =============================================================================

-- -----------------------------------------------------------------------------
-- PASO 1: Crear y seleccionar la base de datos
-- -----------------------------------------------------------------------------
DROP DATABASE IF EXISTS hotelsys_plaza;

CREATE DATABASE hotelsys_plaza
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
    COMMENT 'Base de datos HotelSys — Hotel Plaza Hostal, Yarumal, Antioquia';

USE hotelsys_plaza;

-- Desactivar verificación de FK durante la creación
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- TABLA 1: PERSONAL
-- Descripción : Colaboradores del hotel y usuarios del sistema HotelSys
-- Relaciones  : 1:N con RESERVAS | 1:N con FACTURAS
-- =============================================================================
CREATE TABLE personal (
    id_personal             INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    nombres                 VARCHAR(80)         NOT NULL,
    apellidos               VARCHAR(80)         NOT NULL,
    tipo_documento          ENUM('CC','CE','TI') NOT NULL DEFAULT 'CC',
    num_documento           VARCHAR(20)         NOT NULL UNIQUE,
    cargo                   ENUM(
                                'Administrador',
                                'Recepcionista',
                                'Mucama',
                                'Mantenimiento',
                                'Otro'
                            )                   NOT NULL DEFAULT 'Recepcionista',
    telefono                VARCHAR(15),
    email                   VARCHAR(100),
    turno                   ENUM(
                                'Mañana',
                                'Tarde',
                                'Noche',
                                'Rotativo'
                            )                   NOT NULL DEFAULT 'Mañana',
    -- Acceso al sistema
    usuario_sistema         VARCHAR(40)         UNIQUE,
    password_hash           VARCHAR(255),
    rol                     ENUM(
                                'Admin',
                                'Recepcion',
                                'Consulta'
                            )                   NOT NULL DEFAULT 'Recepcion',
    -- Contacto de emergencia
    contacto_emergencia     VARCHAR(80),
    tel_emergencia          VARCHAR(15),
    -- Control
    activo                  TINYINT(1)          NOT NULL DEFAULT 1
                                                COMMENT '1=activo, 0=inactivo',
    fecha_ingreso           DATE,
    created_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_personal),
    INDEX idx_personal_rol      (rol),
    INDEX idx_personal_activo   (activo),
    INDEX idx_personal_cargo    (cargo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Colaboradores del Hotel Plaza Hostal y usuarios del sistema';

-- =============================================================================
-- TABLA 2: CLIENTES
-- Descripción : Huéspedes del hotel con historial de visitas
-- Relaciones  : 1:N con RESERVAS | 1:N con FACTURAS
-- =============================================================================
CREATE TABLE clientes (
    id_cliente              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    tipo_documento          ENUM('CC','CE','PAS','NIT') NOT NULL DEFAULT 'CC',
    num_documento           VARCHAR(20)         NOT NULL UNIQUE,
    nombres                 VARCHAR(80)         NOT NULL,
    apellidos               VARCHAR(80)         NOT NULL,
    telefono                VARCHAR(15)         COMMENT 'WhatsApp / celular',
    email                   VARCHAR(100),
    ciudad_origen           VARCHAR(60),
    departamento_origen     VARCHAR(60)         DEFAULT 'Antioquia',
    fecha_nacimiento        DATE,
    observaciones           TEXT                COMMENT 'Preferencias, alergias, notas VIP',
    activo                  TINYINT(1)          NOT NULL DEFAULT 1,
    fecha_registro          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cliente),
    UNIQUE INDEX idx_cliente_doc        (tipo_documento, num_documento),
    INDEX        idx_cliente_nombre     (apellidos, nombres),
    INDEX        idx_cliente_ciudad     (ciudad_origen)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Huéspedes del Hotel Plaza Hostal — historial y fidelización';

-- =============================================================================
-- TABLA 3: HABITACIONES
-- Descripción : Las 24 habitaciones del hotel con estado en tiempo real
-- Relaciones  : 1:N con RESERVAS
-- =============================================================================
CREATE TABLE habitaciones (
    id_habitacion           INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    numero_hab              VARCHAR(10)         NOT NULL UNIQUE
                                                COMMENT 'Ej: 101, 202, Suite1',
    tipo                    ENUM(
                                'Sencilla',
                                'Doble',
                                'Triple',
                                'Suite'
                            )                   NOT NULL,
    piso                    TINYINT UNSIGNED    NOT NULL DEFAULT 1,
    capacidad               TINYINT UNSIGNED    NOT NULL DEFAULT 1
                                                COMMENT 'Máximo de personas',
    precio_noche            DECIMAL(10,2)       NOT NULL
                                                COMMENT 'Precio en pesos colombianos',
    estado                  ENUM(
                                'Disponible',
                                'Ocupada',
                                'Mantenimiento',
                                'Reservada'
                            )                   NOT NULL DEFAULT 'Disponible'
                                                COMMENT 'Se actualiza en tiempo real',
    tiene_bano_privado      TINYINT(1)          NOT NULL DEFAULT 1,
    tiene_tv                TINYINT(1)          NOT NULL DEFAULT 1,
    tiene_ac                TINYINT(1)          NOT NULL DEFAULT 0,
    tiene_wifi              TINYINT(1)          NOT NULL DEFAULT 1,
    descripcion             TEXT,
    activa                  TINYINT(1)          NOT NULL DEFAULT 1,
    created_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_habitacion),
    INDEX idx_hab_estado        (estado),
    INDEX idx_hab_tipo          (tipo),
    INDEX idx_hab_disponible    (estado, activa),
    INDEX idx_hab_piso          (piso)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Las 24 habitaciones del hotel — estado actualizado en tiempo real';

-- =============================================================================
-- TABLA 4: RESERVAS  ⭐ ENTIDAD CENTRAL
-- Descripción : Reemplaza la gestión manual por WhatsApp
-- Relaciones  : N:1 CLIENTES | N:1 HABITACIONES | N:1 PERSONAL | 1:1 FACTURAS
-- =============================================================================
CREATE TABLE reservas (
    id_reserva              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    -- Claves foráneas
    id_cliente              INT UNSIGNED        NOT NULL,
    id_habitacion           INT UNSIGNED        NOT NULL,
    id_personal             INT UNSIGNED        NULL
                                                COMMENT 'Colaborador que registró',
    -- Fechas
    fecha_entrada           DATE                NOT NULL COMMENT 'Check-in',
    fecha_salida            DATE                NOT NULL COMMENT 'Check-out',
    num_noches              TINYINT UNSIGNED    GENERATED ALWAYS AS
                                                (DATEDIFF(fecha_salida, fecha_entrada))
                                                VIRTUAL
                                                COMMENT 'Calculado automáticamente',
    num_personas            TINYINT UNSIGNED    NOT NULL DEFAULT 1,
    -- Estado del ciclo de vida
    estado                  ENUM(
                                'Pendiente',
                                'Confirmada',
                                'Activa',
                                'Finalizada',
                                'Cancelada'
                            )                   NOT NULL DEFAULT 'Pendiente',
    -- Trazabilidad: de dónde vino (migración desde WhatsApp)
    canal_origen            ENUM(
                                'WhatsApp',
                                'Telefono',
                                'Presencial',
                                'Web'
                            )                   NOT NULL DEFAULT 'Presencial',
    -- Montos
    precio_noche_aplicado   DECIMAL(10,2)       NOT NULL DEFAULT 0.00
                                                COMMENT 'Precio al momento de la reserva',
    total_calculado         DECIMAL(10,2)       NOT NULL DEFAULT 0.00
                                                COMMENT 'num_noches × precio_noche_aplicado',
    -- Adicional
    observaciones           TEXT,
    motivo_cancelacion      VARCHAR(255),
    created_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reserva),
    -- Restricción: no permitir misma habitación en fechas solapadas (validar en PHP)
    CONSTRAINT fk_reserva_cliente
        FOREIGN KEY (id_cliente)    REFERENCES clientes(id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reserva_habitacion
        FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reserva_personal
        FOREIGN KEY (id_personal)   REFERENCES personal(id_personal)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_res_cliente       (id_cliente),
    INDEX idx_res_habitacion    (id_habitacion),
    INDEX idx_res_estado        (estado),
    INDEX idx_res_fechas        (fecha_entrada, fecha_salida),
    INDEX idx_res_canal         (canal_origen),
    INDEX idx_res_personal      (id_personal)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Entidad central — reemplaza gestión manual por WhatsApp';

-- =============================================================================
-- TABLA 5: FACTURAS
-- Descripción : Facturas con IVA automático vinculadas 1:1 a reservas
-- Relaciones  : 1:1 RESERVAS | N:1 CLIENTES | N:1 PERSONAL
-- =============================================================================
CREATE TABLE facturas (
    id_factura              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    -- Claves foráneas
    id_reserva              INT UNSIGNED        NOT NULL UNIQUE
                                                COMMENT 'Relación 1:1 con reservas',
    id_cliente              INT UNSIGNED        NOT NULL
                                                COMMENT 'Desnormalizado para reportes',
    id_personal             INT UNSIGNED        NULL
                                                COMMENT 'Quién emitió la factura',
    -- Montos con IVA automático (19% Colombia)
    subtotal                DECIMAL(10,2)       NOT NULL,
    iva_porcentaje          DECIMAL(5,2)        NOT NULL DEFAULT 19.00,
    iva_valor               DECIMAL(10,2)       NOT NULL
                                                COMMENT 'subtotal × (iva_porcentaje/100)',
    total                   DECIMAL(10,2)       NOT NULL
                                                COMMENT 'subtotal + iva_valor',
    -- Pago
    metodo_pago             ENUM(
                                'Efectivo',
                                'Transferencia',
                                'Tarjeta',
                                'Nequi',
                                'Daviplata'
                            )                   NOT NULL DEFAULT 'Efectivo',
    estado                  ENUM(
                                'Pendiente',
                                'Pagada',
                                'Anulada'
                            )                   NOT NULL DEFAULT 'Pendiente',
    observaciones           TEXT,
    fecha_emision           TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_factura),
    CONSTRAINT fk_factura_reserva
        FOREIGN KEY (id_reserva)    REFERENCES reservas(id_reserva)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_factura_cliente
        FOREIGN KEY (id_cliente)    REFERENCES clientes(id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_factura_personal
        FOREIGN KEY (id_personal)   REFERENCES personal(id_personal)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_fac_cliente       (id_cliente),
    INDEX idx_fac_estado        (estado),
    INDEX idx_fac_emision       (fecha_emision),
    INDEX idx_fac_personal      (id_personal)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Facturas con IVA 19% automático — relación 1:1 con reservas';

-- =============================================================================
-- TABLA 6: INVENTARIO
-- Descripción : Control de insumos con alertas de stock crítico
-- Relaciones  : Módulo autónomo — sin FK directa en v1.0
-- =============================================================================
CREATE TABLE inventario (
    id_item                 INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    nombre_item             VARCHAR(100)        NOT NULL,
    categoria               ENUM(
                                'Lencería',
                                'Aseo',
                                'Amenidades',
                                'Mantenimiento',
                                'Oficina',
                                'Alimentos',
                                'Otro'
                            )                   NOT NULL DEFAULT 'Otro',
    unidad_medida           VARCHAR(20)         NOT NULL DEFAULT 'unidad',
    stock_actual            INT UNSIGNED        NOT NULL DEFAULT 0,
    stock_minimo            INT UNSIGNED        NOT NULL DEFAULT 5
                                                COMMENT 'Umbral para alerta crítica',
    precio_unitario         DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
    proveedor               VARCHAR(100),
    telefono_proveedor      VARCHAR(15),
    ultima_compra           DATE,
    observaciones           TEXT,
    activo                  TINYINT(1)          NOT NULL DEFAULT 1,
    created_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_item),
    INDEX idx_inv_categoria     (categoria),
    INDEX idx_inv_stock_alerta  (stock_actual, stock_minimo),
    INDEX idx_inv_activo        (activo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos del hotel — alerta cuando stock_actual <= stock_minimo';

-- =============================================================================
-- Reactivar verificación de FK
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- VISTAS UTILITARIAS
-- =============================================================================

-- Vista 1: Habitaciones disponibles ahora
CREATE OR REPLACE VIEW v_habitaciones_disponibles AS
    SELECT
        id_habitacion,
        numero_hab,
        tipo,
        piso,
        capacidad,
        precio_noche,
        tiene_bano_privado,
        tiene_tv,
        tiene_wifi
    FROM habitaciones
    WHERE estado = 'Disponible' AND activa = 1
    ORDER BY tipo, piso, numero_hab;

-- Vista 2: Alertas de stock crítico
CREATE OR REPLACE VIEW v_stock_critico AS
    SELECT
        id_item,
        nombre_item,
        categoria,
        stock_actual,
        stock_minimo,
        (stock_minimo - stock_actual) AS unidades_faltantes,
        proveedor,
        telefono_proveedor
    FROM inventario
    WHERE stock_actual <= stock_minimo AND activo = 1
    ORDER BY unidades_faltantes DESC;

-- Vista 3: Reservas activas con detalle
CREATE OR REPLACE VIEW v_reservas_activas AS
    SELECT
        r.id_reserva,
        CONCAT(c.nombres, ' ', c.apellidos)     AS huesped,
        c.telefono                               AS tel_huesped,
        h.numero_hab,
        h.tipo                                   AS tipo_hab,
        r.fecha_entrada,
        r.fecha_salida,
        r.num_noches,
        r.num_personas,
        r.total_calculado,
        r.canal_origen,
        CONCAT(p.nombres, ' ', p.apellidos)     AS registrado_por
    FROM reservas r
    JOIN clientes    c ON r.id_cliente    = c.id_cliente
    JOIN habitaciones h ON r.id_habitacion = h.id_habitacion
    LEFT JOIN personal p ON r.id_personal  = p.id_personal
    WHERE r.estado IN ('Confirmada', 'Activa')
    ORDER BY r.fecha_entrada;

-- Vista 4: Resumen de ocupación por tipo de habitación
CREATE OR REPLACE VIEW v_ocupacion_por_tipo AS
    SELECT
        tipo,
        COUNT(*)                                                AS total_habitaciones,
        SUM(CASE WHEN estado = 'Disponible'    THEN 1 ELSE 0 END) AS disponibles,
        SUM(CASE WHEN estado = 'Ocupada'       THEN 1 ELSE 0 END) AS ocupadas,
        SUM(CASE WHEN estado = 'Reservada'     THEN 1 ELSE 0 END) AS reservadas,
        SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) AS en_mantenimiento,
        ROUND(
            SUM(CASE WHEN estado = 'Ocupada' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1
        )                                                          AS pct_ocupacion
    FROM habitaciones
    WHERE activa = 1
    GROUP BY tipo
    ORDER BY tipo;

-- Vista 5: Dashboard ejecutivo — KPIs principales
CREATE OR REPLACE VIEW v_dashboard_kpis AS
    SELECT
        (SELECT COUNT(*) FROM habitaciones WHERE estado = 'Disponible' AND activa = 1)
            AS hab_disponibles,
        (SELECT COUNT(*) FROM habitaciones WHERE estado = 'Ocupada' AND activa = 1)
            AS hab_ocupadas,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'Activa')
            AS reservas_activas,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'Pendiente')
            AS reservas_pendientes,
        (SELECT COALESCE(SUM(total), 0) FROM facturas
         WHERE estado = 'Pagada' AND DATE(fecha_emision) = CURDATE())
            AS ingresos_hoy,
        (SELECT COUNT(*) FROM inventario WHERE stock_actual <= stock_minimo AND activo = 1)
            AS items_stock_critico,
        (SELECT COUNT(*) FROM clientes WHERE DATE(fecha_registro) = CURDATE())
            AS clientes_nuevos_hoy;

-- =============================================================================
-- VERIFICACIÓN FINAL
-- =============================================================================
SELECT
    TABLE_NAME      AS 'Tabla creada',
    TABLE_ROWS      AS 'Filas',
    TABLE_COMMENT   AS 'Descripción'
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'hotelsys_plaza'
  AND TABLE_TYPE   = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- =============================================================================
--  FIN DEL SCRIPT DEFINITIVO — Semana 4, Días 1 y 2
--  Siguiente paso (Día 3): Ejecutar hotelsys_seed_data.sql con datos de prueba
--  Siguiente paso (Día 4): Probar en phpMyAdmin y verificar vistas
--  Siguiente paso (Día 5): git add . && git commit -m "feat: DB schema v1.0"
-- =============================================================================
