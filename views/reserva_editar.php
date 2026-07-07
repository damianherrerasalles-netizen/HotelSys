<?php
// views/reserva_editar.php — Formulario para editar una reserva existente
// HotelSys — Hotel Plaza Hostal

require_once __DIR__ . '/../config/db.php'; // Define BASE_URL y getConexion()
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

$id_reserva = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_reserva) {
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

// --- Cargar la reserva actual ---
$stmt = $pdo->prepare(
    "SELECT r.*, CONCAT(c.nombres, ' ', c.apellidos) AS huesped
     FROM reservas r
     JOIN clientes c ON c.id_cliente = r.id_cliente
     WHERE r.id_reserva = :id"
);
$stmt->execute([':id' => $id_reserva]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    $_SESSION['reserva_mensaje'] = 'La reserva que intentas editar no existe.';
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

// No se puede editar una reserva ya cerrada
if (in_array($reserva['estado'], ['Cancelada', 'Finalizada'], true)) {
    $_SESSION['reserva_mensaje'] = 'No se puede editar una reserva ' . $reserva['estado'] . '.';
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

// --- Habitaciones para el dropdown: disponibles + la que ya tiene esta reserva ---
$stmtHab = $pdo->prepare(
    "SELECT id_habitacion, numero_hab, tipo, precio_noche
     FROM habitaciones
     WHERE (estado = 'Disponible' OR id_habitacion = :id_hab_actual) AND activa = 1
     ORDER BY numero_hab"
);
$stmtHab->execute([':id_hab_actual' => $reserva['id_habitacion']]);
$habitaciones = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_SESSION['reserva_mensaje'] ?? null;
$tipoMensaje = $_SESSION['reserva_mensaje_tipo'] ?? 'error';
unset($_SESSION['reserva_mensaje'], $_SESSION['reserva_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Reserva #<?= (int)$reserva['id_reserva'] ?> — HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
</head>
<body>

<h1>Editar Reserva #<?= (int)$reserva['id_reserva'] ?></h1>
<p>Huésped: <strong><?= htmlspecialchars($reserva['huesped']) ?></strong> — Estado actual: <strong><?= htmlspecialchars($reserva['estado']) ?></strong></p>

<?php if ($mensaje): ?>
    <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>modules/reservas/reserva_editar_procesar.php" method="POST">
    <input type="hidden" name="id_reserva" value="<?= (int)$reserva['id_reserva'] ?>">

    <label for="id_habitacion">Habitación:</label>
    <select name="id_habitacion" id="id_habitacion" required>
        <?php foreach ($habitaciones as $h): ?>
            <option value="<?= (int)$h['id_habitacion'] ?>"
                <?= ((int)$h['id_habitacion'] === (int)$reserva['id_habitacion']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($h['numero_hab'] . ' — ' . $h['tipo'] . ' ($' . number_format($h['precio_noche'], 0, ',', '.') . '/noche)') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="fecha_entrada">Fecha de entrada:</label>
    <input type="date" name="fecha_entrada" id="fecha_entrada" value="<?= htmlspecialchars($reserva['fecha_entrada']) ?>" required>

    <label for="fecha_salida">Fecha de salida:</label>
    <input type="date" name="fecha_salida" id="fecha_salida" value="<?= htmlspecialchars($reserva['fecha_salida']) ?>" required>

    <label for="num_personas">Número de personas:</label>
    <input type="number" name="num_personas" id="num_personas" min="1" value="<?= (int)$reserva['num_personas'] ?>" required>

    <label for="observaciones">Observaciones:</label>
    <textarea name="observaciones" id="observaciones" rows="2"><?= htmlspecialchars($reserva['observaciones'] ?? '') ?></textarea>

    <button type="submit">Guardar cambios</button>
    <a href="<?= BASE_URL ?>views/reservas.php">Cancelar edición</a>
</form>

</body>
</html>