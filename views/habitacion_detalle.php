<?php
// views/habitacion_detalle.php
// HotelSys — Hotel Plaza Hostal
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/check_auth.php';
// El require_once de arriba ya protege esta vista (redirige a login si no hay sesión)

$conexion = getConexion();

$idHabitacion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idHabitacion) {
    header('Location: habitaciones.php');
    exit();
}

// Datos completos de la habitación
$stmtHab = $conexion->prepare(
    "SELECT * FROM habitaciones WHERE id_habitacion = :id"
);
$stmtHab->execute([':id' => $idHabitacion]);
$habitacion = $stmtHab->fetch(PDO::FETCH_ASSOC);

if (!$habitacion) {
    header('Location: habitaciones.php');
    exit();
}

// Historial de reservas de esta habitación (JOIN con clientes)
$stmtReservas = $conexion->prepare(
    "SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, r.num_noches, r.num_personas,
            r.estado, r.canal_origen, r.precio_noche_aplicado, r.total_calculado,
            c.nombres, c.apellidos, c.telefono, c.num_documento
     FROM reservas r
     INNER JOIN clientes c ON c.id_cliente = r.id_cliente
     WHERE r.id_habitacion = :id_habitacion
     ORDER BY r.fecha_entrada DESC"
);
$stmtReservas->execute([':id_habitacion' => $idHabitacion]);
$historial = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);

function colorEstadoHabitacion($estado) {
    switch ($estado) {
        case 'Disponible':    return '#2E7D32';
        case 'Ocupada':       return '#C62828';
        case 'Mantenimiento': return '#F9A825';
        case 'Reservada':     return '#1565C0';
        default:              return '#757575';
    }
}

function colorEstadoReserva($estado) {
    switch ($estado) {
        case 'Confirmada': return '#1565C0';
        case 'Activa':     return '#2E7D32';
        case 'Finalizada': return '#757575';
        case 'Cancelada':  return '#C62828';
        case 'Pendiente':  return '#F9A825';
        default:           return '#757575';
    }
}

// Amenidades booleanas presentes en la fila (se listan dinámicamente)
$amenidadesLabels = [
    'tiene_bano_privado' => 'Baño privado',
    'tiene_tv'           => 'TV',
    'tiene_ac'           => 'Aire acondicionado',
    'tiene_wifi'         => 'WiFi',
];
$amenidadesActivas = [];
foreach ($amenidadesLabels as $campo => $label) {
    if (array_key_exists($campo, $habitacion) && (int)$habitacion[$campo] === 1) {
        $amenidadesActivas[] = $label;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hab. <?= htmlspecialchars($habitacion['numero_hab']) ?> - HotelSys</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f4f6f5; color: #222; padding: 30px; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header h1 { color: #2E7D32; font-size: 24px; }
        .btn-volver {
            background-color: #2E7D32; color: #fff; text-decoration: none;
            padding: 8px 16px; border-radius: 6px; font-size: 14px;
        }
        .btn-volver:hover { background-color: #256428; }

        .panel {
            background: #fff; border-radius: 10px; padding: 20px 25px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.08); margin-bottom: 25px;
            border-left: 6px solid <?= colorEstadoHabitacion($habitacion['estado']) ?>;
        }

        .panel-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 12px;
        }
        .dato p:first-child { font-size: 12px; color: #757575; margin-bottom: 3px; }
        .dato p:last-child  { font-size: 16px; font-weight: 600; color: #222; }

        .badge {
            display: inline-block; color: #fff; padding: 4px 14px;
            border-radius: 12px; font-size: 13px; font-weight: 600;
        }

        .amenidades { margin-top: 16px; }
        .amenidad-tag {
            display: inline-block; background: #E8F5E9; color: #2E7D32;
            padding: 4px 10px; border-radius: 6px; font-size: 12px;
            margin-right: 6px; margin-bottom: 6px;
        }

        h2.seccion { color: #2E7D32; font-size: 18px; margin-bottom: 12px; }

        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; }
        thead th {
            background-color: #2E7D32; color: #fff; text-align: left;
            padding: 10px 12px; font-size: 13px;
        }
        tbody td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #eee; }
        tbody tr:hover { background-color: #f9fdf9; }

        .badge-reserva {
            display: inline-block; color: #fff; padding: 3px 10px;
            border-radius: 10px; font-size: 12px; font-weight: 600;
        }

        .sin-historial {
            background: #fff; padding: 30px; border-radius: 10px; text-align: center; color: #777;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>🏨 Habitación <?= htmlspecialchars($habitacion['numero_hab']) ?></h1>
        <a href="habitaciones.php" class="btn-volver">← Volver a Habitaciones</a>
    </div>

    <div class="panel">
        <span class="badge" style="background-color: <?= colorEstadoHabitacion($habitacion['estado']) ?>;">
            <?= htmlspecialchars($habitacion['estado']) ?>
        </span>

        <div class="panel-grid">
            <div class="dato">
                <p>Tipo</p>
                <p><?= htmlspecialchars($habitacion['tipo']) ?></p>
            </div>
            <div class="dato">
                <p>Piso</p>
                <p><?= htmlspecialchars($habitacion['piso']) ?></p>
            </div>
            <div class="dato">
                <p>Capacidad</p>
                <p><?= htmlspecialchars($habitacion['capacidad']) ?> pax</p>
            </div>
            <div class="dato">
                <p>Precio por noche</p>
                <p>$<?= number_format($habitacion['precio_noche'], 0, ',', '.') ?></p>
            </div>
        </div>

        <?php if (!empty($amenidadesActivas)): ?>
            <div class="amenidades">
                <p style="font-size:12px; color:#757575; margin-bottom:6px;">Amenidades</p>
                <?php foreach ($amenidadesActivas as $am): ?>
                    <span class="amenidad-tag"><?= htmlspecialchars($am) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($habitacion['descripcion'])): ?>
            <div style="margin-top: 14px;">
               <p style="font-size:12px; color:#757575; margin-bottom:4px;">Descripción</p>
               <p style="font-size:14px; color:#444;"><?= nl2br(htmlspecialchars($habitacion['descripcion'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <h2 class="seccion">Historial de reservas</h2>

    <?php if (count($historial) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Huésped</th>
                    <th>Documento</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Noches</th>
                    <th>Personas</th>
                    <th>Canal</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $r): ?>
                    <tr>
                        <td>#<?= $r['id_reserva'] ?></td>
                        <td><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?></td>
                        <td><?= htmlspecialchars($r['num_documento']) ?></td>
                        <td><?= htmlspecialchars($r['fecha_entrada']) ?></td>
                        <td><?= htmlspecialchars($r['fecha_salida']) ?></td>
                        <td><?= htmlspecialchars($r['num_noches']) ?></td>
                        <td><?= htmlspecialchars($r['num_personas']) ?></td>
                        <td><?= htmlspecialchars($r['canal_origen']) ?></td>
                        <td>$<?= number_format($r['total_calculado'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge-reserva" style="background-color: <?= colorEstadoReserva($r['estado']) ?>;">
                                <?= htmlspecialchars($r['estado']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="sin-historial">
            <p>Esta habitación aún no registra reservas.</p>
        </div>
    <?php endif; ?>

</body>
</html>