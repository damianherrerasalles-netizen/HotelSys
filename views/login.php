<?php
// views/login.php — Pantalla de acceso a HotelSys
// Basado en wireframe Semana 5

session_start();

// Si ya hay sesión, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Mensajes de error
$errores = [
    'credenciales_invalidas' => 'Correo o contraseña incorrectos.',
    'campos_vacios'          => 'Por favor completa todos los campos.',
    'sesion_cerrada'         => '',
];
$error = isset($_GET['error']) ? ($errores[$_GET['error']] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelSys — Iniciar sesión</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #F5F5F5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: #fff;
            width: 100%;
            max-width: 420px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            overflow: hidden;
        }

        .card-header {
            background: #2E7D32;
            color: #fff;
            text-align: center;
            padding: 28px 20px 20px;
        }

        .card-header h1 { font-size: 1.8rem; margin-bottom: 4px; }
        .card-header p  { font-size: 0.85rem; color: #C8E6C9; }

        .card-body { padding: 28px; }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #BDBDBD;
            border-radius: 4px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
        }

        input:focus { border-color: #2E7D32; }

        .error-box {
            background: #FFEBEE;
            border: 1px solid #F44336;
            color: #C62828;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 0.875rem;
            margin-bottom: 16px;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #2E7D32;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover { background: #1B5E20; }

        .card-footer {
            text-align: center;
            padding: 14px;
            border-top: 1px solid #F0F0F0;
            font-size: 0.78rem;
            color: #9E9E9E;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h1>HotelSys</h1>
        <p>Hotel Plaza Hostal &mdash; Yarumal, Antioquia</p>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="../includes/auth.php">
            <div class="form-group">
                <label for="email">Correo electrónico *</label>
                <input type="email" id="email" name="email"
                       placeholder="ejemplo@hotel.com" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••••" required>
            </div>
            <button type="submit" class="btn-login">INICIAR SESIÓN</button>
        </form>
    </div>
    <div class="card-footer">
        Sistema de uso exclusivo del personal autorizado
    </div>
</div>
</body>
</html>
