<?php
// modules/facturacion/cargo_extra_registrar.php
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 2 — Actividad X: registra un cobro extra sobre una reserva activa.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../includes/facturacion_helpers.php';

$pdo = getConexion();

function volverAlFormulario(int $idReserva, string $msg, string $tipo = 'error'): void {
    $_SESSION['cargo_mensaje'] = $msg;
    $_SESSION['cargo_mensaje_tipo'] = $tipo;
    header('Location: ' . BASE_URL . 'views/cargo_extra_form.php?id=' . $idReserva);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

$id_reserva  = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
$tipo        = $_POST['tipo'] ?? '';
$descripcion = trim($_POST['descripcion'] ?? '');
$valor       = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

$tiposValidos = ['Minibar', 'Daño', 'Penalización'];

if (!$id_reserva) {
    $_SESSION['reserva_mensaje'] = 'Reserva inválida.';
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

if (!in_array($tipo, $tiposValidos, true)) {
    volverAlFormulario($id_reserva, 'Selecciona un tipo de cargo válido.');
}

if ($valor === false || $valor === null || $valor <= 0) {
    volverAlFormulario($id_reserva, 'El valor del cargo debe ser un número mayor a cero.');
}

if (mb_strlen($descripcion) > 150) {
    volverAlFormulario($id_reserva, 'La descripción no puede superar 150 caracteres.');
}

try {
    // --- Confirmar que la reserva sigue activa (pudo cambiar entre la carga del formulario y el envío) ---
    $stmt = $pdo->prepare("SELECT estado FROM reservas WHERE id_reserva = :id");
    $stmt->execute([':id' => $id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        $_SESSION['reserva_mensaje'] = 'La reserva indicada no existe.';
        $_SESSION['reserva_mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . 'views/reservas.php');
        exit();
    }

    if ($reserva['estado'] !== 'Activa') {
        $_SESSION['reserva_mensaje'] = 'Esa reserva ya no está activa; no se pudo registrar el cargo.';
        $_SESSION['reserva_mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . 'views/reservas.php');
        exit();
    }

    $idPersonal = resolverIdPersonalDesdeSesion($pdo, (int) $_SESSION['usuario_id']);

    $stmtInsertar = $pdo->prepare(
        "INSERT INTO cargos_extras (id_reserva, tipo, descripcion, valor, id_personal)
         VALUES (:id_reserva, :tipo, :descripcion, :valor, :id_personal)"
    );
    $stmtInsertar->execute([
        ':id_reserva'  => $id_reserva,
        ':tipo'        => $tipo,
        ':descripcion' => $descripcion !== '' ? $descripcion : null,
        ':valor'       => $valor,
        ':id_personal' => $idPersonal,
    ]);

    $_SESSION['cargo_mensaje'] = "Cargo de '{$tipo}' por \$" . number_format($valor, 0, ',', '.') . ' registrado correctamente.';
    $_SESSION['cargo_mensaje_tipo'] = 'exito';
    header('Location: ' . BASE_URL . 'views/cargo_extra_form.php?id=' . $id_reserva);
    exit();

} catch (PDOException $e) {
    error_log('Error al registrar cargo extra: ' . $e->getMessage());
    // TEMPORAL — Semana 15 Día 2, solo para depurar: muestra el mensaje real de la BD.
    volverAlFormulario($id_reserva, 'Ocurrió un error al registrar el cargo: ' . $e->getMessage());
}
