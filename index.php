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
