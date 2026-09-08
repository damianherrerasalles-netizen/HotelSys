<?php
// modules/personal/tarea_estado.php
// Semana 13 Día 4 — Marca una tarea como completada o la reabre
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';

$pdo = getConexion();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$accion = $_GET['accion'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$urlVolver = BASE_URL . 'views/tareas_personal.php?fecha=' . urlencode($fecha);

if ($id <= 0 || !in_array($accion, ['completar', 'reabrir'], true)) {
    header('Location: ' . $urlVolver);
    exit;
}

try {
    if ($accion === 'completar') {
        $stmt = $pdo->prepare(
            "UPDATE tareas_personal SET completada = 1, fecha_completada = NOW() WHERE id_tarea = :id"
        );
        $mensaje = 'Tarea marcada como completada.';
    } else {
        $stmt = $pdo->prepare(
            "UPDATE tareas_personal SET completada = 0, fecha_completada = NULL WHERE id_tarea = :id"
        );
        $mensaje = 'Tarea reabierta.';
    }
    $stmt->execute([':id' => $id]);

    $_SESSION['tareas_mensaje'] = $mensaje;
    $_SESSION['tareas_mensaje_tipo'] = 'exito';
} catch (PDOException $e) {
    error_log('Error en tarea_estado.php: ' . $e->getMessage());
    $_SESSION['tareas_mensaje'] = 'Ocurrió un error al actualizar la tarea.';
    $_SESSION['tareas_mensaje_tipo'] = 'error';
}

header('Location: ' . $urlVolver);
exit;
