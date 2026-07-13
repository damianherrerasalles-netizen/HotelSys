<?php
// views/dashboard.php — Panel ejecutivo (solo administrador)
// HotelSys — Hotel Plaza Hostal
$rutaBase = '../'; // Ruta relativa SOLO para los require_once de este archivo
require_once $rutaBase . 'config/db.php';   // Aquí db.php define BASE_URL (la URL completa)
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin();

$conexion = getConexion();

$stmtOcupacion = $conexion->query("SELECT * FROM v_ocupacion_por_tipo ORDER BY FIELD(tipo, 'Sencilla','Doble','Triple','Suite')");
$ocupacionPorTipo = $stmtOcupacion->fetchAll(PDO::FETCH_ASSOC);

// Total de clientes activos, para la tarjeta del modulo de Clientes
$stmtClientes = $conexion->query("SELECT COUNT(*) AS total FROM clientes WHERE activo = 1");
$totalClientesActivos = $stmtClientes->fetch(PDO::FETCH_ASSOC)['total'];
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
    <div class="kpi-grid">
        <a href="habitaciones.php" class="kpi-link">
            <div class="kpi">
                <p>Habitaciones</p>
                <h2>—</h2>
                <p>Ver estado en tiempo real →</p>
            </div>
        </a>
        <a href="reservas.php" class="kpi-link">
            <div class="kpi">
                <p>Reservas hoy</p>
                <h2>—</h2>
                <p>Ir al módulo de Reservas →</p>
            </div>
        </a>
        <a href="clientes.php" class="kpi-link">
            <div class="kpi">
                <p>Clientes activos</p>
                <h2><?= (int)$totalClientesActivos ?></h2>
                <p>Ir al módulo de Clientes →</p>
            </div>
        </a>
        <div class="kpi">
            <p>Ingresos del mes</p>
            <h2>—</h2>
            <p>Disponible en Mes 4</p>
        </div>
    </div>

    <div class="ocupacion-panel">
        <h3>% Ocupación por tipo de habitación</h3>
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
</body>
</html>