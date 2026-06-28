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
