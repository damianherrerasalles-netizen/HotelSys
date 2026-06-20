-- =============================================================================
--  HotelSys — Hotel Plaza Hostal, Yarumal, Antioquia
--  Script SEED DATA — Semana 4, Día 3
--  Datos de prueba representativos del hotel real
--  Aprendiz : Robinson Damian Herrera Betancurt | C.C. 1.042.769.035
--  Instructor: Carlos Andrés Yánez Díaz | SENA Ficha 3262266
--  Motor     : MySQL 8.0
-- =============================================================================
--  INSTRUCCIONES:
--  1. Ejecutar DESPUÉS de hotelsys_schema_v2.sql
--  2. phpMyAdmin → seleccionar BD hotelsys_plaza → pestaña SQL → pegar y ejecutar
-- =============================================================================

USE hotelsys_plaza;

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1. PERSONAL (3 colaboradores del hotel)
-- =============================================================================
INSERT INTO personal
    (nombres, apellidos, tipo_documento, num_documento, cargo,
     telefono, email, turno, usuario_sistema, password_hash, rol,
     contacto_emergencia, tel_emergencia, activo, fecha_ingreso)
VALUES
-- Administrador principal
('Laura Milena',  'Restrepo Gómez',   'CC', '43892541', 'Administrador',
 '3124567890', 'lrestrepo@hotelplazahostal.com', 'Rotativo',
 'admin_laura', '$2y$10$abc123hashejemplo001', 'Admin',
 'Carlos Restrepo', '3124567891', 1, '2023-01-15'),

-- Recepcionista turno mañana
('Andrés Felipe', 'Zapata Monsalve',  'CC', '1040234567', 'Recepcionista',
 '3209876543', 'azapata@hotelplazahostal.com', 'Mañana',
 'recep_andres', '$2y$10$abc123hashejemplo002', 'Recepcion',
 'María Zapata', '3209876544', 1, '2024-03-01'),

-- Recepcionista turno tarde
('Claudia Patricia', 'Henao Ríos',    'CC', '43756234', 'Recepcionista',
 '3156789012', 'chenao@hotelplazahostal.com', 'Tarde',
 'recep_claudia', '$2y$10$abc123hashejemplo003', 'Recepcion',
 'Jorge Henao', '3156789013', 1, '2024-06-15');

-- =============================================================================
-- 2. HABITACIONES (24 habitaciones del Hotel Plaza Hostal)
-- Distribución: 10 Sencillas, 8 Dobles, 4 Triples, 2 Suites
-- Pisos: 1, 2, 3
-- =============================================================================
INSERT INTO habitaciones
    (numero_hab, tipo, piso, capacidad, precio_noche, estado,
     tiene_bano_privado, tiene_tv, tiene_ac, tiene_wifi, descripcion)
