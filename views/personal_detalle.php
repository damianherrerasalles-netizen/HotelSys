<?php
// views/personal_detalle.php
$rutaBase = '../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
// Vista de solo lectura: cualquier usuario autenticado puede consultarla
// (no requiere requerirAdmin(), a diferencia de personal_form.php)

$pdo = getConexion();

if (!isset($_GET['id']) || $_GET['id'] === '') {
    $_SESSION['personal_mensaje'] = 'No se especificó un colaborador válido.';
    $_SESSION['personal_mensaje_tipo'] = 'error';
    header('Location: personal.php');
    exit;
}

$id = (int) $_GET['id'];

// ---- Datos generales del colaborador ----
$stmt = $pdo->prepare(
    "SELECT id_personal, nombres, apellidos, tipo_documento, num_documento,
            cargo, telefono, email, turno, contacto_emergencia,
            tel_emergencia, activo, fecha_ingreso
     FROM personal
     WHERE id_personal = :id"
);
$stmt->execute([':id' => $id]);
$colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colaborador) {
    $_SESSION['personal_mensaje'] = 'El colaborador solicitado no existe.';
    $_SESSION['personal_mensaje_tipo'] = 'error';
    header('Location: personal.php');
    exit;
}

// ---- KPI: reservas gestionadas por este colaborador ----
// Nota: reservas.id_personal actualmente no se está poblando desde
// reserva_procesar.php (no existe vínculo entre usuarios y personal),
// por lo que estos conteos pueden legítimamente salir en 0.
$stmtTotalGestionadas = $pdo->prepare(
    "SELECT COUNT(*) FROM reservas WHERE id_personal = :id"
);
$stmtTotalGestionadas->execute([':id' => $id]);
$totalGestionadas = (int) $stmtTotalGestionadas->fetchColumn();

$stmtActivasGestionadas = $pdo->prepare(
    "SELECT COUNT(*) FROM reservas
     WHERE id_personal = :id AND estado IN ('Pendiente', 'Confirmada', 'Activa')"
);
$stmtActivasGestionadas->execute([':id' => $id]);
$activasGestionadas = (int) $stmtActivasGestionadas->fetchColumn();

// ---- Historial de reservas gestionadas (si las hay) ----
$stmtHistorial = $pdo->prepare(
    "SELECT r.id_reserva,
            CONCAT(c.nombres, ' ', c.apellidos) AS huesped,
            h.numero_hab,
            h.tipo AS tipo_hab,
            r.fecha_entrada,
            r.fecha_salida,
            r.estado,
            r.total_calculado
     FROM reservas r
     JOIN clientes c ON c.id_cliente = r.id_cliente
     JOIN habitaciones h ON h.id_habitacion = r.id_habitacion
     WHERE r.id_personal = :id
     ORDER BY r.fecha_entrada DESC"
);
$stmtHistorial->execute([':id' => $id]);
$historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

$colorEstado = [
    'Pendiente'   => '#F9A825',
    'Confirmada'  => '#1565C0',
    'Activa'      => '#2E7D32',
    'Finalizada'  => '#616161',
    'Cancelada'   => '#C62828',
];

