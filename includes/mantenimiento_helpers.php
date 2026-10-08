<?php
// includes/mantenimiento_helpers.php
// Semana 17 Día 2 — Actividad XI (Módulo de Reportes).
//
// Reutiliza exactamente las mismas reglas de negocio que ya existían en
// modules/habitaciones/habitacion_actualizar_estado.php (módulo cerrado,
// no se toca) para los únicos dos destinos de estado que ese endpoint ya
// validaba: no se puede pasar a Mantenimiento si la habitación está
// Ocupada o Reservada, y solo se puede volver a Disponible si estaba en
// Mantenimiento. Se asume que ya se está dentro de una transacción abierta
// por quien llama.

// --- Marca la habitación en Mantenimiento al abrir un registro. ---
// Lanza RuntimeException con un mensaje listo para mostrar si la regla de
// negocio no lo permite (habitación Ocupada o Reservada).
function marcarHabitacionEnMantenimiento(PDO $pdo, int $idHabitacion): void {
    $stmt = $pdo->prepare(
        "SELECT numero_hab, estado FROM habitaciones WHERE id_habitacion = :id FOR UPDATE"
    );
    $stmt->execute([':id' => $idHabitacion]);
    $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$habitacion) {
        throw new RuntimeException('La habitación seleccionada no existe.');
    }

    if (in_array($habitacion['estado'], ['Ocupada', 'Reservada'], true)) {
        throw new RuntimeException(
            "No se puede iniciar el mantenimiento: la habitación {$habitacion['numero_hab']} está actualmente {$habitacion['estado']}."
        );
    }

    // Ya está en Mantenimiento (por ejemplo, otro registro abierto) — no hay
    // nada que cambiar, pero no es un error.
    if ($habitacion['estado'] === 'Mantenimiento') {
        return;
    }

    // Ajuste Semana 22 (Actividad XIII): registrar quién y cuándo cambió el
    // estado, mismo criterio ya usado en habitacion_actualizar_estado.php.
    $pdo->prepare(
        "UPDATE habitaciones
         SET estado = 'Mantenimiento', actualizado_por = :actualizado_por, fecha_actualizacion = NOW()
         WHERE id_habitacion = :id"
    )->execute([
        ':actualizado_por' => $_SESSION['nombre'] ?? 'Administrador',
        ':id' => $idHabitacion,
    ]);
}

// --- Libera la habitación (vuelve a Disponible) al cerrar un registro. ---
// Solo actúa si la habitación sigue en Mantenimiento Y no quedan otros
// registros de mantenimiento en curso para la misma habitación (una
// habitación puede, en teoría, tener más de un registro abierto).
function liberarHabitacionDeMantenimiento(PDO $pdo, int $idHabitacion): void {
    $stmtOtros = $pdo->prepare(
        "SELECT COUNT(*) AS total FROM mantenimientos
         WHERE id_habitacion = :id AND fecha_fin IS NULL"
    );
    $stmtOtros->execute([':id' => $idHabitacion]);
    if ((int) $stmtOtros->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
        return; // todavía hay otro mantenimiento en curso para esta habitación
    }

    $stmt = $pdo->prepare(
        "SELECT estado FROM habitaciones WHERE id_habitacion = :id FOR UPDATE"
    );
    $stmt->execute([':id' => $idHabitacion]);
    $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($habitacion && $habitacion['estado'] === 'Mantenimiento') {
        $pdo->prepare(
            "UPDATE habitaciones
             SET estado = 'Disponible', actualizado_por = :actualizado_por, fecha_actualizacion = NOW()
             WHERE id_habitacion = :id"
        )->execute([
            ':actualizado_por' => $_SESSION['nombre'] ?? 'Administrador',
            ':id' => $idHabitacion,
        ]);
    }
}
