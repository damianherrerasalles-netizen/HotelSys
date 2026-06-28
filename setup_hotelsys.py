"""
setup_hotelsys.py
Crea la estructura de carpetas y archivos base del proyecto HotelSys.
Ejecutar desde la carpeta raiz del proyecto HotelSys

Uso:
    python setup_hotelsys.py
"""

import os

# ── Colores para la consola Windows ──────────────────────────────────────────
VERDE  = "\033[92m"
AZUL   = "\033[94m"
AMARILLO = "\033[93m"
ROJO   = "\033[91m"
RESET  = "\033[0m"
BOLD   = "\033[1m"

def log_ok(msg):   print(f"  {VERDE}✓{RESET}  {msg}")
def log_info(msg): print(f"  {AZUL}→{RESET}  {msg}")
def log_skip(msg): print(f"  {AMARILLO}~{RESET}  {msg} (ya existe)")

# ── Estructura de carpetas ────────────────────────────────────────────────────
CARPETAS = [
    "assets/css",
    "assets/js",
    "assets/img",
    "config",
    "includes",
    "modules/reservas",
    "modules/habitaciones",
    "modules/clientes",
    "modules/personal",
    "modules/inventario",
    "modules/facturacion",
    "views",
]

# ── Archivos base con contenido inicial ──────────────────────────────────────
ARCHIVOS = {

    # ── index.php ─────────────────────────────────────────────────────────────
    "index.php": """\
<?php
// index.php — Punto de entrada de HotelSys
// Redirige al login si no hay sesión activa

session_start();

if (isset($_SESSION['usuario_id'])) {
    // Si ya hay sesión activa, redirige según rol
    if ($_SESSION['rol'] === 'admin') {
        header('Location: views/dashboard.php');
    } else {
        header('Location: views/reservas.php');
    }
} else {
    header('Location: views/login.php');
}
exit();
""",

    # ── config/db.php ─────────────────────────────────────────────────────────
    "config/db.php": """\
<?php
// config/db.php — Conexión PDO a MySQL
// HotelSys — Hotel Plaza Hostal

define('DB_HOST', 'localhost');
define('DB_NAME', 'hotelsys');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getConexion(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En producción nunca mostrar el mensaje real
            error_log("Error de conexión: " . $e->getMessage());
            die(json_encode([
                'error' => 'No se pudo conectar a la base de datos.'
            ]));
        }
    }

    return $pdo;
}
""",

    # ── includes/auth.php ─────────────────────────────────────────────────────
    "includes/auth.php": """\
<?php
// includes/auth.php — Lógica de autenticación
// HotelSys — Hotel Plaza Hostal

require_once __DIR__ . '/../config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit();
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// Validación básica
if (empty($email) || empty($password)) {
    header('Location: ../views/login.php?error=campos_vacios');
    exit();
}

// Buscar usuario en BD con prepared statement (previene inyección SQL)
$pdo  = getConexion();
$stmt = $pdo->prepare(
    "SELECT id, nombre, password_hash, rol
     FROM usuarios
     WHERE email = :email AND estado = 1
     LIMIT 1"
);
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch();

// Verificar contraseña con password_verify()
if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    header('Location: ../views/login.php?error=credenciales_invalidas');
    exit();
}

// Autenticación exitosa — guardar sesión
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['rol']        = $usuario['rol'];

// Redirigir según rol
if ($usuario['rol'] === 'admin') {
    header('Location: ../views/dashboard.php');
} else {
    header('Location: ../views/reservas.php');
}
exit();
""",

    # ── includes/check_auth.php ───────────────────────────────────────────────
    "includes/check_auth.php": """\
<?php
// includes/check_auth.php — Middleware de protección de rutas
// Incluir al inicio de cada vista protegida
// HotelSys — Hotel Plaza Hostal

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'views/login.php');
    exit();
}

// Función helper: verificar si el usuario es admin
function esAdmin(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// Función helper: requerir rol admin (redirige si no lo es)
function requerirAdmin(): void {
    if (!esAdmin()) {
        header('Location: ' . BASE_URL . 'views/reservas.php');
        exit();
    }
}
""",

    # ── includes/logout.php ───────────────────────────────────────────────────
    "includes/logout.php": """\
<?php
// includes/logout.php — Cierre de sesión seguro
// HotelSys — Hotel Plaza Hostal

session_start();

// Destruir todos los datos de sesión
$_SESSION = [];

// Eliminar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Redirigir al login
header('Location: ../views/login.php?msg=sesion_cerrada');
exit();
""",

    # ── views/login.php ───────────────────────────────────────────────────────
    "views/login.php": """\
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
""",

    # ── views/dashboard.php ───────────────────────────────────────────────────
    "views/dashboard.php": """\
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
""",

    # ── assets/css/ placeholder ───────────────────────────────────────────────
    "assets/css/hotelsys.css": """\
/* hotelsys.css — Estilos globales de HotelSys */
/* Hotel Plaza Hostal · Yarumal, Antioquia     */

:root {
    --verde:       #2E7D32;
    --verde-med:   #388E3C;
    --verde-clar:  #C8E6C9;
    --verde-fondo: #F1F8E9;
    --gris:        #F5F5F5;
    --gris-borde:  #BDBDBD;
    --rojo:        #C62828;
    --negro:       #212121;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--gris);
    color: var(--negro);
}
""",

    # ── assets/js/ placeholder ────────────────────────────────────────────────
    "assets/js/hotelsys.js": """\
// hotelsys.js — Scripts globales de HotelSys
// Hotel Plaza Hostal · Yarumal, Antioquia

console.log('HotelSys cargado correctamente.');
""",
}


# ── Ejecución ─────────────────────────────────────────────────────────────────

def main():
    print(f"\n{BOLD}{'='*55}{RESET}")
    print(f"{BOLD}  HotelSys — Setup Semana 6 · Estructura del proyecto{RESET}")
    print(f"{BOLD}{'='*55}{RESET}\n")

    base = os.path.dirname(os.path.abspath(__file__))
    log_info(f"Directorio base: {base}\n")

    # Crear carpetas
    print(f"{BOLD}  Carpetas:{RESET}")
    for carpeta in CARPETAS:
        ruta = os.path.join(base, carpeta)
        if os.path.exists(ruta):
            log_skip(carpeta)
        else:
            os.makedirs(ruta)
            log_ok(carpeta)

    print()

    # Crear archivos
    print(f"{BOLD}  Archivos:{RESET}")
    for archivo, contenido in ARCHIVOS.items():
        ruta = os.path.join(base, archivo)
        if os.path.exists(ruta):
            log_skip(archivo)
        else:
            os.makedirs(os.path.dirname(ruta), exist_ok=True)
            with open(ruta, "w", encoding="utf-8") as f:
                f.write(contenido)
            log_ok(archivo)

    print(f"\n{BOLD}{'='*55}{RESET}")
    print(f"{VERDE}{BOLD}  ✅  Estructura creada correctamente.{RESET}")
    print(f"{BOLD}{'='*55}{RESET}")
    print(f"""
  Próximos pasos:
  1. Abre phpMyAdmin y ejecuta el SQL de la tabla usuarios
  2. Abre http://localhost/HotelSys/
  3. Continúa con el Día 2 — tabla usuarios y seed data
""")


if __name__ == "__main__":
    main()
