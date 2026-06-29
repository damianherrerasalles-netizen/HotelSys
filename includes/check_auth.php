<?php
// includes/check_auth.php — Middleware de protección de rutas
// HotelSys — Hotel Plaza Hostal

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sin sesión → redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'views/login.php');
    exit();
}

// Verificar si es administrador
function esAdmin(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';
}

// Requerir rol administrador — redirige a acceso denegado si no lo es
// El segundo parámetro evita bucle infinito en la propia vista de acceso denegado
function requerirAdmin(bool $mostrarDenegado = true): void {
    if (!esAdmin()) {
        if ($mostrarDenegado) {
            header('Location: ' . BASE_URL . 'views/acceso_denegado.php');
        } else {
            header('Location: ' . BASE_URL . 'views/reservas.php');
        }
        exit();
    }
}