<?php
// views/facturas.php — Listado de facturas con filtros
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 3 — Actividad X: Módulo de Facturación

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

// ---- Filtros ----
$filtroEstado = $_GET['estado'] ?? 'todas';
$filtroMetodo = $_GET['metodo'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$busqueda = trim($_GET['busqueda'] ?? '');

$estadosValidos = ['Pendiente', 'Pagada', 'Anulada'];
// ENUM completo de la BD, usado solo para validar el filtro de forma segura
$metodosValidosDB = ['Efectivo', 'Transferencia', 'Tarjeta', 'Nequi', 'Daviplata'];
// Métodos que el hostal realmente ofrece (P14) — los únicos que puede generar
// el flujo de registrar pago, así que son los únicos útiles como filtro
$metodosPago = ['Efectivo', 'Transferencia', 'Tarjeta', 'Nequi'];

$condiciones = [];
$params = [];

if (in_array($filtroEstado, $estadosValidos, true)) {
    $condiciones[] = 'f.estado = :estado';
    $params[':estado'] = $filtroEstado;
}

if (in_array($filtroMetodo, $metodosValidosDB, true)) {
    $condiciones[] = 'f.metodo_pago = :metodo';
    $params[':metodo'] = $filtroMetodo;
}

if ($desde !== '' && DateTime::createFromFormat('Y-m-d', $desde)) {
    $condiciones[] = 'DATE(f.fecha_emision) >= :desde';
    $params[':desde'] = $desde;
}

if ($hasta !== '' && DateTime::createFromFormat('Y-m-d', $hasta)) {
    $condiciones[] = 'DATE(f.fecha_emision) <= :hasta';
    $params[':hasta'] = $hasta;
}

if ($busqueda !== '') {
    $condiciones[] = "(c.nombres LIKE :busqueda OR c.apellidos LIKE :busqueda2 OR c.num_documento LIKE :busqueda3 OR f.id_reserva = :busquedaNum)";
    $params[':busqueda'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
    $params[':busqueda3'] = "%$busqueda%";
    $params[':busquedaNum'] = ctype_digit($busqueda) ? (int)$busqueda : 0;
}

$whereSql = count($condiciones) > 0 ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$sql = "SELECT f.id_factura, f.id_reserva, f.subtotal, f.iva_valor, f.total, f.metodo_pago, f.estado, f.fecha_emision,
               CONCAT(c.nombres, ' ', c.apellidos) AS huesped,
               h.numero_hab
        FROM facturas f
        JOIN clientes c     ON c.id_cliente = f.id_cliente
        JOIN reservas r     ON r.id_reserva = f.id_reserva
        JOIN habitaciones h ON h.id_habitacion = r.id_habitacion
        $whereSql
        ORDER BY f.fecha_emision DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totales rápidos sobre la vista filtrada actual
$totalFacturado = array_sum(array_column($facturas, 'total'));
$totalPagadas = array_sum(array_map(
    fn($f) => $f['estado'] === 'Pagada' ? (float)$f['total'] : 0,
    $facturas
));

$mensaje = $_SESSION['facturas_mensaje'] ?? null;
$tipoMensaje = $_SESSION['facturas_mensaje_tipo'] ?? 'error';
unset($_SESSION['facturas_mensaje'], $_SESSION['facturas_mensaje_tipo']);

$badgeEstado = ['Pendiente' => 'badge-pendiente', 'Pagada' => 'badge-activo', 'Anulada' => 'badge-inactivo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturas — HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        .filtros-facturas { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin: 14px 0; }
        .filtros-facturas input, .filtros-facturas select { padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .filtros-facturas label { font-size: 13px; color: #555; }

        table.tabla-facturas { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
        table.tabla-facturas th {
            background: var(--verde-clar); color: #1b5e20; font-size: 12.5px;
            text-align: left; padding: 8px 10px;
        }
        table.tabla-facturas td { padding: 8px 10px; font-size: 13px; border-bottom: 1px solid #eee; }
        table.tabla-facturas td.num { text-align: right; }
        table.tabla-facturas tr:hover { background: #FAFAFA; }
    </style>
</head>
<body>
<div class="contenedor">
    <nav class="nav-hotelsys" style="display:flex; justify-content:space-between; align-items:center; background-color:#2E7D32; padding:10px 20px; margin-bottom:15px;">
        <div>
            <a href="<?= BASE_URL ?>views/dashboard.php" style="color:#fff; text-decoration:none; font-weight:bold;">← Dashboard</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/reservas.php" style="color:#fff; text-decoration:none; font-weight:bold;">Reservas</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/habitaciones.php" style="color:#fff; text-decoration:none; font-weight:bold;">Habitaciones</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/personal.php" style="color:#fff; text-decoration:none; font-weight:bold;">Personal</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/cierre_caja.php" style="color:#fff; text-decoration:none; font-weight:bold;">Caja</a>
        </div>
        <span style="color:#E8F5E9;">
            Sesión: <strong><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></strong>
        </span>
        <a href="<?= BASE_URL ?>views/logout.php" style="color:#fff; text-decoration:none; font-weight:bold;">Cerrar sesión</a>
    </nav>

    <h1>Facturación — Hotel Plaza Hostal</h1>
    <p class="subtitulo">Facturas generadas automáticamente al checkout, con IVA del 19%</p>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="tarjetas-resumen">
        <div class="tarjeta">
            <span class="tarjeta-valor"><?= count($facturas) ?></span>
            <span class="tarjeta-etiqueta">Facturas en esta vista</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor">$<?= number_format($totalFacturado, 0, ',', '.') ?></span>
            <span class="tarjeta-etiqueta">Total facturado (filtro actual)</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor">$<?= number_format($totalPagadas, 0, ',', '.') ?></span>
            <span class="tarjeta-etiqueta">Total pagado (filtro actual)</span>
        </div>
    </div>

    <form method="GET" class="filtros-facturas">
        <input type="text" name="busqueda" placeholder="Huésped, documento o # reserva..."
               value="<?= htmlspecialchars($busqueda) ?>">

        <select name="estado">
            <option value="todas" <?= $filtroEstado === 'todas' ? 'selected' : '' ?>>Todos los estados</option>
            <?php foreach ($estadosValidos as $e): ?>
                <option value="<?= $e ?>" <?= $filtroEstado === $e ? 'selected' : '' ?>><?= $e ?></option>
            <?php endforeach; ?>
        </select>

        <select name="metodo">
            <option value="">Todos los métodos</option>
            <?php foreach ($metodosPago as $m): ?>
                <option value="<?= $m ?>" <?= $filtroMetodo === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>

        <label>Desde <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
        <label>Hasta <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>

        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <a href="facturas.php" class="btn btn-texto">Limpiar</a>
    </form>

    <table class="tabla-facturas">
        <thead>
            <tr>
                <th>#</th><th>Huésped</th><th>Hab.</th><th>Emisión</th>
                <th class="num">Subtotal</th><th class="num">IVA</th><th class="num">Total</th>
                <th>Método</th><th>Estado</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($facturas as $f): ?>
                <tr>
                    <td>#<?= str_pad((string)$f['id_factura'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($f['huesped']) ?></td>
                    <td><?= htmlspecialchars($f['numero_hab']) ?></td>
                    <td><?= htmlspecialchars($f['fecha_emision']) ?></td>
                    <td class="num">$<?= number_format($f['subtotal'], 0, ',', '.') ?></td>
                    <td class="num">$<?= number_format($f['iva_valor'], 0, ',', '.') ?></td>
                    <td class="num">$<?= number_format($f['total'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($f['metodo_pago']) ?></td>
                    <td><span class="badge <?= $badgeEstado[$f['estado']] ?? 'badge-inactivo' ?>"><?= htmlspecialchars($f['estado']) ?></span></td>
                    <td>
                        <a href="factura_detalle.php?id=<?= (int)$f['id_factura'] ?>" class="btn btn-secundario" style="padding:5px 10px; font-size:12px;">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($facturas) === 0): ?>
                <tr><td colspan="10" style="text-align:center; color:#777; padding:16px;">No se encontraron facturas con los filtros aplicados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
