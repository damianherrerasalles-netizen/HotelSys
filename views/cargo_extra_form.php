<?php
// views/cargo_extra_form.php — Registrar un cobro extra (minibar, daño, late check-out)
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 2 — Actividad X: Módulo de Facturación

require_once __DIR__ . '/../config/db.php'; // Define BASE_URL y getConexion()
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

$id_reserva = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_reserva) {
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

// --- Cargar la reserva: solo se pueden agregar cargos a una estadía activa ---
$stmt = $pdo->prepare(
    "SELECT r.id_reserva, r.estado, r.fecha_entrada, r.fecha_salida,
            CONCAT(c.nombres, ' ', c.apellidos) AS huesped,
            h.numero_hab, h.tipo AS tipo_hab
     FROM reservas r
     JOIN clientes c ON c.id_cliente = r.id_cliente
     JOIN habitaciones h ON h.id_habitacion = r.id_habitacion
     WHERE r.id_reserva = :id"
);
$stmt->execute([':id' => $id_reserva]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    $_SESSION['reserva_mensaje'] = 'La reserva indicada no existe.';
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

if ($reserva['estado'] !== 'Activa') {
    $_SESSION['reserva_mensaje'] = 'Solo se pueden registrar cargos extra sobre una estadía activa (check-in ya hecho, check-out pendiente).';
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

// --- Cargos ya registrados para esta reserva (contexto para el recepcionista) ---
$stmtCargos = $pdo->prepare(
    "SELECT tipo, descripcion, valor, fecha
     FROM cargos_extras
     WHERE id_reserva = :id
     ORDER BY fecha DESC"
);
$stmtCargos->execute([':id' => $id_reserva]);
$cargosExistentes = $stmtCargos->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_SESSION['cargo_mensaje'] ?? null;
$tipoMensaje = $_SESSION['cargo_mensaje_tipo'] ?? 'error';
unset($_SESSION['cargo_mensaje'], $_SESSION['cargo_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargo extra — Reserva #<?= (int)$reserva['id_reserva'] ?> — HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        .contenedor-angosto { max-width: 560px; margin: 24px auto; padding: 0 20px; }
        table.tabla-cargos { width: 100%; border-collapse: collapse; margin: 10px 0 20px; }
        table.tabla-cargos th, table.tabla-cargos td {
            border: 1px solid var(--gris-borde); padding: 6px 10px; font-size: 13px; text-align: left;
        }
        table.tabla-cargos th { background: var(--verde-clar); color: var(--verde); }
    </style>
</head>
<body>

<div class="contenedor-angosto">
    <h1>Registrar cargo extra</h1>
    <p>
        Reserva #<?= (int)$reserva['id_reserva'] ?> — <strong><?= htmlspecialchars($reserva['huesped']) ?></strong><br>
        Habitación <?= htmlspecialchars($reserva['numero_hab']) ?> (<?= htmlspecialchars($reserva['tipo_hab']) ?>)
        — <?= htmlspecialchars($reserva['fecha_entrada']) ?> a <?= htmlspecialchars($reserva['fecha_salida']) ?>
    </p>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($cargosExistentes)): ?>
        <h3>Cargos ya registrados en esta estadía</h3>
        <table class="tabla-cargos">
            <thead>
                <tr><th>Tipo</th><th>Descripción</th><th>Valor</th><th>Fecha</th></tr>
            </thead>
            <tbody>
                <?php foreach ($cargosExistentes as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['tipo']) ?></td>
                        <td><?= htmlspecialchars($c['descripcion'] ?? '—') ?></td>
                        <td>$<?= number_format($c['valor'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($c['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>modules/facturacion/cargo_extra_registrar.php" method="POST">
        <input type="hidden" name="id_reserva" value="<?= (int)$reserva['id_reserva'] ?>">

        <label for="tipo">Tipo de cargo:</label>
        <select name="tipo" id="tipo" required>
            <option value="Minibar">Minibar</option>
            <option value="Daño">Daño a mobiliario</option>
            <option value="Penalización">Penalización (ej. late check-out)</option>
        </select>

        <label for="descripcion">Descripción (opcional):</label>
        <input type="text" name="descripcion" id="descripcion" maxlength="150" placeholder="Ej. 2 gaseosas, silla rota, salida 2 horas tarde">

        <label for="valor">Valor ($):</label>
        <input type="number" name="valor" id="valor" min="1" step="1" required>

        <button type="submit" class="btn btn-primario">Registrar cargo</button>
        <a href="<?= BASE_URL ?>views/reservas.php" class="btn btn-texto">Volver a Reservas</a>
    </form>
</div>

</body>
</html>