$colorCargo = [
    'Administrador'  => '#2E7D32',
    'Recepcionista'  => '#1976D2',
    'Mucama'         => '#7B1FA2',
    'Mantenimiento'  => '#EF6C00',
    'Otro'           => '#616161',
];
$color = $colorCargo[$colaborador['cargo']] ?? '#616161';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de <?= htmlspecialchars($colaborador['nombres']) ?> - HotelSys</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F5F5F5; margin: 0; }
        /* Ajuste Semana 20 (hallazgo #5, Semana 19 Día 4): esta vista no tenía
           ninguna navegación hacia el resto del sistema, ni siquiera un
           botón "← Dashboard". Mismo nav-hotelsys que ya usan
           views/reservas.php, views/habitaciones.php, etc. */
        .nav-hotelsys {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2E7D32;
            padding: 10px 20px;
            margin-bottom: 15px;
        }
        .nav-hotelsys a {
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
        }
        .nav-hotelsys a:hover {
            text-decoration: underline;
        }
        .nav-rol {
            color: #E8F5E9;
        }
        .contenedor { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .tarjeta-cabecera {
            background: #fff; border-radius: 10px; padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 6px solid <?= $color ?>;
            margin-bottom: 20px;
        }
        .badge-cargo {
            display: inline-block; color: #fff; font-size: 12px;
            padding: 3px 12px; border-radius: 12px; background: <?= $color ?>;
            margin-bottom: 8px;
        }
        .badge-inactivo {
            background: #9E9E9E; color: #fff; font-size: 11px;
            padding: 2px 8px; border-radius: 10px; margin-left: 6px;
        }
        h1 { margin: 6px 0; color: #222; }
        .datos-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px; margin-top: 16px;
        }
        .dato p { margin: 0; font-size: 13px; color: #777; }
        .dato strong { font-size: 14.5px; color: #222; }
        .kpi-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 20px;
        }
        .kpi {
            background: #fff; border-radius: 10px; padding: 18px;
            text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .kpi h2 { font-size: 1.8rem; color: #2E7D32; margin: 6px 0; }
        .kpi p { font-size: 0.85rem; color: #757575; margin: 0; }
        .panel {
            background: #fff; border-radius: 10px; padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); margin-bottom: 20px;
        }
        .panel h3 { color: #2E7D32; font-size: 15px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; background: #FAFAFA; }
        .badge-estado {
            color: #fff; font-size: 11px; padding: 3px 9px; border-radius: 10px;
        }
        .sin-datos { color: #999; font-size: 13.5px; padding: 10px 0; }
        .btn-volver {
            display: inline-block; margin-bottom: 16px; color: #2E7D32;
            text-decoration: none; font-size: 14px; font-weight: bold;
        }
    </style>
</head>
<body>

<nav class="nav-hotelsys">
    <div>
        <a href="<?= BASE_URL ?>views/dashboard.php">← Dashboard</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/reservas.php">Reservas</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/clientes.php">Clientes</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/habitaciones.php">Habitaciones</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/personal.php">Personal</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/tareas_personal.php">Tareas</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/facturas.php">Facturas</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/cierre_caja.php">Caja</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/mantenimientos.php">Mantenimiento</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/reportes.php">Reportes</a>
    </div>
    <span class="nav-rol">
        Sesión: <strong><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></strong>
    </span>
    <a href="<?= BASE_URL ?>views/logout.php">Cerrar sesión</a>
</nav>

<div class="contenedor">
    <a href="personal.php" class="btn-volver">← Volver al listado</a>

    <div class="tarjeta-cabecera">
        <span class="badge-cargo"><?= htmlspecialchars($colaborador['cargo']) ?></span>
        <?php if ($colaborador['activo'] == 0): ?>
            <span class="badge-inactivo">Inactivo</span>
        <?php endif; ?>
        <h1><?= htmlspecialchars($colaborador['nombres'] . ' ' . $colaborador['apellidos']) ?></h1>

        <div class="datos-grid">
            <div class="dato">
                <p>Documento</p>
                <strong><?= htmlspecialchars($colaborador['tipo_documento'] . ' ' . $colaborador['num_documento']) ?></strong>
            </div>
            <div class="dato">
                <p>Turno</p>
                <strong><?= htmlspecialchars($colaborador['turno']) ?></strong>
            </div>
            <div class="dato">
                <p>Teléfono</p>
                <strong><?= htmlspecialchars($colaborador['telefono'] ?? '—') ?></strong>
            </div>
            <div class="dato">
                <p>Email</p>
                <strong><?= htmlspecialchars($colaborador['email'] ?? '—') ?></strong>
            </div>
            <div class="dato">
                <p>Fecha de ingreso</p>
                <strong><?= htmlspecialchars($colaborador['fecha_ingreso'] ?? '—') ?></strong>
            </div>
            <div class="dato">
                <p>Contacto de emergencia</p>
                <strong>
                    <?= htmlspecialchars($colaborador['contacto_emergencia'] ?? '—') ?>
                    <?= $colaborador['tel_emergencia'] ? ' · ' . htmlspecialchars($colaborador['tel_emergencia']) : '' ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi">
            <p>Reservas gestionadas (histórico)</p>
            <h2><?= $totalGestionadas ?></h2>
        </div>
        <div class="kpi">
            <p>Reservas activas a su cargo</p>
            <h2><?= $activasGestionadas ?></h2>
        </div>
    </div>

    <div class="panel">
        <h3>Historial de reservas gestionadas</h3>
        <?php if (count($historial) === 0): ?>
            <p class="sin-datos">
                Sin reservas gestionadas registradas. El campo de asignación de colaborador en
                Reservas aún no está vinculado al usuario que inicia sesión — ver nota técnica pendiente.
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Huésped</th>
                        <th>Habitación</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $r): ?>
                        <?php $colorFila = $colorEstado[$r['estado']] ?? '#616161'; ?>
                        <tr>
                            <td><?= htmlspecialchars($r['huesped']) ?></td>
                            <td><?= htmlspecialchars($r['numero_hab'] . ' — ' . $r['tipo_hab']) ?></td>
                            <td><?= htmlspecialchars($r['fecha_entrada']) ?></td>
                            <td><?= htmlspecialchars($r['fecha_salida']) ?></td>
                            <td>$<?= number_format($r['total_calculado'], 0, ',', '.') ?></td>
                            <td><span class="badge-estado" style="background: <?= $colorFila ?>;">
                                <?= htmlspecialchars($r['estado']) ?>
                            </span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>