<?php
// modules/inventario/alerta_atender.php
// Semana 12 Día 2 — marca una alerta de inventario como atendida manualmente.
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin gestiona alertas de inventario

$pdo = getConexion();

$idAlerta = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$urlAlertas = BASE_URL . 'views/alertas_inventario.php';

if ($idAlerta <= 0) {
    $_SESSION['inventario_mensaje'] = 'Alerta no válida.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlAlertas);
    exit;
}

$stmt = $pdo->prepare("SELECT id_alerta FROM alertas_inventario WHERE id_alerta = :id AND estado = 'abierta'");
$stmt->execute([':id' => $idAlerta]);
$alerta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alerta) {
    $_SESSION['inventario_mensaje'] = 'La alerta ya fue atendida, se resolvió sola o no existe.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlAlertas);
    exit;
}

$pdo->prepare(
    "UPDATE alertas_inventario
     SET estado = 'atendida', fecha_cierre = NOW(), atendida_por = :usuario
     WHERE id_alerta = :id"
)->execute([
    ':usuario' => $_SESSION['nombre'] ?? 'Administrador',
    ':id' => $idAlerta,
]);

$_SESSION['inventario_mensaje'] = 'Alerta marcada como atendida.';
$_SESSION['inventario_mensaje_tipo'] = 'exito';
header('Location: ' . $urlAlertas);
exit;
