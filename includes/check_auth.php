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
