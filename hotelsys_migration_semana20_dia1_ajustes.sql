-- =============================================================================
-- hotelsys_migration_semana20_dia1_ajustes.sql
-- HotelSys — Hotel Plaza Hostal
-- Semana 20, Actividad XII — Ajuste de datos para el hallazgo #2
-- (Semana 19 Día 3: descripción de la Hab. 107 estática y desactualizada)
-- =============================================================================
-- Diagnóstico (confirmado contra hotelsys_seed_data.sql, línea 60): a
-- diferencia de las otras 23 habitaciones, cuya columna `descripcion`
-- siempre tiene texto fijo de características físicas (piso, vista, camas,
-- baño), la Hab. 107 se sembró con un mensaje de ESTADO OPERATIVO transitorio:
-- 'En mantenimiento — reparación de baño'. Ese texto nunca se actualiza con
-- el estado real de la habitación — el estado real ya lo gestiona la propia
-- columna `habitaciones.estado` y, desde la Actividad XI, la tabla
-- `mantenimientos` con sus propios registros y fechas. Tras el cierre del
-- mantenimiento "Daño eléctrico" en la Semana 19 Día 5, la Hab. 107 ya está
-- Disponible, pero su descripción seguía afirmando una reparación de baño
-- que ni siquiera fue el motivo real de ningún mantenimiento registrado.
--
-- Ajuste: reemplazar la descripción por una de características físicas
-- permanentes, en el mismo estilo que las demás habitaciones del piso 1,
-- para que no vuelva a quedar desactualizada cuando cambie el estado o se
-- registre un nuevo mantenimiento.

USE hotelsys_plaza;

UPDATE habitaciones
SET descripcion = 'Habitación doble piso 1, baño privado, cerca de recepción'
WHERE numero_hab = '107';

-- Verificación rápida:
-- SELECT numero_hab, estado, descripcion FROM habitaciones WHERE numero_hab = '107';
