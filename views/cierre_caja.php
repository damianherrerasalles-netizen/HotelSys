<?php
// views/cierre_caja.php — Cierre de caja diario por método de pago
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 4 — Actividad X: alerta de descuadre (P14 del levantamiento)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

// --- Fecha a revisar (por defecto, hoy) ---
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fechaObj) {
    $fecha = date('Y-m-d');
    $fechaObj = new DateTime($fecha);
}
$esHoy = $fecha === date('Y-m-d');
// Comparación por texto 'Y-m-d', no por objeto DateTime — ver la nota en
// cierre_registrar.php sobre por qué comparar DateTime aquí es incorrecto.
$esFuturo = $fecha > date('Y-m-d');

// Los únicos métodos que el hostal realmente maneja (P14)
$metodosPago = ['Efectivo', 'Transferencia', 'Tarjeta', 'Nequi'];

// Montos que el sistema calcula ahora mismo, por método, para la fecha elegida
$stmtSistema = $pdo->prepare(
    "SELECT metodo_pago, COALESCE(SUM(total), 0) AS total_metodo
     FROM facturas
     WHERE estado = 'Pagada' AND DATE(fecha_emision) = :fecha
     GROUP BY metodo_pago"
);
$stmtSistema->execute([':fecha' => $fecha]);
$sistemaPorMetodo = array_column($stmtSistema->fetchAll(PDO::FETCH_ASSOC), 'total_metodo', 'metodo_pago');

// Cierres ya registrados para esa fecha (si el administrador ya los cerró)
$stmtCierres = $pdo->prepare(
    "SELECT c.metodo_pago, c.monto_sistema, c.monto_declarado, c.diferencia,
            c.fecha_registro, CONCAT(p.nombres, ' ', p.apellidos) AS cerrado_por
     FROM cierres_caja c
     LEFT JOIN personal p ON p.id_personal = c.id_personal
     WHERE c.fecha = :fecha"
);
$stmtCierres->execute([':fecha' => $fecha]);
$cierresPorMetodo = [];
foreach ($stmtCierres->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $cierresPorMetodo[$c['metodo_pago']] = $c;
}

// Totales de la fecha
$totalSistemaDia = array_sum($sistemaPorMetodo);
$totalDeclaradoDia = array_sum(array_column($cierresPorMetodo, 'monto_declarado'));
$totalCerrados = count($cierresPorMetodo);
$cajaCompleta = $totalCerrados === count($metodosPago);
$hayDescuadre = false;
foreach ($cierresPorMetodo as $c) {
    if (abs((float)$c['diferencia']) > 0.01) {
        $hayDescuadre = true;
        break;
    }
}

$mensaje = $_SESSION['cierre_mensaje'] ?? null;
$tipoMensaje = $_SESSION['cierre_mensaje_tipo'] ?? 'error';
unset($_SESSION['cierre_mensaje'], $_SESSION['cierre_mensaje_tipo']);