VALUES
-- ── PISO 1 ── 8 habitaciones ─────────────────────────────────────────────────
('101', 'Sencilla', 1, 1,  55000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 1, vista interior, baño privado'),
('102', 'Sencilla', 1, 1,  55000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 1, vista exterior'),
('103', 'Doble',    1, 2,  85000.00, 'Disponible',   1, 1, 0, 1, 'Habitación doble piso 1, cama doble, baño privado'),
('104', 'Doble',    1, 2,  85000.00, 'Ocupada',      1, 1, 0, 1, 'Habitación doble piso 1, dos camas individuales'),
('105', 'Sencilla', 1, 1,  55000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 1, baño privado'),
('106', 'Triple',   1, 3, 110000.00, 'Disponible',   1, 1, 0, 1, 'Habitación triple piso 1, tres camas individuales'),
('107', 'Doble',    1, 2,  85000.00, 'Mantenimiento',1, 0, 0, 1, 'En mantenimiento — reparación de baño'),
('108', 'Sencilla', 1, 1,  55000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 1 cerca a recepción'),

-- ── PISO 2 ── 8 habitaciones ─────────────────────────────────────────────────
('201', 'Sencilla', 2, 1,  60000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 2, vista a la calle'),
('202', 'Sencilla', 2, 1,  60000.00, 'Reservada',    1, 1, 0, 1, 'Habitación sencilla piso 2, baño privado'),
('203', 'Doble',    2, 2,  90000.00, 'Disponible',   1, 1, 1, 1, 'Habitación doble piso 2, aire acondicionado'),
('204', 'Doble',    2, 2,  90000.00, 'Ocupada',      1, 1, 1, 1, 'Habitación doble piso 2, A/C, vista exterior'),
('205', 'Triple',   2, 3, 115000.00, 'Disponible',   1, 1, 0, 1, 'Habitación triple piso 2, baño amplio'),
('206', 'Sencilla', 2, 1,  60000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 2, tranquila'),
('207', 'Doble',    2, 2,  90000.00, 'Disponible',   1, 1, 1, 1, 'Habitación doble piso 2, A/C, cama queen'),
('208', 'Triple',   2, 3, 115000.00, 'Disponible',   1, 1, 0, 1, 'Habitación triple piso 2, ideal para familias'),

-- ── PISO 3 ── 8 habitaciones ─────────────────────────────────────────────────
('301', 'Sencilla', 3, 1,  65000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 3, mejor vista'),
('302', 'Doble',    3, 2,  95000.00, 'Disponible',   1, 1, 1, 1, 'Habitación doble piso 3, A/C, vista panorámica'),
('303', 'Triple',   3, 3, 120000.00, 'Disponible',   1, 1, 1, 1, 'Habitación triple piso 3, A/C, baño amplio'),
('304', 'Sencilla', 3, 1,  65000.00, 'Ocupada',      1, 1, 0, 1, 'Habitación sencilla piso 3'),
('305', 'Doble',    3, 2,  95000.00, 'Disponible',   1, 1, 1, 1, 'Habitación doble piso 3, A/C, cama doble'),
('306', 'Sencilla', 3, 1,  65000.00, 'Disponible',   1, 1, 0, 1, 'Habitación sencilla piso 3, baño privado'),
('Suite1', 'Suite', 3, 4, 180000.00, 'Disponible',   1, 1, 1, 1, 'Suite principal — sala, jacuzzi, vista panorámica, A/C'),
('Suite2', 'Suite', 3, 4, 180000.00, 'Disponible',   1, 1, 1, 1, 'Suite ejecutiva — sala de trabajo, A/C, vista exterior');

-- =============================================================================
-- 3. CLIENTES (8 huéspedes de prueba de distintas ciudades)
-- =============================================================================
INSERT INTO clientes
    (tipo_documento, num_documento, nombres, apellidos,
     telefono, email, ciudad_origen, departamento_origen, observaciones)
VALUES
('CC', '71234567',  'Carlos Eduardo',  'Martínez López',
 '3101234567', 'cemartinez@gmail.com',       'Medellín',      'Antioquia',  NULL),

('CC', '32456789',  'María Fernanda',  'Ríos Castañeda',
 '3152345678', 'mfrios@hotmail.com',         'Bogotá',        'Cundinamarca', 'Prefiere habitación alta'),

('CC', '98765432',  'Jorge Iván',      'Pérez Saldarriaga',
 '3163456789', 'jiperez@gmail.com',          'Manizales',     'Caldas',     NULL),

('CC', '54321098',  'Ana Lucía',       'Gómez Vargas',
 '3174567890', 'algomez@gmail.com',          'Yarumal',       'Antioquia',  'Cliente frecuente'),

('CE', '987654321', 'Jean Pierre',     'Dubois',
 '3185678901', 'jpdubois@gmail.com',         'París',         'Francia',    'Habla español básico'),

('CC', '1023456789','Valentina',       'Ospina Herrera',
 '3196789012', 'vospina@gmail.com',          'Apartadó',      'Antioquia',  NULL),

('CC', '75432198',  'Ricardo',         'Vélez Muñoz',
 '3107890123', 'rvelez@empresa.com',         'Santa Fe de Antioquia', 'Antioquia', 'Viajero de negocios'),

('PAS','AB123456',  'Sarah',           'Johnson',
 '3118901234', 'sjohnson@gmail.com',         'Nueva York',    'Estados Unidos', 'Turista — reserva por WhatsApp');

-- =============================================================================
-- 4. RESERVAS (6 reservas de prueba con distintos estados y canales)
-- =============================================================================
INSERT INTO reservas
    (id_cliente, id_habitacion, id_personal,
     fecha_entrada, fecha_salida, num_personas,
     estado, canal_origen, precio_noche_aplicado, total_calculado, observaciones)
VALUES
-- Reserva activa — hab 104 (Doble, ocupada)
(1, 4, 2, '2026-06-05', '2026-06-08', 2,
 'Activa', 'Presencial', 85000.00, 255000.00,
 'Cliente llega desde Medellín por trabajo'),

-- Reserva activa — hab 204 (Doble piso 2, ocupada)
(3, 12, 3, '2026-06-06', '2026-06-09', 2,
 'Activa', 'WhatsApp', 90000.00, 270000.00,
 'Reserva original por WhatsApp — migrada al sistema'),

-- Reserva activa — hab 304 (Sencilla piso 3, ocupada)
(7, 20, 2, '2026-06-07', '2026-06-08', 1,
 'Activa', 'Telefono', 65000.00, 65000.00,
 'Viajero de negocios — una noche'),

-- Reserva confirmada — hab 202 (Sencilla piso 2, reservada)
(2, 10, 2, '2026-06-08', '2026-06-11', 1,
 'Confirmada', 'WhatsApp', 60000.00, 180000.00,
 'Llegada desde Bogotá — solicita hab piso alto'),

-- Reserva pendiente — futura
(5, 23, 1, '2026-06-15', '2026-06-18', 2,
 'Pendiente', 'Web', 180000.00, 540000.00,
 'Turista extranjero — Suite 1, necesita factura'),

-- Reserva finalizada — histórico
(4, 1, 3, '2026-05-20', '2026-05-22', 1,
 'Finalizada', 'Presencial', 55000.00, 110000.00,
 'Cliente local frecuente');

-- =============================================================================
-- 5. FACTURAS (2 facturas: 1 pagada del histórico + 1 pendiente de reserva activa)
-- =============================================================================
INSERT INTO facturas
    (id_reserva, id_cliente, id_personal,
     subtotal, iva_porcentaje, iva_valor, total,
     metodo_pago, estado, observaciones)
VALUES
-- Factura pagada — reserva finalizada (id_reserva=6)
(6, 4, 3,
 110000.00, 19.00, 20900.00, 130900.00,
 'Efectivo', 'Pagada',
 'Pago en efectivo al check-out — cliente frecuente Ana Lucía Gómez'),

-- Factura pendiente — reserva activa (id_reserva=1)
(1, 1, 2,
 255000.00, 19.00, 48450.00, 303450.00,
 'Transferencia', 'Pendiente',
 'Pendiente de pago al check-out — Carlos Martínez');

-- =============================================================================
-- 6. INVENTARIO (12 insumos representativos del hotel)
-- =============================================================================
INSERT INTO inventario
    (nombre_item, categoria, unidad_medida, stock_actual, stock_minimo,
     precio_unitario, proveedor, telefono_proveedor, ultima_compra)
VALUES
-- Lencería
('Toallas grandes',         'Lencería',     'unidad', 48, 20, 15000.00, 'Distribuidora Textil Norte', '3201234567', '2026-05-01'),
('Sábanas individuales',    'Lencería',     'unidad', 30, 15, 25000.00, 'Distribuidora Textil Norte', '3201234567', '2026-05-01'),
('Sábanas dobles',          'Lencería',     'unidad', 22, 12, 35000.00, 'Distribuidora Textil Norte', '3201234567', '2026-05-01'),
('Cobijas',                 'Lencería',     'unidad',  8, 10, 45000.00, 'Distribuidora Textil Norte', '3201234567', '2026-04-15'),

-- Aseo ── cobija con stock crítico para probar alerta
('Jabón líquido manos',     'Aseo',         'litro',  12, 10, 8500.00,  'Suministros Yarumal',       '3157654321', '2026-05-20'),
('Shampoo individual',      'Amenidades',   'unidad',  6, 15, 2500.00,  'Suministros Yarumal',       '3157654321', '2026-05-10'),
('Papel higiénico',         'Aseo',         'rollo',  80, 50, 1200.00,  'Suministros Yarumal',       '3157654321', '2026-05-25'),
('Desinfectante pisos',     'Aseo',         'litro',   3,  8, 12000.00, 'Suministros Yarumal',       '3157654321', '2026-04-20'),

-- Amenidades
('Gel antibacterial',       'Amenidades',   'unidad', 18, 10, 5500.00,  'Droguería Central Yarumal', '3168765432', '2026-05-15'),
('Kit dental (cepillo+pasta)','Amenidades', 'unidad',  4, 12, 3800.00,  'Droguería Central Yarumal', '3168765432', '2026-05-01'),

-- Mantenimiento
('Bombillos LED',           'Mantenimiento','unidad', 15,  8, 9500.00,  'Ferretería El Tornillo',    '3179876543', '2026-04-10'),
('Bolsas de basura',        'Oficina',      'paquete',20, 10, 4500.00,  'Suministros Yarumal',       '3157654321', '2026-05-18');

-- =============================================================================
-- Reactivar FK
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- VERIFICACIÓN DEL SEED DATA
-- =============================================================================
SELECT 'personal'     AS tabla, COUNT(*) AS registros FROM personal    UNION ALL
SELECT 'clientes',              COUNT(*)               FROM clientes    UNION ALL
SELECT 'habitaciones',          COUNT(*)               FROM habitaciones UNION ALL
SELECT 'reservas',              COUNT(*)               FROM reservas    UNION ALL
SELECT 'facturas',              COUNT(*)               FROM facturas    UNION ALL
SELECT 'inventario',            COUNT(*)               FROM inventario;

-- Verificar vistas con datos
SELECT * FROM v_dashboard_kpis;
SELECT * FROM v_stock_critico;
SELECT * FROM v_ocupacion_por_tipo;

-- =============================================================================
--  FIN SEED DATA — Semana 4, Día 3
--  Siguiente paso (Día 4): Verificar en phpMyAdmin y tomar capturas de evidencia
-- =============================================================================
