<?php
// modules/inventario/inventario_estado.php
// Activa o desactiva un ítem de inventario (baja lógica, mismo patrón que
// el resto del sistema: nunca se borra físicamente un registro).
// Semana 11 — Módulo de Inventario
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin puede activar/desactivar insumos

$pdo = getConexion();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$accion = $_GET['accion'] ?? '';

if ($id <= 0 || !in_array($accion, ['activar', 'desactivar'], true)) {
    header('Location: ' . BASE_URL . 'views/inventario.php');
    exit;
}

$nuevoEstado = $accion === 'activar' ? 1 : 0;

try {
    $stmt = $pdo->prepare("UPDATE inventario SET activo = :activo WHERE id_item = :id");
    $stmt->execute([':activo' => $nuevoEstado, ':id' => $id]);

    $_SESSION['inventario_mensaje'] = $accion === 'activar'
        ? 'Insumo reactivado correctamente.'
        : 'Insumo desactivado correctamente.';
    $_SESSION['inventario_mensaje_tipo'] = 'exito';
} catch (PDOException $e) {
    error_log('Error en inventario_estado.php: ' . $e->getMessage());
    $_SESSION['inventario_mensaje'] = 'Ocurrió un error al cambiar el estado del insumo.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . 'views/inventario.php');
exit;
