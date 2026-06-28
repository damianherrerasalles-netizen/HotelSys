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
