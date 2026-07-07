<?php
// modules/reservas/reserva_editar_procesar.php
// HotelSys — Hotel Plaza Hostal
// Actualiza fechas/habitación/personas de una reserva existente.
// Reutiliza la misma validación de solapamiento del Día 3, excluyendo
// la propia reserva de esa validación (si no, siempre chocaría consigo misma).

require_once __DIR__ . '/../../config/db.php'; // Define BASE_URL y getConexion()
require_once __DIR__ . '/../../includes/check_auth.php';

$pdo = getConexion();

function volverConError(string $msg, ?int $id = null): void {
    $_SESSION['reserva_mensaje'] = $msg;
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    $destino = $id ? 'views/reserva_editar.php?id=' . $id : 'views/reservas.php';
    header('Location: ' . BASE_URL . $destino);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volverConError('Método no permitido.');
}

$id_reserva     = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
$id_habitacion  = filter_input(INPUT_POST, 'id_habitacion', FILTER_VALIDATE_INT);
$fecha_entrada  = $_POST['fecha_entrada'] ?? '';
$fecha_salida   = $_POST['fecha_salida'] ?? '';
$num_personas   = filter_input(INPUT_POST, 'num_personas', FILTER_VALIDATE_INT);
$observaciones  = trim($_POST['observaciones'] ?? '');

if (!$id_reserva || !$id_habitacion || !$num_personas || $num_personas < 1) {
    volverConError('Todos los campos obligatorios deben estar completos.', $id_reserva);
}

$fechaEntradaObj = DateTime::createFromFormat('Y-m-d', $fecha_entrada);
$fechaSalidaObj  = DateTime::createFromFormat('Y-m-d', $fecha_salida);

if (!$fechaEntradaObj || !$fechaSalidaObj) {
    volverConError('Las fechas ingresadas no son válidas.', $id_reserva);
}
if ($fechaSalidaObj <= $fechaEntradaObj) {
    volverConError('La fecha de salida debe ser posterior a la de entrada.', $id_reserva);
}

try {
    // --- Confirmar que la reserva existe y no está cerrada ---
    $stmt = $pdo->prepare("SELECT estado FROM reservas WHERE id_reserva = :id");
    $stmt->execute([':id' => $id_reserva]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$actual) {
        volverConError('La reserva no existe.');
    }
    if (in_array($actual['estado'], ['Cancelada', 'Finalizada'], true)) {
        volverConError('No se puede editar una reserva ' . $actual['estado'] . '.');
    }

    // --- Habitación válida y su precio actual ---
    $stmtHab = $pdo->prepare(
        "SELECT precio_noche FROM habitaciones WHERE id_habitacion = :id AND activa = 1"
    );
    $stmtHab->execute([':id' => $id_habitacion]);
    $habitacion = $stmtHab->fetch(PDO::FETCH_ASSOC);

    if (!$habitacion) {
        volverConError('La habitación seleccionada no existe o no está activa.', $id_reserva);
    }

    // --- Validación de solapamiento, EXCLUYENDO esta misma reserva ---
    $stmtSolape = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM reservas
         WHERE id_habitacion = :id_habitacion
           AND id_reserva != :id_reserva
           AND estado NOT IN ('Cancelada', 'Finalizada')
           AND fecha_entrada < :fecha_salida
           AND fecha_salida > :fecha_entrada"
    );
    $stmtSolape->execute([
        ':id_habitacion' => $id_habitacion,
        ':id_reserva'    => $id_reserva,
        ':fecha_entrada' => $fecha_entrada,
        ':fecha_salida'  => $fecha_salida,
    ]);
    $solape = $stmtSolape->fetch(PDO::FETCH_ASSOC);

    if ((int)$solape['total'] > 0) {
        volverConError('Esa habitación ya tiene otra reserva activa que se cruza con esas fechas.', $id_reserva);
    }

    // --- Recalcular noches y total con los nuevos datos ---
    $intervalo = $fechaEntradaObj->diff($fechaSalidaObj);
    $num_noches = (int)$intervalo->days;
    $precio_noche_aplicado = (float)$habitacion['precio_noche'];
    $total_calculado = $num_noches * $precio_noche_aplicado;

    // --- Actualizar ---
    $stmtUpdate = $pdo->prepare(
        "UPDATE reservas
         SET id_habitacion = :id_habitacion,
             fecha_entrada = :fecha_entrada,
             fecha_salida = :fecha_salida,
             num_personas = :num_personas,
             precio_noche_aplicado = :precio_noche_aplicado,
             total_calculado = :total_calculado,
             observaciones = :observaciones
         WHERE id_reserva = :id_reserva"
    );
    $stmtUpdate->execute([
        ':id_habitacion'         => $id_habitacion,
        ':fecha_entrada'         => $fecha_entrada,
        ':fecha_salida'          => $fecha_salida,
        ':num_personas'          => $num_personas,
        ':precio_noche_aplicado' => $precio_noche_aplicado,
        ':total_calculado'       => $total_calculado,
        ':observaciones'         => $observaciones !== '' ? $observaciones : null,
        ':id_reserva'            => $id_reserva,
    ]);

    $_SESSION['reserva_mensaje'] = 'Reserva actualizada correctamente.';
    $_SESSION['reserva_mensaje_tipo'] = 'exito';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();

} catch (PDOException $e) {
    error_log('Error al editar reserva: ' . $e->getMessage());
    volverConError('Ocurrió un error al guardar los cambios. Intenta de nuevo.', $id_reserva);
}