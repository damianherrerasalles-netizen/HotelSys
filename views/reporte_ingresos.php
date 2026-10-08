<?php
// views/reporte_ingresos.php — Reporte de Ingresos por método de pago
// HotelSys — Hotel Plaza Hostal
// Semana 17 Día 4 — Actividad XI: Módulo de Reportes con gráficas e indicadores

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

// ---- Filtro de rango de fechas (por defecto: del 1 del mes actual a hoy) ----
$hoy = date('Y-m-d');
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? $hoy;

if (!DateTime::createFromFormat('Y-m-d', $desde)) {
    $desde = date('Y-m-01');
}
if (!DateTime::createFromFormat('Y-m-d', $hasta)) {
    $hasta = $hoy;
}
// Comparación de fechas como strings 'Y-m-d' (no objetos DateTime) — mismo
// ajuste aplicado en Semana 15 Día 4 y en el Reporte de Ocupación (Día 3).
if ($hasta < $desde) {
    [$desde, $hasta] = [$hasta, $desde];
}

// Solo los métodos que el hostal realmente ofrece (P14 del levantamiento) —
// mismo criterio ya usado en views/facturas.php y views/cierre_caja.php.
// El ENUM de la BD también permite 'Daviplata', pero el flujo de registrar
// pago nunca lo genera, así que no se incluye como categoría del reporte.
$metodosPago = ['Efectivo', 'Transferencia', 'Tarjeta', 'Nequi'];

// ---- Ingresos por método: solo facturas Pagada (dinero realmente cobrado) ----
// Pendiente no es ingreso todavía, y Anulada nunca lo fue — mismo criterio que
// usa cierre_caja.php para un solo día, generalizado aquí a un rango de fechas.
$sql = "SELECT metodo_pago, COALESCE(SUM(total), 0) AS ingresos, COUNT(*) AS num_facturas
        FROM facturas
        WHERE estado = 'Pagada'
          AND DATE(fecha_emision) BETWEEN :desde AND :hasta
        GROUP BY metodo_pago";

$stmt = $pdo->prepare($sql);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$porMetodoDB = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $porMetodoDB[$fila['metodo_pago']] = $fila;
}

// Normaliza a los 4 métodos fijos, en orden, para que la gráfica y la tabla
// siempre muestren las mismas categorías aunque un método no tenga ingresos
// en el rango elegido (igual que Triple/Suite en el Reporte de Ocupación).
$filas = [];
$totalIngresos = 0.0;
$totalFacturas = 0;
foreach ($metodosPago as $metodo) {
    $ingresos = (float) ($porMetodoDB[$metodo]['ingresos'] ?? 0);
    $numFacturas = (int) ($porMetodoDB[$metodo]['num_facturas'] ?? 0);
    $filas[] = ['metodo' => $metodo, 'ingresos' => $ingresos, 'num_facturas' => $numFacturas];
    $totalIngresos += $ingresos;
    $totalFacturas += $numFacturas;
}

foreach ($filas as &$f) {
    $f['pct'] = $totalIngresos > 0 ? round($f['ingresos'] * 100 / $totalIngresos, 1) : 0.0;
}
unset($f);

// Método con mayor ingreso en el rango (para la tarjeta resumen)
$metodoTop = null;
foreach ($filas as $f) {
    if ($metodoTop === null || $f['ingresos'] > $metodoTop['ingresos']) {
        $metodoTop = $f;
    }
}

$etiquetasChart = array_column($filas, 'metodo');
$datosChart = array_column($filas, 'ingresos');

// Ajuste Semana 22 (Actividad XIII, confirmado en validación Semana 21 Día
// 2-3): comparar el total de ingresos del rango elegido contra el mismo
// número de días inmediatamente anterior, para saber si el periodo mejoró
// o empeoró sin tener que generar el reporte dos veces y comparar a mano.
$numDiasRango = (new DateTime($desde))->diff(new DateTime($hasta))->days + 1;
$finPeriodoAnterior = (new DateTime($desde))->modify('-1 day');
$inicioPeriodoAnterior = (clone $finPeriodoAnterior)->modify('-' . ($numDiasRango - 1) . ' day');

