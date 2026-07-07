<?php
// views/logout.php — Cierre de sesión
// HotelSys — Hotel Plaza Hostal

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php'; // Define BASE_URL

// Limpiar todas las variables de sesión
$_SESSION = [];

// Eliminar la cookie de sesión del navegador (si existe)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: ' . BASE_URL . 'views/login.php');
exit();