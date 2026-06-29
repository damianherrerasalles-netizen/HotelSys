<?php
// views/reservas.php — Vista principal del recepcionista
// HotelSys — Hotel Plaza Hostal

define('BASE_URL', '../');
require_once BASE_URL . 'includes/check_auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HotelSys — Reservas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
        .info-box {
            background: #fff;
            border: 1px solid #C8E6C9;
            border-radius: 8px;
            padding: 24px;
            max-width: 500px;
            color: #555;
        }
        .info-box h2 { color: #2E7D32; margin-bottom: 10px; font-size: 1.1rem; }
        .info-box p  { font-size: 0.9rem; line-height: 1.6; }
    </style>
</head>
<body>
<div class="header">
    <h1>HotelSys &mdash; Reservas</h1>
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
    </p>
    <div class="info-box">
        <h2>Módulo de Reservas</h2>
        <p>Este módulo estará disponible en la <strong>Semana 7</strong>
           del cronograma del proyecto HotelSys.<br><br>
           Por ahora el sistema de autenticación con roles
           está funcionando correctamente.</p>
    </div>
</div>
</body>
</html>