$stmtAnterior = $pdo->prepare(
    "SELECT COALESCE(SUM(total), 0) AS ingresos
     FROM facturas
     WHERE estado = 'Pagada'
       AND DATE(fecha_emision) BETWEEN :desde AND :hasta"
);
$stmtAnterior->execute([
    ':desde' => $inicioPeriodoAnterior->format('Y-m-d'),
    ':hasta' => $finPeriodoAnterior->format('Y-m-d'),
]);
$totalIngresosPeriodoAnterior = (float) $stmtAnterior->fetch(PDO::FETCH_ASSOC)['ingresos'];

if ($totalIngresosPeriodoAnterior > 0) {
    $variacionPct = round((($totalIngresos - $totalIngresosPeriodoAnterior) / $totalIngresosPeriodoAnterior) * 100, 1);
} elseif ($totalIngresos > 0) {
    $variacionPct = null; // sin base de comparación (período anterior en $0)
} else {
    $variacionPct = 0.0; // ambos períodos en $0 — sin cambio
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos — HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        .filtros-reporte { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin: 14px 0; }
        .filtros-reporte input { padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .filtros-reporte label { font-size: 13px; color: #555; }

        .panel-grafica {
            background: #fff; border-radius: 10px; padding: 18px 20px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1); margin: 16px 0;
        }
        .panel-grafica h2 { color: #2E7D32; font-size: 16px; margin-bottom: 10px; }
        .lienzo-grafica { max-width: 640px; margin: 0 auto; }

        table.tabla-ingresos { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
        table.tabla-ingresos th {
            background: var(--verde-clar); color: #1b5e20; font-size: 12.5px;
            text-align: left; padding: 8px 10px;
        }
        table.tabla-ingresos td { padding: 8px 10px; font-size: 13px; border-bottom: 1px solid #eee; }
        table.tabla-ingresos td.num { text-align: right; }
        table.tabla-ingresos tr:hover { background: #FAFAFA; }
        table.tabla-ingresos tr.fila-total { font-weight: bold; background: #F1F8E9; }

        .nota-metodologia {
            font-size: 11.5px; color: #666; background: #FAFAFA; border: 1px solid #eee;
            border-radius: 6px; padding: 8px 12px; margin-top: 10px; line-height: 1.4;
        }
        /* Ajuste Semana 22 (Actividad XIII) — variación vs período anterior */
        .tarjeta-valor.variacion-positiva { color: #2E7D32; }
        .tarjeta-valor.variacion-negativa { color: #C62828; }
        .tarjeta-valor.variacion-neutra   { color: #757575; }
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
            <a href="<?= BASE_URL ?>views/facturas.php" style="color:#fff; text-decoration:none; font-weight:bold;">Facturas</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/cierre_caja.php" style="color:#fff; text-decoration:none; font-weight:bold;">Caja</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/mantenimientos.php" style="color:#fff; text-decoration:none; font-weight:bold;">Mantenimiento</a>
            &nbsp;|&nbsp;
            <a href="<?= BASE_URL ?>views/reportes.php" style="color:#fff; text-decoration:none; font-weight:bold;">Reportes</a>
        </div>
        <span style="color:#E8F5E9;">
            Sesión: <strong><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></strong>
        </span>
        <a href="<?= BASE_URL ?>views/logout.php" style="color:#fff; text-decoration:none; font-weight:bold;">Cerrar sesión</a>
    </nav>

    <p><a href="reportes.php" style="color:#2E7D32; text-decoration:none; font-weight:bold;">← Reportes</a></p>
    <h1>Reporte de Ingresos — Hotel Plaza Hostal</h1>
    <p class="subtitulo">Ingresos facturados por método de pago en el rango de fechas seleccionado</p>

    <form method="GET" class="filtros-reporte">
        <label>Desde <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
        <label>Hasta <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>
        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <a href="reporte_ingresos.php" class="btn btn-texto">Limpiar</a>
    </form>

    <div class="tarjetas-resumen">
        <div class="tarjeta">
            <span class="tarjeta-valor">$<?= number_format($totalIngresos, 0, ',', '.') ?></span>
            <span class="tarjeta-etiqueta">Ingresos totales (<?= htmlspecialchars($desde) ?> a <?= htmlspecialchars($hasta) ?>)</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor"><?= $totalFacturas ?></span>
            <span class="tarjeta-etiqueta">Facturas pagadas en el rango</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor"><?= $metodoTop && $metodoTop['ingresos'] > 0 ? htmlspecialchars($metodoTop['metodo']) : '—' ?></span>
            <span class="tarjeta-etiqueta">Método con mayor ingreso<?= $metodoTop && $metodoTop['ingresos'] > 0 ? ' (' . number_format($metodoTop['pct'], 1) . '%)' : '' ?></span>
        </div>
        <div class="tarjeta">
            <?php if ($variacionPct === null): ?>
                <span class="tarjeta-valor variacion-neutra">—</span>
                <span class="tarjeta-etiqueta">Sin ingresos en el período anterior para comparar</span>
            <?php else: ?>
                <span class="tarjeta-valor <?= $variacionPct > 0 ? 'variacion-positiva' : ($variacionPct < 0 ? 'variacion-negativa' : 'variacion-neutra') ?>">
                    <?= $variacionPct > 0 ? '+' : '' ?><?= number_format($variacionPct, 1) ?>%
                </span>
                <span class="tarjeta-etiqueta">
                    vs período anterior ($<?= number_format($totalIngresosPeriodoAnterior, 0, ',', '.') ?>,
                    <?= htmlspecialchars($inicioPeriodoAnterior->format('d/m/Y')) ?> a <?= htmlspecialchars($finPeriodoAnterior->format('d/m/Y')) ?>)
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel-grafica">
        <h2>Ingresos por método de pago</h2>
        <div class="lienzo-grafica">
            <canvas id="graficaIngresos" height="260"></canvas>
        </div>
    </div>

    <table class="tabla-ingresos">
        <thead>
            <tr>
                <th>Método de pago</th><th class="num">Facturas pagadas</th>
                <th class="num">Ingresos</th><th class="num">% del total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['metodo']) ?></td>
                    <td class="num"><?= $f['num_facturas'] ?></td>
                    <td class="num">$<?= number_format($f['ingresos'], 0, ',', '.') ?></td>
                    <td class="num"><?= number_format($f['pct'], 1) ?>%</td>
                </tr>
            <?php endforeach; ?>
            <tr class="fila-total">
                <td>Total</td>
                <td class="num"><?= $totalFacturas ?></td>
                <td class="num">$<?= number_format($totalIngresos, 0, ',', '.') ?></td>
                <td class="num">100.0%</td>
            </tr>
        </tbody>
    </table>

    <p class="nota-metodologia">
        <strong>Metodología:</strong> solo se cuentan facturas en estado <strong>Pagada</strong> — dinero
        realmente cobrado — filtradas por la fecha de emisión dentro del rango seleccionado. Una factura
        <strong>Pendiente</strong> todavía no es un ingreso, y una <strong>Anulada</strong> nunca lo fue. Los
        métodos mostrados son los 4 que el hostal realmente ofrece (Efectivo, Transferencia, Tarjeta, Nequi);
        Daviplata existe en la base de datos pero el flujo de pago nunca lo genera. La comparación "vs período
        anterior" (Ajuste Semana 22) toma los mismos <?= $numDiasRango ?> día(s) inmediatamente antes del
        rango seleccionado, con el mismo criterio de facturas Pagada.
    </p>
</div>

<script>
const ctx = document.getElementById('graficaIngresos');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($etiquetasChart, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'Ingresos',
            data: <?= json_encode($datosChart) ?>,
            backgroundColor: '#66BB6A',
            borderColor: '#2E7D32',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => '$' + ctx.parsed.y.toLocaleString('es-CO', { maximumFractionDigits: 0 })
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: (v) => '$' + Number(v).toLocaleString('es-CO', { maximumFractionDigits: 0 })
                },
                title: { display: true, text: 'Ingresos ($)' }
            },
            x: {
                title: { display: true, text: 'Método de pago' }
            }
        }
    }
});
</script>
</body>
</html>
