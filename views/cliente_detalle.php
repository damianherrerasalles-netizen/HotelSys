<?php
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

$idCliente = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($idCliente === null || $idCliente <= 0) {
    header('Location: clientes.php');
    exit();
}

// Datos del cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
$stmt->execute([':id' => $idCliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    $_SESSION['cliente_mensaje'] = 'El cliente solicitado no existe.';
    $_SESSION['cliente_mensaje_tipo'] = 'error';
    header('Location: clientes.php');
    exit();
}

// Historial completo de reservas (todos los estados), con datos de la habitacion
$sqlHistorial = "SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, r.num_noches,
                         r.num_personas, r.estado, r.canal_origen,
                         r.precio_noche_aplicado, r.total_calculado,
                         h.numero_hab, h.tipo AS tipo_habitacion
                  FROM reservas r
                  INNER JOIN habitaciones h ON h.id_habitacion = r.id_habitacion
                  WHERE r.id_cliente = :id_cliente
                  ORDER BY r.fecha_entrada DESC";
$stmtHistorial = $pdo->prepare($sqlHistorial);
$stmtHistorial->execute([':id_cliente' => $idCliente]);
$historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

// Totales: solo se cuentan estadias efectivamente realizadas o en curso
$sqlTotales = "SELECT
                    COUNT(CASE WHEN estado IN ('Finalizada', 'Activa') THEN 1 END) AS num_estadias,
                    COALESCE(SUM(CASE WHEN estado IN ('Finalizada', 'Activa') THEN total_calculado END), 0) AS gasto_acumulado,
                    MAX(CASE WHEN estado = 'Finalizada' THEN fecha_salida END) AS ultima_visita
               FROM reservas
               WHERE id_cliente = :id_cliente";
$stmtTotales = $pdo->prepare($sqlTotales);
$stmtTotales->execute([':id_cliente' => $idCliente]);
$totales = $stmtTotales->fetch(PDO::FETCH_ASSOC);

// Mapeo de clases CSS por estado, para colorear badges en el historial
$claseEstado = [
    'Pendiente'  => 'badge-pendiente',
    'Confirmada' => 'badge-confirmada',
    'Activa'     => 'badge-activa',
    'Finalizada' => 'badge-finalizada',
    'Cancelada'  => 'badge-cancelada',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de cliente - HotelSys</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estilos.css">
</head>
<body>
<div class="contenedor">
    <a href="clientes.php">&larr; Volver al listado</a>

    <h1><?php echo htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']); ?></h1>
    <?php if (!$cliente['activo']): ?>
        <span class="badge badge-inactivo">Cliente inactivo</span>
    <?php endif; ?>

    <div class="panel-datos-cliente">
        <table class="tabla-detalle">
            <tr>
                <th>Documento</th>
                <td><?php echo htmlspecialchars($cliente['tipo_documento'] . ' ' . $cliente['num_documento']); ?></td>
                <th>Telefono</th>
                <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
                <th>Fecha de nacimiento</th>
                <td><?php echo htmlspecialchars($cliente['fecha_nacimiento'] ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Origen</th>
                <td colspan="3">
                    <?php
                    $origen = trim(($cliente['ciudad_origen'] ?? '') . ($cliente['departamento_origen'] ? ', ' . $cliente['departamento_origen'] : ''));
                    echo htmlspecialchars($origen !== '' ? $origen : '-');
                    ?>
                </td>
            </tr>
            <?php if (!empty($cliente['observaciones'])): ?>
            <tr>
                <th>Observaciones</th>
                <td colspan="3"><?php echo nl2br(htmlspecialchars($cliente['observaciones'])); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <a href="cliente_form.php?id=<?php echo (int)$cliente['id_cliente']; ?>" class="boton boton-primario">
            Editar cliente
        </a>
    </div>

    <h2>Resumen</h2>
    <div class="tarjetas-resumen">
        <div class="tarjeta">
            <span class="tarjeta-valor"><?php echo (int)$totales['num_estadias']; ?></span>
            <span class="tarjeta-etiqueta">Estadias realizadas</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor">
                <?php echo $totales['ultima_visita'] ? htmlspecialchars($totales['ultima_visita']) : '-'; ?>
            </span>
            <span class="tarjeta-etiqueta">Ultima visita</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor">
                $<?php echo number_format((float)$totales['gasto_acumulado'], 0, ',', '.'); ?>
            </span>
            <span class="tarjeta-etiqueta">Gasto acumulado</span>
        </div>
    </div>

    <h2>Historial de reservas</h2>
    <table class="tabla-datos">
        <thead>
        <tr>
            <th>Fecha entrada</th>
            <th>Fecha salida</th>
            <th>Noches</th>
            <th>Habitacion</th>
            <th>Canal</th>
            <th>Total</th>
            <th>Estado</th>
        </tr>
        </thead>
        <tbody>
        <?php if (count($historial) === 0): ?>
            <tr>
                <td colspan="7">Este cliente aun no tiene reservas registradas.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($historial as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['fecha_entrada']); ?></td>
                    <td><?php echo htmlspecialchars($r['fecha_salida']); ?></td>
                    <td><?php echo (int)$r['num_noches']; ?></td>
                    <td><?php echo htmlspecialchars($r['numero_hab'] . ' (' . $r['tipo_habitacion'] . ')'); ?></td>
                    <td><?php echo htmlspecialchars($r['canal_origen']); ?></td>
                    <td>$<?php echo number_format((float)$r['total_calculado'], 0, ',', '.'); ?></td>
                    <td>
                        <span class="badge <?php echo $claseEstado[$r['estado']] ?? ''; ?>">
                            <?php echo htmlspecialchars($r['estado']); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>