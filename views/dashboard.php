<?php
// views/dashboard.php — Panel ejecutivo (solo administrador)
// HotelSys — Hotel Plaza Hostal
define('BASE_URL', '../');
require_once BASE_URL . 'includes/check_auth.php';
requerirAdmin();
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
        .kpi-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
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
        <div class="kpi">
            <p>Habitaciones ocupadas</p>
            <h2>—</h2>
            <p>Disponible en Mes 3</p>
        </div>
        <a href="reservas.php" class="kpi-link">
            <div class="kpi">
                <p>Reservas hoy</p>
                <h2>—</h2>
                <p>Ir al módulo de Reservas →</p>
            </div>
        </a>
        <div class="kpi">
            <p>Ingresos del mes</p>
            <h2>—</h2>
            <p>Disponible en Mes 4</p>
        </div>
    </div>
</div>
</body>
</html>