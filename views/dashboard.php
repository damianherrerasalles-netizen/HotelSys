<?php
// views/dashboard.php — Panel ejecutivo (solo admin)
// HotelSys — Hotel Plaza Hostal

define('BASE_URL', '../');
require_once BASE_URL . 'includes/check_auth.php';
requerirAdmin(); // Solo admin puede ver el dashboard
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
        .kpi-grid  { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .kpi {
            background: #fff; border: 1px solid #C8E6C9;
            border-radius: 8px; padding: 20px; text-align: center;
        }
        .kpi h2 { font-size: 2rem; color: #2E7D32; margin: 8px 0 4px; }
        .kpi p  { font-size: 0.85rem; color: #757575; }
    </style>
</head>
<body>
<div class="header">
    <h1>HotelSys &mdash; Dashboard</h1>
    <div>
        👤 <?= htmlspecialchars($_SESSION['nombre']) ?> &nbsp;|&nbsp;
        <a href="../includes/logout.php">Cerrar sesión</a>
    </div>
</div>
<div class="content">
    <p class="welcome">
        Bienvenido, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>.
        Panel de administración del Hotel Plaza Hostal.
    </p>
    <div class="kpi-grid">
        <div class="kpi">
            <p>Habitaciones ocupadas</p>
            <h2>—</h2>
            <p>Módulo Mes 3</p>
        </div>
        <div class="kpi">
            <p>Reservas hoy</p>
            <h2>—</h2>
            <p>Módulo Mes 2</p>
        </div>
        <div class="kpi">
            <p>Ingresos del mes</p>
            <h2>—</h2>
            <p>Módulo Mes 4</p>
        </div>
    </div>
</div>
</body>
</html>
