<?php
// views/dashboard.php — Panel ejecutivo (solo administrador)
// HotelSys — Hotel Plaza Hostal
$rutaBase = '../'; // Ruta relativa SOLO para los require_once de este archivo
require_once '../config/db.php';
require_once '../includes/check_auth.php';
require_once '../includes/habitaciones_helpers.php'; // Semana 13 Día 2 — Widget 1 del Dashboard ejecutivo
require_once '../includes/dashboard_datos.php'; // Semana 14 Día 2 — datos compartidos con el endpoint JSON
requerirAdmin();

$conexion = getConexion();

// Semana 14 Día 2: las ~10 consultas del Dashboard ahora viven en una sola
// función compartida (includes/dashboard_datos.php), reutilizada también por
// el endpoint modules/dashboard/dashboard_datos.php que consultará el
// JavaScript de actualización automática (Día 3). extract() es seguro aquí
// porque las claves del arreglo son fijas y las define esa misma función —
// no vienen de ninguna entrada del usuario.
extract(obtenerDatosDashboard($conexion));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HotelSys — Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: #F5F5F5; }
        .header {
            background: #2E7D32; color: #fff;
            padding: 14px 24px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { font-size: 1.2rem; }
        .header a  { color: #C8E6C9; font-size: 0.85rem; text-decoration: none; }
        .content   { padding: 24px; }
        .welcome   { font-size: 1rem; color: #555; margin-bottom: 20px; }
        .ultima-actualizacion { font-size: 0.78rem; color: #999; margin: -14px 0 20px; }
        .rol-badge {
            display: inline-block;
            background: #C8E6C9; color: #2E7D32;
            padding: 2px 10px; border-radius: 12px;
            font-size: 0.8rem; font-weight: bold;
            text-transform: capitalize;
        }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .kpi {
            background: #fff; border: 1px solid #C8E6C9;
            border-radius: 8px; padding: 20px; text-align: center;
        }
        .kpi h2 { font-size: 2rem; color: #2E7D32; margin: 8px 0 4px; }
        .kpi p  { font-size: 0.85rem; color: #757575; }
        .kpi-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .kpi-link:hover .kpi {
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.25);
        }
        .kpi-link .kpi p:last-child {
            color: #2E7D32;
            font-weight: bold;
        }
        .kpi.kpi-alerta {
            border: 1px solid #C62828;
            background: #FFFBFB;
        }
        .kpi.kpi-alerta h2 {
            color: #C62828;
        }
        .kpi-link.kpi-link-alerta .kpi-link .kpi p:last-child { color: #C62828; }
        .kpi-link.kpi-link-alerta:hover .kpi {
            box-shadow: 0 2px 8px rgba(198, 40, 40, 0.25);
        }
        .kpi-link.kpi-link-alerta .kpi p:last-child {
            color: #C62828;
            font-weight: bold;
        }
        .ocupacion-panel {
            background: #fff; border: 1px solid #C8E6C9; border-radius: 8px;
            padding: 20px; margin-top: 20px;
        }
        .ocupacion-panel h3 { color: #2E7D32; font-size: 16px; margin-bottom: 16px; }
        .ocupacion-fila { margin-bottom: 14px; }
        .ocupacion-fila-header {
            display: flex; justify-content: space-between; font-size: 13px;
            color: #444; margin-bottom: 4px;
        }
        .ocupacion-fila-header strong { color: #222; }
        .barra-fondo {
            width: 100%; height: 10px; background: #E8F5E9; border-radius: 6px; overflow: hidden;
        }
        .barra-relleno {
            height: 100%; background: #2E7D32; border-radius: 6px;
        }
        .ocupacion-detalle {
            font-size: 11px; color: #999; margin-top: 3px;
        }
        /* Widget 1 — Mapa de habitaciones en tiempo real (Semana 13 Día 2) */
        .mapa-habitaciones-panel {
            background: #fff; border: 1px solid #C8E6C9; border-radius: 8px;
            padding: 20px; margin-top: 20px;
        }
        .mapa-habitaciones-panel h3 { color: #2E7D32; font-size: 16px; margin-bottom: 2px; }
        .mapa-habitaciones-panel .subtitulo {
            font-size: 12px; color: #757575; margin-bottom: 16px;
        }
        .mapa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
            gap: 10px;
        }
        .mapa-celda {
            aspect-ratio: 1;
            border-radius: 8px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: #fff; text-decoration: none;
            transition: transform 0.12s ease;
        }
        .mapa-celda:hover { transform: scale(1.07); }
        .mapa-celda .num { font-size: 15px; font-weight: bold; }
        .mapa-celda .tipo { font-size: 8.5px; opacity: 0.9; margin-top: 2px; text-align: center; }
        .mapa-leyenda {
            display: flex; flex-wrap: wrap; gap: 16px;
            margin-top: 16px; font-size: 12px; color: #444;
        }
        .mapa-leyenda .punto {
            display: inline-block; width: 10px; height: 10px;
            border-radius: 50%; margin-right: 5px; vertical-align: middle;
        }
        /* Widget 1 + Widget 2 en una sola fila, ~60/40 (Semana 13 Día 3) */
        .fila-widgets-principal {
            display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;
            align-items: stretch;
        }
        .fila-widgets-principal .mapa-habitaciones-panel {
            flex: 3 1 460px; margin-top: 0;
        }
        /* Widget 2 — Panel de alertas de inventario (Semana 13 Día 3) */
        .panel-alertas {
            flex: 2 1 260px;
            background: #fff; border: 1px solid #C8E6C9; border-radius: 8px;
            padding: 20px;
            display: flex; flex-direction: column;
        }
        .panel-alertas.panel-alertas-critico {
            border-color: #C62828; background: #FFFBFB;
        }
        .panel-alertas-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px;
        }
        .panel-alertas-header h3 { color: #2E7D32; font-size: 16px; margin: 0; }
        .panel-alertas-critico .panel-alertas-header h3 { color: #C62828; }
        .alertas-badge {
            background: #2E7D32; color: #fff; font-weight: bold; font-size: 14px;
            padding: 3px 13px; border-radius: 14px; min-width: 18px; text-align: center;
        }
        .panel-alertas-critico .alertas-badge { background: #C62828; }
        .alertas-lista { list-style: none; padding: 0; margin: 0 0 6px 0; flex: 1; }
        .alertas-lista li {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 12px; gap: 8px;
        }
        .alertas-lista li:last-child { border-bottom: none; }
        .alerta-insumo { font-weight: bold; color: #333; }
        .alerta-stock { color: #C62828; font-size: 10.5px; white-space: nowrap; }
        .alertas-mas { font-size: 11px; color: #999; margin: 2px 0 8px; }
        .alertas-ok { color: #2E7D32; font-size: 13px; padding: 24px 0; text-align: center; flex: 1; }
        .alertas-link {
            display: block; text-align: center; margin-top: auto; padding-top: 10px;
            border-top: 1px solid #eee; color: #2E7D32; font-size: 12px; font-weight: bold;
            text-decoration: none;
        }
        .alertas-link:hover { text-decoration: underline; }
        /* Widget 3 — Caja del día y tareas del personal (Semana 13 Día 4) */
        .fila-widget3 {
            display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;
        }
        .stat-panel {
            flex: 1 1 240px;
            background: #fff; border: 1px solid #C8E6C9; border-radius: 8px;
            padding: 20px; text-align: center;
        }
        .stat-panel p.stat-titulo { font-size: 0.85rem; color: #757575; margin-bottom: 6px; }
        .stat-panel h2 { font-size: 2rem; color: #2E7D32; margin: 4px 0; }
        .stat-panel p.stat-nota { font-size: 0.78rem; color: #999; margin-top: 4px; }
        .stat-panel p.stat-nota a { color: #2E7D32; font-weight: bold; text-decoration: none; }
        .stat-panel p.stat-nota a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="header">
    <h1>HotelSys &mdash; Dashboard</h1>
    <div>
        <?= htmlspecialchars($_SESSION['nombre']) ?>
        <span class="rol-badge"><?= htmlspecialchars($_SESSION['rol']) ?></span>
        &nbsp;|&nbsp;
        <a href="../includes/logout.php">Cerrar sesión</a>
    </div>
</div>
<div class="content">
    <p class="welcome">
        Bienvenido, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>.
        Panel de administración — Hotel Plaza Hostal.
    </p>
    <p class="ultima-actualizacion" id="ultima-actualizacion">
        Los datos se actualizan automáticamente cada 30 segundos.
    </p>
    <div class="kpi-grid">
        <a href="habitaciones.php" class="kpi-link">
            <div class="kpi" id="kpi-habitaciones-div">
                <p>Habitaciones disponibles</p>
                <h2 id="kpi-habitaciones"><?= (int)$totalHabitacionesDisponibles ?>/<?= count($mapaHabitaciones) ?></h2>
                <p>Ver estado en tiempo real →</p>
            </div>
        </a>
        <a href="reservas.php" class="kpi-link">
            <div class="kpi">
                <p>Reservas hoy</p>
                <h2 id="kpi-reservas"><?= (int)$totalReservasHoy ?></h2>
                <p>Check-ins programados para hoy →</p>
            </div>
        </a>
        <a href="clientes.php" class="kpi-link">
            <div class="kpi">
                <p>Clientes activos</p>
                <h2 id="kpi-clientes"><?= (int)$totalClientesActivos ?></h2>
                <p>Ir al módulo de Clientes →</p>
            </div>
        </a>
        <a href="personal.php" class="kpi-link">
            <div class="kpi">
                <p>Personal activo</p>
                <h2 id="kpi-personal"><?= (int)$totalPersonalActivo ?></h2>
                <p>Ir al módulo de Personal →</p>
            </div>
        </a>
        <a href="inventario.php?criticos=1" class="kpi-link">
            <div class="kpi <?= $totalStockCritico > 0 ? 'kpi-alerta' : '' ?>" id="kpi-stock-div">
                <p>Insumos en stock crítico</p>
                <h2 id="kpi-stock"><?= (int)$totalStockCritico ?></h2>
                <p>Ir al módulo de Inventario →</p>
            </div>
        </a>
        <div class="kpi">
            <p>Ingresos del mes</p>
            <h2>—</h2>
            <p>Disponible en Mes 4</p>
        </div>
    </div>

    <div class="fila-widgets-principal">
        <div class="mapa-habitaciones-panel">
            <h3>Mapa de habitaciones en tiempo real</h3>
            <p class="subtitulo">
                Estado actual de las <?= count($mapaHabitaciones) ?> habitaciones del hostal — Widget 1 del
                Dashboard ejecutivo (Semana 13).
            </p>
            <div class="mapa-grid" id="mapa-grid">
                <?php foreach ($mapaHabitaciones as $hab): ?>
                    <a href="habitacion_detalle.php?id=<?= $hab['id_habitacion'] ?>"
                       class="mapa-celda"
                       style="background-color: <?= colorEstadoHabitacion($hab['estado']) ?>;"
                       title="Hab. <?= htmlspecialchars($hab['numero_hab']) ?> — <?= htmlspecialchars($hab['tipo']) ?> — <?= htmlspecialchars($hab['estado']) ?>">
                        <span class="num"><?= htmlspecialchars($hab['numero_hab']) ?></span>
                        <span class="tipo"><?= htmlspecialchars($hab['tipo']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="mapa-leyenda">
                <span><span class="punto" style="background:#2E7D32;"></span>Disponible</span>
                <span><span class="punto" style="background:#C62828;"></span>Ocupada</span>
                <span><span class="punto" style="background:#1565C0;"></span>Reservada</span>
                <span><span class="punto" style="background:#F9A825;"></span>Mantenimiento</span>
            </div>
        </div>

        <div class="panel-alertas <?= $totalAlertasAbiertas > 0 ? 'panel-alertas-critico' : '' ?>" id="panel-alertas">
            <div class="panel-alertas-header">
                <h3>Alertas de Inventario</h3>
                <span class="alertas-badge" id="alertas-badge"><?= (int)$totalAlertasAbiertas ?></span>
            </div>
            <div id="alertas-cuerpo">
                <?php if ($totalAlertasAbiertas > 0): ?>
                    <ul class="alertas-lista">
                        <?php foreach ($alertasRecientes as $alerta): ?>
                            <li>
                                <span class="alerta-insumo"><?= htmlspecialchars($alerta['nombre_item']) ?></span>
                                <span class="alerta-stock">
                                    <?= (int)$alerta['stock_al_generar'] ?> / mín. <?= (int)$alerta['stock_minimo_al_generar'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($totalAlertasAbiertas > count($alertasRecientes)): ?>
                        <p class="alertas-mas">+<?= $totalAlertasAbiertas - count($alertasRecientes) ?> alerta(s) más</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="alertas-ok">✓ Sin alertas de stock crítico abiertas.</p>
                <?php endif; ?>
            </div>
            <a href="alertas_inventario.php" class="alertas-link">Ver todas las alertas →</a>
        </div>
    </div>

    <div class="ocupacion-panel">
        <h3>% Ocupación por tipo de habitación</h3>
        <div id="ocupacion-filas">
            <?php foreach ($ocupacionPorTipo as $fila): ?>
                <div class="ocupacion-fila">
                    <div class="ocupacion-fila-header">
                        <span><strong><?= htmlspecialchars($fila['tipo']) ?></strong> (<?= $fila['total_habitaciones'] ?> hab.)</span>
                        <span><strong><?= number_format($fila['pct_ocupacion'], 1) ?>%</strong></span>
                    </div>
                    <div class="barra-fondo">
                        <div class="barra-relleno" style="width: <?= min(100, $fila['pct_ocupacion']) ?>%;"></div>
                    </div>
                    <div class="ocupacion-detalle">
                        Disponibles: <?= $fila['disponibles'] ?> ·
                        Ocupadas: <?= $fila['ocupadas'] ?> ·
                        Reservadas: <?= $fila['reservadas'] ?> ·
                        Mantenimiento: <?= $fila['en_mantenimiento'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="fila-widget3">
        <div class="stat-panel">
            <p class="stat-titulo">Caja del día</p>
            <h2 id="widget3-caja-h2">$<?= number_format($totalCajaHoy, 0, ',', '.') ?></h2>
            <p class="stat-nota" id="widget3-caja-nota">
                <?php if ($totalCajaHoy > 0): ?>
                    Total de facturas pagadas hoy
                <?php else: ?>
                    Sin facturas registradas hoy — el módulo de Facturación aún no existe (Actividad X)
                <?php endif; ?>
            </p>
        </div>
        <div class="stat-panel">
            <p class="stat-titulo">Tareas completadas hoy</p>
            <h2 id="widget3-tareas-h2"><?= $pctTareasCompletadasHoy !== null ? $pctTareasCompletadasHoy . '%' : '—' ?></h2>
            <p class="stat-nota" id="widget3-tareas-nota">
                <?php if ($totalTareasHoy > 0): ?>
                    <?= $totalTareasCompletadasHoy ?> de <?= $totalTareasHoy ?> tareas ·
                <?php else: ?>
                    Sin tareas registradas hoy ·
                <?php endif; ?>
                <a href="tareas_personal.php">Ver tareas →</a>
            </p>
        </div>
    </div>
</div>

<script>
// Semana 14 Día 3 — Actualización automática del Dashboard ejecutivo cada 30
// segundos, sin recargar la página. Consulta el endpoint JSON construido en
// el Día 2 (modules/dashboard/dashboard_datos.php) y solo reemplaza los
// números y widgets en el DOM.
const INTERVALO_ACTUALIZACION_MS = 30000;
const URL_DATOS_DASHBOARD = '<?= BASE_URL ?>modules/dashboard/dashboard_datos.php';

const COLOR_ESTADO_HABITACION = {
    'Disponible':    '#2E7D32',
    'Ocupada':       '#C62828',
    'Mantenimiento': '#F9A825',
    'Reservada':     '#1565C0',
};

function colorEstadoHabitacionJS(estado) {
    return COLOR_ESTADO_HABITACION[estado] || '#757575';
}

// Igual que htmlspecialchars() en PHP: evita que un dato con < > & " termine
// interpretándose como HTML al insertarlo con innerHTML.
function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function actualizarKPIs(d) {
    document.getElementById('kpi-habitaciones').textContent =
        d.totalHabitacionesDisponibles + '/' + d.mapaHabitaciones.length;
    document.getElementById('kpi-reservas').textContent = d.totalReservasHoy;
    document.getElementById('kpi-clientes').textContent = d.totalClientesActivos;
    document.getElementById('kpi-personal').textContent = d.totalPersonalActivo;
    document.getElementById('kpi-stock').textContent = d.totalStockCritico;
    document.getElementById('kpi-stock-div').classList.toggle('kpi-alerta', d.totalStockCritico > 0);
}

function actualizarMapaHabitaciones(mapa) {
    document.getElementById('mapa-grid').innerHTML = mapa.map(hab => `
        <a href="habitacion_detalle.php?id=${hab.id_habitacion}"
           class="mapa-celda"
           style="background-color: ${colorEstadoHabitacionJS(hab.estado)};"
           title="Hab. ${escaparHtml(hab.numero_hab)} — ${escaparHtml(hab.tipo)} — ${escaparHtml(hab.estado)}">
            <span class="num">${escaparHtml(hab.numero_hab)}</span>
            <span class="tipo">${escaparHtml(hab.tipo)}</span>
        </a>
    `).join('');
}

function actualizarAlertas(d) {
    document.getElementById('panel-alertas').classList.toggle('panel-alertas-critico', d.totalAlertasAbiertas > 0);
    document.getElementById('alertas-badge').textContent = d.totalAlertasAbiertas;

    const cuerpo = document.getElementById('alertas-cuerpo');
    if (d.totalAlertasAbiertas > 0) {
        let html = '<ul class="alertas-lista">' + d.alertasRecientes.map(a => `
            <li>
                <span class="alerta-insumo">${escaparHtml(a.nombre_item)}</span>
                <span class="alerta-stock">${parseInt(a.stock_al_generar, 10)} / mín. ${parseInt(a.stock_minimo_al_generar, 10)}</span>
            </li>
        `).join('') + '</ul>';
        if (d.totalAlertasAbiertas > d.alertasRecientes.length) {
            html += `<p class="alertas-mas">+${d.totalAlertasAbiertas - d.alertasRecientes.length} alerta(s) más</p>`;
        }
        cuerpo.innerHTML = html;
    } else {
        cuerpo.innerHTML = '<p class="alertas-ok">✓ Sin alertas de stock crítico abiertas.</p>';
    }
}

function actualizarOcupacion(filas) {
    document.getElementById('ocupacion-filas').innerHTML = filas.map(fila => `
        <div class="ocupacion-fila">
            <div class="ocupacion-fila-header">
                <span><strong>${escaparHtml(fila.tipo)}</strong> (${fila.total_habitaciones} hab.)</span>
                <span><strong>${parseFloat(fila.pct_ocupacion).toFixed(1)}%</strong></span>
            </div>
            <div class="barra-fondo">
                <div class="barra-relleno" style="width: ${Math.min(100, parseFloat(fila.pct_ocupacion))}%;"></div>
            </div>
            <div class="ocupacion-detalle">
                Disponibles: ${fila.disponibles} ·
                Ocupadas: ${fila.ocupadas} ·
                Reservadas: ${fila.reservadas} ·
                Mantenimiento: ${fila.en_mantenimiento}
            </div>
        </div>
    `).join('');
}

function actualizarWidget3(d) {
    document.getElementById('widget3-caja-h2').textContent =
        '$' + Number(d.totalCajaHoy).toLocaleString('es-CO', { maximumFractionDigits: 0 });
    document.getElementById('widget3-caja-nota').textContent = d.totalCajaHoy > 0
        ? 'Total de facturas pagadas hoy'
        : 'Sin facturas registradas hoy — el módulo de Facturación aún no existe (Actividad X)';

    document.getElementById('widget3-tareas-h2').textContent =
        d.pctTareasCompletadasHoy !== null ? d.pctTareasCompletadasHoy + '%' : '—';
    document.getElementById('widget3-tareas-nota').innerHTML =
        (d.totalTareasHoy > 0
            ? `${d.totalTareasCompletadasHoy} de ${d.totalTareasHoy} tareas · `
            : 'Sin tareas registradas hoy · ')
        + '<a href="tareas_personal.php">Ver tareas →</a>';
}

function marcarUltimaActualizacion(exito) {
    const el = document.getElementById('ultima-actualizacion');
    const hora = new Date().toLocaleTimeString('es-CO');
    el.textContent = exito
        ? `Última actualización: ${hora} (se refresca solo cada 30 segundos)`
        : `No se pudo actualizar (${hora}) — se reintentará en 30 segundos`;
}

async function actualizarDashboard() {
    try {
        const resp = await fetch(URL_DATOS_DASHBOARD, { cache: 'no-store' });
        if (!resp.ok) {
            throw new Error('HTTP ' + resp.status);
        }
        const datos = await resp.json();

        actualizarKPIs(datos);
        actualizarMapaHabitaciones(datos.mapaHabitaciones);
        actualizarAlertas(datos);
        actualizarOcupacion(datos.ocupacionPorTipo);
        actualizarWidget3(datos);
        marcarUltimaActualizacion(true);
    } catch (error) {
        console.error('Error actualizando el Dashboard:', error);
        marcarUltimaActualizacion(false);
    }
}

setInterval(actualizarDashboard, INTERVALO_ACTUALIZACION_MS);
</script>
</body>
</html>
