<?php
// modules/personal/tarea_procesar.php
// Semana 13 Día 4 — Registrar una nueva tarea diaria del personal
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';

$pdo = getConexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/tareas_personal.php');
    exit;
}

$descripcion = trim($_POST['descripcion'] ?? '');
$idPersonal = isset($_POST['id_personal']) ? (int) $_POST['id_personal'] : 0;
$fecha = $_POST['fecha'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$urlVolver = BASE_URL . 'views/tareas_personal.php?fecha=' . urlencode($fecha);

// Validaciones mínimas
if ($descripcion === '') {
    $_SESSION['tareas_mensaje'] = 'La descripción de la tarea es obligatoria.';
    $_SESSION['tareas_mensaje_tipo'] = 'error';
    header('Location: ' . $urlVolver);
    exit;
}

if (mb_strlen($descripcion) > 255) {
    $_SESSION['tareas_mensaje'] = 'La descripción no puede superar 255 caracteres.';
    $_SESSION['tareas_mensaje_tipo'] = 'error';
    header('Location: ' . $urlVolver);
    exit;
}

if ($idPersonal <= 0) {
    $_SESSION['tareas_mensaje'] = 'Debes asignar la tarea a un colaborador.';
    $_SESSION['tareas_mensaje_tipo'] = 'error';
    header('Location: ' . $urlVolver);
    exit;
}

// Verificar que el colaborador exista y esté activo
$stmtVerifica = $pdo->prepare("SELECT id_personal FROM personal WHERE id_personal = :id AND activo = 1");
$stmtVerifica->execute([':id' => $idPersonal]);
if (!$stmtVerifica->fetch()) {
    $_SESSION['tareas_mensaje'] = 'El colaborador seleccionado no es válido.';
    $_SESSION['tareas_mensaje_tipo'] = 'error';
    header('Location: ' . $urlVolver);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO tareas_personal (id_personal, descripcion, fecha, creada_por)
         VALUES (:id_personal, :descripcion, :fecha, :creada_por)"
    );
    $stmt->execute([
        ':id_personal' => $idPersonal,
        ':descripcion' => $descripcion,
        ':fecha' => $fecha,
        ':creada_por' => $_SESSION['nombre'] ?? null,
    ]);

    $_SESSION['tareas_mensaje'] = 'Tarea registrada correctamente.';
    $_SESSION['tareas_mensaje_tipo'] = 'exito';
} catch (PDOException $e) {
    error_log('Error en tarea_procesar.php: ' . $e->getMessage());
    $_SESSION['tareas_mensaje'] = 'Ocurrió un error al registrar la tarea.';
    $_SESSION['tareas_mensaje_tipo'] = 'error';
}

header('Location: ' . $urlVolver);
exit;
