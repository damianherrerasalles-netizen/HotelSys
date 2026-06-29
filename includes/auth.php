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

// Buscar usuario activo con prepared statement
$pdo  = getConexion();
$stmt = $pdo->prepare(
    "SELECT id, nombre, password, rol
     FROM usuarios
     WHERE email = :email AND activo = 1
     LIMIT 1"
);
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch();

// Verificar contraseña
if (!$usuario || !password_verify($password, $usuario['password'])) {
    header('Location: ../views/login.php?error=credenciales_invalidas');
    exit();
}

// Sesión exitosa
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['rol']        = $usuario['rol'];


// Redirigir según rol
if ($usuario['rol'] === 'administrador') {
    header('Location: ../views/dashboard.php');
} else {
    header('Location: ../views/reservas.php');
}
exit();