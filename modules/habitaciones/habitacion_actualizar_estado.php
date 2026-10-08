<?php
// modules/habitaciones/habitacion_actualizar_estado.php
// HotelSys — Hotel Plaza Hostal
// Cambio manual de estado de habitación (solo Mantenimiento <-> Disponible)

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/check_auth.php';
// El require_once de arriba ya valida que exista sesión activa
require_once __DIR__ . '/../../includes/facturacion_helpers.php'; // resolverIdPersonalDesdeSesion()

header('Content-Type: application/json; charset=utf-8');

// Solo administrador puede ejecutar este cambio
if (!esAdmin()) {
    http_response_code(403);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Acceso denegado: solo un administrador puede cambiar el estado de mantenimiento.'
    ]);
    exit();
}

// Solo se acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$idHabitacion = filter_input(INPUT_POST, 'id_habitacion', FILTER_VALIDATE_INT);
$nuevoEstado  = trim($_POST['nuevo_estado'] ?? '');

// Solo se permiten estos dos destinos desde este endpoint
$estadosPermitidos = ['Mantenimiento', 'Disponible'];

if (!$idHabitacion || !in_array($nuevoEstado, $estadosPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos.']);
    exit();
}

$conexion = getConexion();

try {
    $conexion->beginTransaction();

    // Bloqueamos la fila para evitar condiciones de carrera
    $stmt = $conexion->prepare(
        "SELECT id_habitacion, numero_hab, estado FROM habitaciones WHERE id_habitacion = :id FOR UPDATE"
    );
    $stmt->execute([':id' => $idHabitacion]);
    $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$habitacion) {
        $conexion->rollBack();
        http_response_code(404);
        echo json_encode(['exito' => false, 'mensaje' => 'Habitación no encontrada.']);
        exit();
    }

    $estadoActual = $habitacion['estado'];

    // Regla de negocio: no se puede pasar a Mantenimiento si está Ocupada o Reservada
    if ($nuevoEstado === 'Mantenimiento' && in_array($estadoActual, ['Ocupada', 'Reservada'], true)) {
        $conexion->rollBack();
        http_response_code(409);
        echo json_encode([
            'exito' => false,
            'mensaje' => "No se puede poner en Mantenimiento: la habitación {$habitacion['numero_hab']} está actualmente {$estadoActual}. Libere o finalice la reserva primero."
        ]);
        exit();
    }

    // Regla de negocio: solo se puede volver a Disponible si estaba en Mantenimiento
    if ($nuevoEstado === 'Disponible' && $estadoActual !== 'Mantenimiento') {
        $conexion->rollBack();
        http_response_code(409);
        echo json_encode([
            'exito' => false,
            'mensaje' => "Esta acción solo aplica para habitaciones que están en Mantenimiento (estado actual: {$estadoActual})."
        ]);
        exit();
    }

    // Sin cambio real
    if ($estadoActual === $nuevoEstado) {
        $conexion->rollBack();
        echo json_encode(['exito' => true, 'mensaje' => 'La habitación ya se encuentra en ese estado.']);
        exit();
    }

    // Ajuste Semana 22 (Actividad XIII, confirmado en validación Semana 21
    // Día 2-3): registrar quién y cuándo hizo el último cambio de estado,
    // para poder identificar y corregir un cambio hecho por error.
    $update = $conexion->prepare(
        "UPDATE habitaciones
         SET estado = :nuevo_estado, actualizado_por = :actualizado_por, fecha_actualizacion = NOW()
         WHERE id_habitacion = :id"
    );
    $update->execute([
        ':nuevo_estado' => $nuevoEstado,
        ':actualizado_por' => $_SESSION['nombre'] ?? 'Administrador',
        ':id' => $idHabitacion
    ]);

    // Semana 17 Día 2 — Actividad XI: este botón rápido es un segundo camino
    // para iniciar/cerrar un mantenimiento, además del formulario nuevo
    // (views/mantenimiento_form.php). Para que el reporte de costo de
    // mantenimiento (Día 5) no tenga huecos, este atajo también deja un
    // registro en `mantenimientos` — sin motivo detallado ni costo, que el
    // administrador puede completar después editando directamente ese
    // registro desde el listado de Mantenimiento si lo necesita.
    if ($nuevoEstado === 'Mantenimiento') {
        $idPersonal = isset($_SESSION['usuario_id'])
            ? resolverIdPersonalDesdeSesion($conexion, (int) $_SESSION['usuario_id'])
            : null;

        $conexion->prepare(
            "INSERT INTO mantenimientos (id_habitacion, id_personal, motivo, fecha_inicio, costo)
             VALUES (:id_habitacion, :id_personal, 'Mantenimiento rápido (registrado desde Habitaciones)', CURDATE(), 0)"
        )->execute([
            ':id_habitacion' => $idHabitacion,
            ':id_personal'   => $idPersonal,
        ]);
    } elseif ($nuevoEstado === 'Disponible') {
        // Cierra cualquier registro de mantenimiento todavía abierto para
        // esta habitación (normalmente habrá como mucho uno).
        $conexion->prepare(
            "UPDATE mantenimientos SET fecha_fin = CURDATE()
             WHERE id_habitacion = :id AND fecha_fin IS NULL"
        )->execute([':id' => $idHabitacion]);
    }

    $conexion->commit();

    echo json_encode([
        'exito' => true,
        'mensaje' => "Habitación {$habitacion['numero_hab']} actualizada de {$estadoActual} a {$nuevoEstado} correctamente.",
        'nuevo_estado' => $nuevoEstado
    ]);

} catch (Exception $e) {
    $conexion->rollBack();
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error interno al actualizar el estado.']);
}