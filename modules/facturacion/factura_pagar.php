<?php
// modules/facturacion/factura_pagar.php
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 3 — Registra el pago de una factura Pendiente.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/check_auth.php';

$pdo = getConexion();

function volverAFactura(int $idFactura, string $msg, string $tipo = 'error'): void {
    $_SESSION['factura_mensaje'] = $msg;
    $_SESSION['factura_mensaje_tipo'] = $tipo;
    header('Location: ' . BASE_URL . 'views/factura_detalle.php?id=' . $idFactura);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/facturas.php');
    exit();
}

$id_factura  = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
$metodo_pago = $_POST['metodo_pago'] ?? '';

// Métodos que el hostal realmente maneja (P14 del levantamiento): Efectivo,
// Nequi, Transferencia (Bancolombia) y Tarjeta débito — sin crédito/cuotas,
// y sin Daviplata, que el ENUM de la BD permite pero el hostal no ofrece.
$metodosValidos = ['Efectivo', 'Transferencia', 'Tarjeta', 'Nequi'];

if (!$id_factura) {
    header('Location: ' . BASE_URL . 'views/facturas.php');
    exit();
}

if (!in_array($metodo_pago, $metodosValidos, true)) {
    volverAFactura($id_factura, 'Selecciona un método de pago válido.');
}

try {
    $stmt = $pdo->prepare("SELECT estado FROM facturas WHERE id_factura = :id");
    $stmt->execute([':id' => $id_factura]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        header('Location: ' . BASE_URL . 'views/facturas.php');
        exit();
    }

    // Evita reprocesar un pago (doble clic) o "despagar" una factura Anulada
    if ($factura['estado'] !== 'Pendiente') {
        volverAFactura(
            $id_factura,
            'Esta factura ya no está pendiente de pago (estado actual: ' . $factura['estado'] . ').'
        );
    }

    $stmtUpdate = $pdo->prepare(
        "UPDATE facturas SET estado = 'Pagada', metodo_pago = :metodo WHERE id_factura = :id"
    );
    $stmtUpdate->execute([
        ':metodo' => $metodo_pago,
        ':id'     => $id_factura,
    ]);

    volverAFactura($id_factura, 'Pago registrado correctamente. Factura marcada como Pagada.', 'exito');

} catch (PDOException $e) {
    error_log('Error al registrar pago de factura: ' . $e->getMessage());
    volverAFactura($id_factura, 'Ocurrió un error al registrar el pago. Intenta de nuevo.');
}
