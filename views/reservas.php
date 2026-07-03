<?php
// views/reservas.php — Módulo de Reservas: listado y creación
// HotelSys — Hotel Plaza Hostal

require_once __DIR__ . '/../config/db.php'; // Define BASE_URL y getConexion()
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

// Mensajes flash (éxito/error) que vienen del procesador
$mensaje = $_SESSION['reserva_mensaje'] ?? null;
$tipoMensaje = $_SESSION['reserva_mensaje_tipo'] ?? 'error'; // 'exito' | 'error'
unset($_SESSION['reserva_mensaje'], $_SESSION['reserva_mensaje_tipo']);

// --- Datos para el formulario ---

// Habitaciones disponibles (solo estas se pueden reservar)
$stmtHab = $pdo->query(
    "SELECT id_habitacion, numero_hab, tipo, precio_noche
     FROM habitaciones
     WHERE estado = 'Disponible' AND activa = 1
     ORDER BY numero_hab"
);
$habitaciones = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

// Clientes registrados
$stmtCli = $pdo->query(
    "SELECT id_cliente, nombres, apellidos, num_documento
     FROM clientes
     WHERE activo = 1
     ORDER BY apellidos, nombres"
);
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

// --- Listado de reservas activas ---
// Nota: no usamos v_reservas_activas porque esa vista solo incluye
// reservas en estado Confirmada/Activa, y excluye las Pendiente
// (estado por defecto al crear una reserva nueva).
$stmtReservas = $pdo->query(
    "SELECT r.id_reserva,
            CONCAT(c.nombres, ' ', c.apellidos) AS huesped,
            c.telefono AS tel_huesped,
            h.numero_hab,
            h.tipo AS tipo_hab,
            r.fecha_entrada,
            r.fecha_salida,
            r.num_noches,
            r.num_personas,
            r.total_calculado,
            r.canal_origen,
            r.estado
     FROM reservas r
     JOIN clientes c ON c.id_cliente = r.id_cliente
     JOIN habitaciones h ON h.id_habitacion = r.id_habitacion
     WHERE r.estado NOT IN ('Cancelada', 'Finalizada')
     ORDER BY r.fecha_entrada DESC"
);
$reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas — HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
</head>
<body>

<h1>Módulo de Reservas — Hotel Plaza Hostal</h1>

<?php if ($mensaje): ?>
    <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<h2>Nueva reserva</h2>
<form action="<?= BASE_URL ?>modules/reservas/reserva_procesar.php" method="POST">

    <label for="id_cliente">Cliente:</label>
    <select name="id_cliente" id="id_cliente" required>
        <option value="">-- Seleccione un cliente --</option>
        <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id_cliente'] ?>">
                <?= htmlspecialchars($c['nombres'] . ' ' . $c['apellidos'] . ' — ' . $c['num_documento']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="id_habitacion">Habitación disponible:</label>
    <select name="id_habitacion" id="id_habitacion" required>
        <option value="">-- Seleccione una habitación --</option>
        <?php foreach ($habitaciones as $h): ?>
            <option value="<?= (int)$h['id_habitacion'] ?>" data-precio="<?= htmlspecialchars($h['precio_noche']) ?>">
                <?= htmlspecialchars($h['numero_hab'] . ' — ' . $h['tipo'] . ' ($' . number_format($h['precio_noche'], 0, ',', '.') . '/noche)') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="fecha_entrada">Fecha de entrada:</label>
    <input type="date" name="fecha_entrada" id="fecha_entrada" required>

    <label for="fecha_salida">Fecha de salida:</label>
    <input type="date" name="fecha_salida" id="fecha_salida" required>

    <label for="num_personas">Número de personas:</label>
    <input type="number" name="num_personas" id="num_personas" min="1" value="1" required>

    <label for="canal_origen">Canal de origen:</label>
    <select name="canal_origen" id="canal_origen" required>
        <option value="WhatsApp">WhatsApp</option>
        <option value="Telefono">Teléfono</option>
        <option value="Presencial">Presencial</option>
        <option value="Web">Web</option>
    </select>

    <label for="observaciones">Observaciones (opcional):</label>
    <textarea name="observaciones" id="observaciones" rows="2"></textarea>

    <button type="submit">Crear reserva</button>
</form>

<h2>Reservas activas</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Huésped</th>
            <th>Teléfono</th>
            <th>Habitación</th>
            <th>Tipo</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Noches</th>
            <th>Personas</th>
            <th>Total</th>
            <th>Canal</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reservas)): ?>
            <tr><td colspan="11">No hay reservas activas registradas.</td></tr>
        <?php else: ?>
            <?php foreach ($reservas as $r): ?>
                <tr>
                    <td><?= (int)$r['id_reserva'] ?></td>
                    <td><?= htmlspecialchars($r['huesped']) ?></td>
                    <td><?= htmlspecialchars($r['tel_huesped']) ?></td>
                    <td><?= htmlspecialchars($r['numero_hab']) ?></td>
                    <td><?= htmlspecialchars($r['tipo_hab']) ?></td>
                    <td><?= htmlspecialchars($r['fecha_entrada']) ?></td>
                    <td><?= htmlspecialchars($r['fecha_salida']) ?></td>
                    <td><?= (int)$r['num_noches'] ?></td>
                    <td><?= (int)$r['num_personas'] ?></td>
                    <td>$<?= number_format($r['total_calculado'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($r['canal_origen']) ?></td>
                    <td><?= htmlspecialchars($r['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>