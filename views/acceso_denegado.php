<?php
// views/acceso_denegado.php
// Se muestra cuando un recepcionista intenta entrar a rutas de admin

define('BASE_URL', '../');
require_once BASE_URL . 'includes/check_auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HotelSys — Acceso denegado</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #F5F5F5;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            padding: 40px;
            text-align: center;
            max-width: 420px;
            width: 100%;
        }
        .icono { font-size: 3rem; margin-bottom: 16px; }
        h1 { color: #C62828; font-size: 1.4rem; margin-bottom: 12px; }
        p  { color: #757575; font-size: 0.9rem; margin-bottom: 24px; }
        .btn {
            display: inline-block;
            background: #2E7D32; color: #fff;
            padding: 10px 28px; border-radius: 4px;
            text-decoration: none; font-weight: bold;
        }
        .btn:hover { background: #1B5E20; }
        .usuario {
            background: #F1F8E9; border: 1px solid #C8E6C9;
            border-radius: 4px; padding: 8px 16px;
            font-size: 0.85rem; color: #2E7D32;
            margin-bottom: 20px; display: inline-block;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="icono">🔒</div>
    <h1>Acceso denegado</h1>
    <div class="usuario">
        Sesión activa: <?= htmlspecialchars($_SESSION['nombre']) ?>
        (<?= htmlspecialchars($_SESSION['rol']) ?>)
    </div>
    <p>No tienes permisos para acceder a esta sección.<br>
       Esta área es exclusiva del administrador del sistema.</p>
    <a href="../views/login.php" class="btn">Volver al inicio</a>
</div>
</body>
</html>