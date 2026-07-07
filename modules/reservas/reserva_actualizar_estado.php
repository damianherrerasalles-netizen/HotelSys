<?php
// modules/reservas/reserva_actualizar_estado.php
// HotelSys — Hotel Plaza Hostal
// Cambia el estado de una reserva y sincroniza el estado de la habitación.
// Reglas:
//   - Confirmar / Check-in / Finalizar: administrador y recepcionista
//   - Cancelar: SOLO administrador
//   - Activa      -> habitacion pasa a 'Ocupada'
//   - Cancelada / Finalizada -> habitacion vuelve a 'Disponible'

require_once __DIR__ . '/../../config/db.php'; // Define BASE_URL y getConexion()
require_once __DIR__ . '/../../includes/check_auth.php';

$pdo = getConexion();

function volverConMensaje(string $msg, string $tipo = 'error'): void {
    $_SESSION['reserva_mensaje'] = $msg;
    $_SESSION['reserva_mensaje_tipo'] = $tipo;
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volverConMensaje('Método no permitido.');
}

$id_reserva     = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
$nuevo_estado   = $_POST['nuevo_estado'] ?? '';
$motivo_cancel  = trim($_POST['motivo_cancelacion'] ?? '');

$estadosValidos = ['Confirmada', 'Activa', 'Finalizada', 'Cancelada'];

if (!$id_reserva || !in_array($nuevo_estado, $estadosValidos, true)) {
    volverConMensaje('Datos inválidos para actualizar la reserva.');
}

// --- Regla de permisos: cancelar es exclusivo de administrador ---
if ($nuevo_estado === 'Cancelada' && !esAdmin()) {
    volverConMensaje('Solo un administrador puede cancelar una reserva.');
}

// Si se cancela, exigir un motivo (columna motivo_cancelacion ya existe en la tabla)
if ($nuevo_estado === 'Cancelada' && $motivo_cancel === '') {
    volverConMensaje('Debes indicar un motivo para cancelar la reserva.');
}

try {
    // --- Obtener la reserva actual para saber su habitación y estado previo ---
    $stmt = $pdo->prepare(
        "SELECT id_habitacion, estado FROM reservas WHERE id_reserva = :id"
    );
    $stmt->execute([':id' => $id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        volverConMensaje('La reserva no existe.');
    }

    // No permitir cambios sobre reservas ya cerradas
    if (in_array($reserva['estado'], ['Cancelada', 'Finalizada'], true)) {
        volverConMensaje('Esta reserva ya está cerrada (' . $reserva['estado'] . ') y no se puede modificar.');
    }

    $pdo->beginTransaction();

    // --- Actualizar estado de la reserva ---
    if ($nuevo_estado === 'Cancelada') {
        $stmtUpdate = $pdo->prepare(
            "UPDATE reservas
             SET estado = :estado, motivo_cancelacion = :motivo
             WHERE id_reserva = :id"
        );
        $stmtUpdate->execute([
            ':estado' => $nuevo_estado,
            ':motivo' => $motivo_cancel,
            ':id'     => $id_reserva,
        ]);
    } else {
        $stmtUpdate = $pdo->prepare(
            "UPDATE reservas SET estado = :estado WHERE id_reserva = :id"
        );
        $stmtUpdate->execute([
            ':estado' => $nuevo_estado,
            ':id'     => $id_reserva,
        ]);
    }

    // --- Sincronizar estado de la habitación ---
    if ($nuevo_estado === 'Activa') {
        $stmtHab = $pdo->prepare(
            "UPDATE habitaciones SET estado = 'Ocupada' WHERE id_habitacion = :id"
        );
        $stmtHab->execute([':id' => $reserva['id_habitacion']]);
    } elseif (in_array($nuevo_estado, ['Cancelada', 'Finalizada'], true)) {
        $stmtHab = $pdo->prepare(
            "UPDATE habitaciones SET estado = 'Disponible' WHERE id_habitacion = :id"
        );
        $stmtHab->execute([':id' => $reserva['id_habitacion']]);
    }
    // Si el nuevo estado es 'Confirmada', la habitación no cambia todavía
    // (solo cambia a 'Ocupada' en el check-in, es decir, al pasar a 'Activa').

    $pdo->commit();

    volverConMensaje("Reserva actualizada a estado '{$nuevo_estado}' correctamente.", 'exito');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al actualizar estado de reserva: ' . $e->getMessage());
    volverConMensaje('Ocurrió un error al actualizar la reserva. Intenta de nuevo.');
}