$fechaAnterior = (clone $fechaObj)->modify('-1 day')->format('Y-m-d');
$fechaSiguiente = (clone $fechaObj)->modify('+1 day')->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de caja — HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        .selector-fecha { display: flex; align-items: center; gap: 10px; margin: 14px 0 18px; }
        .selector-fecha input[type="date"] { padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .selector-fecha a.btn { padding: 7px 12px; font-size: 13px; }

        .resumen-caja {
            display: flex; gap: 16px; margin-bottom: 18px;
        }
        .resumen-caja .tarjeta { flex: 1; }

        .aviso-caja {
            padding: 12px 16px; border-radius: 6px; margin-bottom: 18px; font-size: 14px;
            border: 1px solid transparent;
        }
        .aviso-cuadrada { background: #C8E6C9; color: #1b5e20; border-color: #2E7D32; }
        .aviso-descuadre { background: #FDECEA; color: #B71C1C; border-color: #C62828; }
        .aviso-pendiente { background: #FFF3E0; color: #E65100; border-color: #E65100; }

        table.tabla-cierre { width: 100%; border-collapse: collapse; background: #fff; margin-top: 10px; }
        table.tabla-cierre th {
            background: var(--verde-clar); color: #1b5e20; font-size: 12.5px; text-align: left; padding: 9px 12px;
        }
        table.tabla-cierre td { padding: 9px 12px; font-size: 13.5px; border-bottom: 1px solid #eee; vertical-align: middle; }
        table.tabla-cierre td.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.tabla-cierre tr.fila-descuadre { background: #FFF5F5; }
        table.tabla-cierre input[type="number"] {
            width: 130px; padding: 6px 8px; border: 1px solid #ccc; border-radius: 6px; text-align: right;
        }
        .pill-ok { color: #2E7D32; font-weight: bold; }
        .pill-mal { color: #C62828; font-weight: bold; }
        .pill-pendiente { color: #E65100; font-weight: bold; }

        .fila-totales td { font-weight: bold; background: #FAFAFA; border-top: 2px solid #ccc; }
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
        </div>
        <span style="color:#E8F5E9;">
            Sesión: <strong><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></strong>
        </span>
        <a href="<?= BASE_URL ?>views/logout.php" style="color:#fff; text-decoration:none; font-weight:bold;">Cerrar sesión</a>
    </nav>

    <h1>Cierre de caja diario</h1>
    <p class="subtitulo">Hotel Plaza Hostal — Resumen por método de pago y alerta de descuadre</p>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="GET" class="selector-fecha">
        <a href="?fecha=<?= $fechaAnterior ?>" class="btn btn-secundario">← Día anterior</a>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
        <button type="submit" class="btn btn-secundario">Ver</button>
        <?php if (!$esFuturo && $fecha !== date('Y-m-d')): ?>
            <a href="?fecha=<?= $fechaSiguiente ?>" class="btn btn-secundario">Día siguiente →</a>
        <?php endif; ?>
        <?php if (!$esHoy): ?>
            <a href="?fecha=<?= date('Y-m-d') ?>" class="btn btn-texto">Volver a hoy</a>
        <?php endif; ?>
    </form>

    <?php if ($totalCerrados === 0): ?>
        <div class="aviso-caja aviso-pendiente">
            <strong>&#9203; Caja sin cerrar</strong> para el <?= htmlspecialchars($fecha) ?>. El sistema calculó
            lo que debería sumar cada método — compáralo con lo contado físicamente y registra el cierre abajo.
        </div>
    <?php elseif ($hayDescuadre): ?>
        <div class="aviso-caja aviso-descuadre">
            <strong>&#9888; Descuadre detectado</strong> en uno o más métodos de pago del <?= htmlspecialchars($fecha) ?> —
            revisa la columna "Diferencia" en la tabla.
        </div>
    <?php elseif ($cajaCompleta): ?>
        <div class="aviso-caja aviso-cuadrada">
            <strong>&#10003; Caja cuadrada</strong> — los <?= count($metodosPago) ?> métodos de pago del
            <?= htmlspecialchars($fecha) ?> ya fueron cerrados y coinciden con lo calculado por el sistema.
        </div>
    <?php else: ?>
        <div class="aviso-caja aviso-pendiente">
            <strong>&#9203; Cierre parcial</strong> — <?= $totalCerrados ?> de <?= count($metodosPago) ?> métodos
            cerrados para el <?= htmlspecialchars($fecha) ?>. Los métodos sin cerrar quedan pendientes abajo.
        </div>
    <?php endif; ?>

    <div class="resumen-caja">
        <div class="tarjeta">
            <span class="tarjeta-valor">$<?= number_format($totalSistemaDia, 0, ',', '.') ?></span>
            <span class="tarjeta-etiqueta">Total facturado hoy (sistema)</span>
        </div>
        <div class="tarjeta">
            <span class="tarjeta-valor">$<?= number_format($totalDeclaradoDia, 0, ',', '.') ?></span>
            <span class="tarjeta-etiqueta">Total declarado (<?= $totalCerrados ?>/<?= count($metodosPago) ?> métodos cerrados)</span>
        </div>
    </div>

    <form action="<?= BASE_URL ?>modules/facturacion/cierre_registrar.php" method="POST">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <table class="tabla-cierre">
            <thead>
                <tr>
                    <th>Método</th>
                    <th class="num">Calculado por el sistema</th>
                    <th class="num">Monto declarado / contado</th>
                    <th class="num">Diferencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($metodosPago as $metodo): ?>
                    <?php
                        $montoSistemaActual = (float) ($sistemaPorMetodo[$metodo] ?? 0);
                        $cierre = $cierresPorMetodo[$metodo] ?? null;
                        $tieneDescuadre = $cierre && abs((float)$cierre['diferencia']) > 0.01;
                    ?>
                    <tr class="<?= $tieneDescuadre ? 'fila-descuadre' : '' ?>">
                        <td><?= htmlspecialchars($metodo) ?></td>
                        <td class="num">$<?= number_format($montoSistemaActual, 0, ',', '.') ?></td>
                        <td class="num">
                            <?php if (esAdmin()): ?>
                                <input type="number" name="monto_declarado[<?= $metodo ?>]" min="0" step="1"
                                       value="<?= $cierre ? number_format((float)$cierre['monto_declarado'], 0, '.', '') : number_format($montoSistemaActual, 0, '.', '') ?>">
                            <?php elseif ($cierre): ?>
                                $<?= number_format($cierre['monto_declarado'], 0, ',', '.') ?>
                            <?php else: ?>
                                <span style="color:#999;">— sin cerrar —</span>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <?php if ($cierre): ?>
                                <?php $dif = (float)$cierre['diferencia']; ?>
                                <span class="<?= abs($dif) > 0.01 ? 'pill-mal' : 'pill-ok' ?>">
                                    <?= $dif > 0 ? '+' : '' ?>$<?= number_format($dif, 0, ',', '.') ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$cierre): ?>
                                <span class="pill-pendiente">Pendiente</span>
                            <?php elseif (abs((float)$cierre['diferencia']) > 0.01): ?>
                                <span class="pill-mal">Descuadre</span>
                            <?php else: ?>
                                <span class="pill-ok">Cuadrado</span>
                            <?php endif; ?>
                            <?php if ($cierre && $cierre['cerrado_por']): ?>
                                <div style="font-size:11px; color:#888;">por <?= htmlspecialchars($cierre['cerrado_por']) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="fila-totales">
                    <td>Total</td>
                    <td class="num">$<?= number_format($totalSistemaDia, 0, ',', '.') ?></td>
                    <td class="num">$<?= number_format($totalDeclaradoDia, 0, ',', '.') ?></td>
                    <td class="num"><?= ($totalDeclaradoDia - $totalSistemaDia) >= 0 ? '+' : '' ?>$<?= number_format($totalDeclaradoDia - $totalSistemaDia, 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <?php if (esAdmin()): ?>
            <p class="conteo-resultados">
                El monto sugerido ya viene prellenado con lo que calculó el sistema — ajústalo al valor real
                contado antes de guardar. Solo se registran los métodos con un valor en el campo.
            </p>
            <button type="submit" class="btn btn-primario" style="margin-top:8px;">Guardar cierre de caja</button>
        <?php else: ?>
            <p class="conteo-resultados">Solo un administrador puede registrar o corregir el cierre de caja.</p>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
