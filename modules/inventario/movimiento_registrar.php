<?php
// modules/inventario/movimiento_registrar.php
// Semana 12 — Kardex: registra una entrada o salida de inventario y
// actualiza stock_actual de forma consistente dentro de una transacción.
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
require_once $rutaBase . 'includes/validaciones_inventario.php'; // Semana 12 Día 4
requerirAdmin(); // Solo admin puede registrar movimientos de inventario

$pdo = getConexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/inventario.php');
    exit;
}

$idItem = isset($_POST['id_item']) ? (int) $_POST['id_item'] : 0;
$tipo = $_POST['tipo'] ?? '';
$motivo = trim($_POST['motivo'] ?? '');
$cantidad = trim($_POST['cantidad'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

$urlKardex = BASE_URL . 'views/inventario_kardex.php?id=' . $idItem;

// ---- Motivos válidos por tipo de movimiento (Semana 12) ----
$motivosEntrada = ['Compra a proveedor', 'Ajuste de inventario'];
$motivosSalida = ['Consumo operativo', 'Pérdida o daño', 'Ajuste de inventario'];

if ($idItem <= 0 || !in_array($tipo, ['entrada', 'salida'], true)) {
    $_SESSION['inventario_mensaje'] = 'Movimiento no válido.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlKardex);
    exit;
}

$motivosValidos = $tipo === 'entrada' ? $motivosEntrada : $motivosSalida;
if (!in_array($motivo, $motivosValidos, true)) {
    $_SESSION['inventario_mensaje'] = 'Selecciona un motivo válido para el tipo de movimiento elegido.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlKardex);
    exit;
}

$errorCantidad = validarEnteroNoNegativo($cantidad);
if ($errorCantidad !== null || (int) $cantidad <= 0) {
    $_SESSION['inventario_mensaje'] = 'La cantidad debe ser un número entero mayor que cero.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlKardex);
    exit;
}
$cantidad = (int) $cantidad;

$errorObservaciones = validarObservaciones($observaciones);
if ($errorObservaciones !== null) {
    $_SESSION['inventario_mensaje'] = $errorObservaciones;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlKardex);
    exit;
}
$observaciones = $observaciones !== '' ? $observaciones : null;

try {
    $pdo->beginTransaction();

    // SELECT ... FOR UPDATE bloquea la fila del insumo mientras calculamos el
    // nuevo stock, para evitar que dos movimientos simultáneos se pisen entre sí.
    $stmt = $pdo->prepare("SELECT stock_actual, stock_minimo, nombre_item FROM inventario WHERE id_item = :id FOR UPDATE");
    $stmt->execute([':id' => $idItem]);
    $insumo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$insumo) {
        throw new RuntimeException('El insumo solicitado no existe.');
    }

    $stockActual = (int) $insumo['stock_actual'];
    $stockMinimo = (int) $insumo['stock_minimo'];
    $stockNuevo = $tipo === 'entrada' ? ($stockActual + $cantidad) : ($stockActual - $cantidad);

    if ($stockNuevo < 0) {
        throw new RuntimeException(
            'No hay suficiente stock de "' . $insumo['nombre_item'] . '" para registrar esa salida ' .
            '(disponible: ' . $stockActual . ').'
        );
    }

    $pdo->prepare("UPDATE inventario SET stock_actual = :stock WHERE id_item = :id")
        ->execute([':stock' => $stockNuevo, ':id' => $idItem]);

    $pdo->prepare(
        "INSERT INTO movimientos_inventario
            (id_item, tipo, motivo, cantidad, stock_resultante, usuario, observaciones)
         VALUES (:id_item, :tipo, :motivo, :cantidad, :stock_resultante, :usuario, :observaciones)"
    )->execute([
        ':id_item' => $idItem,
        ':tipo' => $tipo,
        ':motivo' => $motivo,
        ':cantidad' => $cantidad,
        ':stock_resultante' => $stockNuevo,
        ':usuario' => $_SESSION['nombre'] ?? 'Administrador',
        ':observaciones' => $observaciones,
    ]);

    // --- Semana 12 Día 2: registro automático de alertas ---
    // Busca si ya hay una alerta abierta para este insumo (evita duplicados).
    $stmtAlertaAbierta = $pdo->prepare(
        "SELECT id_alerta FROM alertas_inventario WHERE id_item = :id AND estado = 'abierta' LIMIT 1"
    );
    $stmtAlertaAbierta->execute([':id' => $idItem]);
    $alertaAbierta = $stmtAlertaAbierta->fetch(PDO::FETCH_ASSOC);

    $mensajeAlerta = '';
    if ($stockNuevo <= $stockMinimo) {
        // Stock crítico: si no hay una alerta abierta ya, se crea una nueva.
        if (!$alertaAbierta) {
            $pdo->prepare(
                "INSERT INTO alertas_inventario (id_item, stock_al_generar, stock_minimo_al_generar, estado)
                 VALUES (:id_item, :stock, :stock_minimo, 'abierta')"
            )->execute([
                ':id_item' => $idItem,
                ':stock' => $stockNuevo,
                ':stock_minimo' => $stockMinimo,
            ]);
            $mensajeAlerta = ' Se generó una alerta de stock crítico para este insumo.';
        }
    } else {
        // Stock recuperado: si había una alerta abierta, se cierra sola.
        if ($alertaAbierta) {
            $pdo->prepare(
                "UPDATE alertas_inventario
                 SET estado = 'resuelta_automaticamente', fecha_cierre = NOW(), atendida_por = 'Sistema (stock recuperado)'
                 WHERE id_alerta = :id_alerta"
            )->execute([':id_alerta' => $alertaAbierta['id_alerta']]);
            $mensajeAlerta = ' La alerta de stock crítico de este insumo se cerró automáticamente.';
        }
    }

    $pdo->commit();

    $_SESSION['inventario_mensaje'] = 'Movimiento registrado correctamente. Nuevo stock: ' . $stockNuevo . '.' . $mensajeAlerta;
    $_SESSION['inventario_mensaje_tipo'] = 'exito';
} catch (RuntimeException $e) {
    $pdo->rollBack();
    $_SESSION['inventario_mensaje'] = $e->getMessage();
    $_SESSION['inventario_mensaje_tipo'] = 'error';
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Error en movimiento_registrar.php: ' . $e->getMessage());
    $_SESSION['inventario_mensaje'] = 'Ocurrió un error al registrar el movimiento. Intenta nuevamente.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
}

header('Location: ' . $urlKardex);
exit;
