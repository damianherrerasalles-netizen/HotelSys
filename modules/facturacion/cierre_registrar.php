<?php
// modules/facturacion/cierre_registrar.php
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 4 — Registra el cierre de caja diario por método de pago.
//
// El administrador declara cuánto contó físicamente por cada método; el
// sistema recalcula en este mismo momento (nunca confía en un valor oculto
// del formulario) cuánto deberían sumar las facturas Pagadas de ese día y
// ese método, y guarda ambos valores — la diferencia (columna generada
// "diferencia") es la alerta de descuadre que pidió el levantamiento (P14).

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../includes/facturacion_helpers.php';

$pdo = getConexion();

function volverACierre(string $fecha, string $msg, string $tipo = 'error'): void {
    $_SESSION['cierre_mensaje'] = $msg;
    $_SESSION['cierre_mensaje_tipo'] = $tipo;
    header('Location: ' . BASE_URL . 'views/cierre_caja.php?fecha=' . urlencode($fecha));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/cierre_caja.php');
    exit();
}

// P14: "el administrador necesita cierre de caja diario por método de pago"
if (!esAdmin()) {
    header('Location: ' . BASE_URL . 'views/cierre_caja.php');
    exit();
}

$fecha = $_POST['fecha'] ?? '';
$fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);

// Comparamos las fechas como texto 'Y-m-d' (ordenan igual que fechas reales)
// en vez de comparar objetos DateTime: createFromFormat() sin un componente de
// hora rellena la hora actual del reloj, mientras que "today" siempre es
// medianoche — eso hacía que la fecha de HOY pareciera "futura" después de
// medianoche y bloqueaba el cierre de caja del propio día.
if (!$fechaObj || $fecha > date('Y-m-d')) {
    volverACierre(date('Y-m-d'), 'Fecha inválida para el cierre de caja.');
}

// Los únicos métodos que el hostal realmente maneja (P14) — los mismos que
// ofrece el formulario de registrar pago de una factura.
$metodosValidos = ['Efectivo', 'Transferencia', 'Tarjeta', 'Nequi'];
$montosDeclarados = $_POST['monto_declarado'] ?? [];

$idPersonal = resolverIdPersonalDesdeSesion($pdo, (int) $_SESSION['usuario_id']);

try {
    $huboDescuadre = false;
    $algunoRegistrado = false;

    foreach ($metodosValidos as $metodo) {
        if (!isset($montosDeclarados[$metodo]) || $montosDeclarados[$metodo] === '') {
            continue; // el admin puede cerrar solo los métodos que ya revisó
        }

        $montoDeclarado = filter_var($montosDeclarados[$metodo], FILTER_VALIDATE_FLOAT);
        if ($montoDeclarado === false || $montoDeclarado < 0) {
            volverACierre($fecha, "El monto declarado para $metodo no es válido.");
        }

        // Recalcular el monto del sistema en este momento — nunca se confía
        // en un total que haya viajado oculto en el formulario.
        $stmtSistema = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) AS total_metodo
             FROM facturas
             WHERE estado = 'Pagada' AND metodo_pago = :metodo AND DATE(fecha_emision) = :fecha"
        );
        $stmtSistema->execute([':metodo' => $metodo, ':fecha' => $fecha]);
        $montoSistema = (float) $stmtSistema->fetch(PDO::FETCH_ASSOC)['total_metodo'];

        $stmtUpsert = $pdo->prepare(
            "INSERT INTO cierres_caja (fecha, metodo_pago, monto_sistema, monto_declarado, id_personal)
             VALUES (:fecha, :metodo, :monto_sistema, :monto_declarado, :id_personal)
             ON DUPLICATE KEY UPDATE
                monto_sistema = VALUES(monto_sistema),
                monto_declarado = VALUES(monto_declarado),
                id_personal = VALUES(id_personal)"
        );
        $stmtUpsert->execute([
            ':fecha'           => $fecha,
            ':metodo'          => $metodo,
            ':monto_sistema'   => $montoSistema,
            ':monto_declarado' => $montoDeclarado,
            ':id_personal'     => $idPersonal,
        ]);

        $algunoRegistrado = true;
        if (abs($montoDeclarado - $montoSistema) > 0.01) {
            $huboDescuadre = true;
        }
    }

    if (!$algunoRegistrado) {
        volverACierre($fecha, 'No se indicó ningún monto declarado para registrar.');
    }

    if ($huboDescuadre) {
        volverACierre($fecha, 'Cierre registrado — se detectó una descuadre en uno o más métodos. Revisa el detalle abajo.', 'error');
    }

    volverACierre($fecha, 'Cierre de caja registrado correctamente. Todo cuadra.', 'exito');

} catch (PDOException $e) {
    error_log('Error al registrar cierre de caja: ' . $e->getMessage());
    volverACierre($fecha, 'Ocurrió un error al registrar el cierre de caja. Intenta de nuevo.');
}
