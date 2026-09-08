<?php
// modules/dashboard/dashboard_datos.php
// Semana 14 Día 2 — Endpoint JSON con los datos del Dashboard ejecutivo.
//
// Reutiliza la misma función obtenerDatosDashboard() que usa views/dashboard.php
// al cargar la página, así que este endpoint siempre devuelve exactamente los
// mismos datos y con la misma lógica. El JavaScript del Día 3 lo consultará
// cada 30 segundos para refrescar el Dashboard sin recargar la página.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../includes/dashboard_datos.php';

requerirAdmin(false); // Mismo control de acceso que el resto del Dashboard

header('Content-Type: application/json; charset=utf-8');

$conexion = getConexion();
$datos = obtenerDatosDashboard($conexion);

echo json_encode($datos, JSON_UNESCAPED_UNICODE);
