<?php
// views/factura_detalle.php — Detalle e impresión de una factura
// HotelSys — Hotel Plaza Hostal
// Semana 15 Día 3 — Actividad X: Módulo de Facturación

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/check_auth.php';

$pdo = getConexion();

$id_factura = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_factura) {
    header('Location: ' . BASE_URL . 'views/facturas.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT f.id_factura, f.id_reserva, f.subtotal, f.iva_porcentaje, f.iva_valor, f.total,
            f.metodo_pago, f.estado, f.fecha_emision,
            CONCAT(c.nombres, ' ', c.apellidos) AS huesped,
            c.tipo_documento, c.num_documento, c.telefono AS tel_huesped, c.email AS email_huesped,
            r.fecha_entrada, r.fecha_salida, r.num_noches, r.precio_noche_aplicado, r.num_personas,
            h.numero_hab, h.tipo AS tipo_hab,
            CONCAT(p.nombres, ' ', p.apellidos) AS atendido_por
     FROM facturas f
     JOIN reservas r     ON r.id_reserva = f.id_reserva
     JOIN clientes c     ON c.id_cliente = f.id_cliente
     JOIN habitaciones h ON h.id_habitacion = r.id_habitacion
     LEFT JOIN personal p ON p.id_personal = f.id_personal
     WHERE f.id_factura = :id"
);
$stmt->execute([':id' => $id_factura]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
    $_SESSION['facturas_mensaje'] = 'La factura indicada no existe.';
    $_SESSION['facturas_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/facturas.php');
    exit();
}

// Cargos extra de la reserva facturada, para el desglose línea por línea
$stmtCargos = $pdo->prepare(
    "SELECT tipo, descripcion, valor, fecha FROM cargos_extras WHERE id_reserva = :id ORDER BY fecha ASC"
);
$stmtCargos->execute([':id' => $factura['id_reserva']]);
$cargosExtras = $stmtCargos->fetchAll(PDO::FETCH_ASSOC);

$totalHabitacion = (float) $factura['num_noches'] * (float) $factura['precio_noche_aplicado'];

$mensaje = $_SESSION['factura_mensaje'] ?? null;
$tipoMensaje = $_SESSION['factura_mensaje_tipo'] ?? 'error';
unset($_SESSION['factura_mensaje'], $_SESSION['factura_mensaje_tipo']);

// Reutiliza los badges de estado ya definidos en estilos.css (por color, no por nombre literal)
$badgeEstado = [
    'Pendiente' => 'badge-pendiente',
    'Pagada'    => 'badge-activo',
    'Anulada'   => 'badge-inactivo',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura #<?= (int)$factura['id_factura'] ?> — HotelSys</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; }
    .contenedor-factura { max-width: 760px; margin: 24px auto; padding: 0 20px; }
    .barra-acciones { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }

    .hoja-factura {
        background: #fff; border: 1px solid var(--gris-borde); border-radius: 8px;
        padding: 28px 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .encabezado-factura {
        display: flex; justify-content: space-between; align-items: flex-start;
        border-bottom: 2px solid var(--verde); padding-bottom: 14px; margin-bottom: 14px;
    }
    .encabezado-factura h2 { color: var(--verde); margin-bottom: 2px; font-size: 1.1rem; }
    .datos-hotel { font-size: 12.5px; color: #555; line-height: 1.5; }
    .datos-factura-num { text-align: right; }
    .datos-factura-num .num-grande { font-size: 20px; font-weight: 700; color: var(--negro); margin: 2px 0 6px; }

    .leyenda-prueba {
        background: #FFF8E1; border: 1px solid #FFD54F; color: #8D6E00;
        font-size: 11.5px; padding: 8px 12px; border-radius: 6px; margin-bottom: 18px; text-align: center;
    }

    .bloque-dos-col { display: flex; gap: 24px; margin-bottom: 18px; }
    .bloque-dos-col > div { flex: 1; }
    .bloque-dos-col h4 { font-size: 12px; color: #607D8B; text-transform: uppercase; margin-bottom: 6px; }
    .bloque-dos-col p { font-size: 13.5px; margin: 2px 0; }

    table.tabla-desglose { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
    table.tabla-desglose th {
        background: var(--verde-clar); color: #1b5e20; font-size: 12px;
        text-align: left; padding: 7px 10px; border: 1px solid var(--gris-borde);
    }
    table.tabla-desglose td { padding: 7px 10px; font-size: 13px; border: 1px solid var(--gris-borde); }
    table.tabla-desglose td.num { text-align: right; }

    .totales { margin-left: auto; width: 280px; }
    .totales div { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13.5px; }
    .totales .fila-total {
        border-top: 2px solid var(--negro); font-weight: 700; font-size: 16px;
        padding-top: 8px; margin-top: 4px;
    }

    .pie-factura { margin-top: 20px; font-size: 11px; color: #777; text-align: center; }

    .panel-pago {
        margin-top: 20px; background: var(--verde-fondo); border: 1px solid #AED581;
        border-radius: 6px; padding: 14px 16px;
    }
    .panel-pago label { font-size: 13px; font-weight: 600; margin-right: 8px; }
    .panel-pago select { padding: 7px; border-radius: 6px; border: 1px solid #ccc; }

    @media print {
        body { background: #fff; }
        .barra-acciones, .panel-pago, .alerta { display: none !important; }
        .hoja-factura { border: none; box-shadow: none; padding: 0; }
        .contenedor-factura { margin: 0; max-width: 100%; }
    }
</style>
</head>
<body>
<div class="contenedor-factura">
    <div class="barra-acciones">
        <a href="<?= BASE_URL ?>views/facturas.php" class="btn btn-texto">← Volver a Facturas</a>
        <button type="button" onclick="window.print()" class="btn btn-secundario">Imprimir / Guardar PDF</button>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="hoja-factura">
        <div class="leyenda-prueba">
            DOCUMENTO DE PRUEBA — Hostal en régimen simplificado. Sin validez tributaria real (sin CUFE DIAN).
        </div>

        <div class="encabezado-factura">
            <div class="datos-hotel">
                <strong><?= htmlspecialchars(HOTEL_NOMBRE) ?></strong><br>
                <?= htmlspecialchars(HOTEL_DIRECCION) ?><br>
                NIT: <?= htmlspecialchars(HOTEL_NIT) ?>
            </div>
            <div class="datos-factura-num">
                <h2>FACTURA DE VENTA</h2>
                <div class="num-grande">#<?= str_pad((string)$factura['id_factura'], 6, '0', STR_PAD_LEFT) ?></div>
                <span class="badge <?= $badgeEstado[$factura['estado']] ?? 'badge-inactivo' ?>">
                    <?= htmlspecialchars($factura['estado']) ?>
                </span>
            </div>
        </div>

        <div class="bloque-dos-col">
            <div>
                <h4>Facturado a</h4>
                <p><strong><?= htmlspecialchars($factura['huesped']) ?></strong></p>
                <p><?= htmlspecialchars($factura['tipo_documento'] . ' ' . $factura['num_documento']) ?></p>
                <?php if ($factura['tel_huesped']): ?>
                    <p>Tel: <?= htmlspecialchars($factura['tel_huesped']) ?></p>
                <?php endif; ?>
                <?php if ($factura['email_huesped']): ?>
                    <p><?= htmlspecialchars($factura['email_huesped']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h4>Detalle de la estadía</h4>
                <p>Habitación <?= htmlspecialchars($factura['numero_hab']) ?> (<?= htmlspecialchars($factura['tipo_hab']) ?>)</p>
                <p>
                    <?= htmlspecialchars($factura['fecha_entrada']) ?> a <?= htmlspecialchars($factura['fecha_salida']) ?>
                    (<?= (int)$factura['num_noches'] ?> noche<?= (int)$factura['num_noches'] !== 1 ? 's' : '' ?>)
                </p>
                <p>Fecha de emisión: <?= htmlspecialchars($factura['fecha_emision']) ?></p>
                <?php if ($factura['atendido_por']): ?>
                    <p>Atendido por: <?= htmlspecialchars($factura['atendido_por']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table class="tabla-desglose">
            <thead>
                <tr><th>Concepto</th><th style="width:15%">Cant.</th><th style="width:20%" class="num">Valor</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Alojamiento — Habitación <?= htmlspecialchars($factura['numero_hab']) ?> (<?= htmlspecialchars($factura['tipo_hab']) ?>)</td>
                    <td><?= (int)$factura['num_noches'] ?> noche<?= (int)$factura['num_noches'] !== 1 ? 's' : '' ?></td>
                    <td class="num">$<?= number_format($totalHabitacion, 0, ',', '.') ?></td>
                </tr>
                <?php foreach ($cargosExtras as $cargo): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($cargo['tipo']) ?><?= $cargo['descripcion'] ? ' — ' . htmlspecialchars($cargo['descripcion']) : '' ?>
                        </td>
                        <td>1</td>
                        <td class="num">$<?= number_format($cargo['valor'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totales">
            <div><span>Subtotal</span><span>$<?= number_format($factura['subtotal'], 0, ',', '.') ?></span></div>
            <div><span>IVA (<?= number_format($factura['iva_porcentaje'], 0) ?>%)</span><span>$<?= number_format($factura['iva_valor'], 0, ',', '.') ?></span></div>
            <div class="fila-total"><span>Total</span><span>$<?= number_format($factura['total'], 0, ',', '.') ?></span></div>
        </div>

        <p style="font-size:12.5px; margin-top:14px;">
            Método de pago: <strong><?= htmlspecialchars($factura['metodo_pago']) ?></strong>
        </p>

        <div class="pie-factura">
            Factura generada automáticamente por HotelSys al finalizar la estadía — Hotel Plaza Hostal, Yarumal, Antioquia.
        </div>
    </div>

    <?php if ($factura['estado'] === 'Pendiente'): ?>
        <div class="panel-pago">
            <form action="<?= BASE_URL ?>modules/facturacion/factura_pagar.php" method="POST"
                  style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <input type="hidden" name="id_factura" value="<?= (int)$factura['id_factura'] ?>">
                <label for="metodo_pago">Registrar pago recibido:</label>
                <select name="metodo_pago" id="metodo_pago">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia (Bancolombia)</option>
                    <option value="Tarjeta">Tarjeta débito</option>
                    <option value="Nequi">Nequi</option>
                </select>
                <button type="submit" class="btn btn-primario">Marcar como Pagada</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
