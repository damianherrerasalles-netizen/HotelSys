-- =============================================================================
-- hotelsys_migration_semana22_ajuste1_auditoria_habitaciones.sql
-- HotelSys — Hotel Plaza Hostal
-- Semana 22, Actividad XIII — Ajuste #1 (confirmado en la sesión de
-- validación simulada de la Semana 21 Día 2-3, con "la administración del
-- Hotel Plaza Hostal"): "Sí nos gustaría poder ver quién fue el último en
-- cambiar el estado de una habitación, por si alguien se equivoca y hay
-- que corregir".
-- =============================================================================
-- Diagnóstico (verificado contra el código real en la Semana 21 Día 3,
-- antes de dar este ajuste por necesario): ningún UPDATE habitaciones SET
-- estado... en todo el proyecto dejaba rastro de quién hizo el cambio ni
-- cuándo. Se confirmaron 6 puntos de actualización de `habitaciones.estado`
-- en 3 archivos distintos:
--   - modules/habitaciones/habitacion_actualizar_estado.php (cambio manual
--     rápido Mantenimiento <-> Disponible)
--   - modules/reservas/reserva_actualizar_estado.php (Activa, Confirmada,
--     Cancelada/Finalizada — 3 UPDATE)
--   - includes/mantenimiento_helpers.php (marcarHabitacionEnMantenimiento
--     y liberarHabitacionDeMantenimiento)
--
-- Ajuste: se agregan dos columnas nuevas a `habitaciones` para registrar el
-- último cambio de estado. Mismo patrón ya usado desde la Semana 12 Día 2
-- en alertas_inventario.atendida_por (includes del módulo de Inventario):
-- se guarda el nombre de sesión ($_SESSION['nombre'] ?? 'Administrador'),
-- no un id_usuario, para no exigir un JOIN solo para mostrarlo en la vista
-- de detalle de habitación. Ambas columnas quedan NULL para las filas ya
-- existentes, ya que no hay forma de reconstruir ese historial retroactivo;
-- la vista de detalle solo muestra el bloque cuando el dato ya existe.

USE hotelsys_plaza;

ALTER TABLE habitaciones
    ADD COLUMN actualizado_por VARCHAR(100) NULL
        COMMENT 'Semana 22 Ajuste #1: nombre de quien hizo el último cambio de estado',
    ADD COLUMN fecha_actualizacion DATETIME NULL
        COMMENT 'Semana 22 Ajuste #1: fecha/hora del último cambio de estado';

-- Verificación rápida:
-- DESCRIBE habitaciones;
-- SELECT id_habitacion, numero_hab, estado, actualizado_por, fecha_actualizacion FROM habitaciones LIMIT 5;
