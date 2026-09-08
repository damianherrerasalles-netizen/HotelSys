<?php
// views/inventario_kardex.php
// Semana 12 — Historial de movimientos (Kardex) de un insumo de inventario:
// permite registrar una entrada/salida y muestra los últimos movimientos.
$rutaBase = '../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin gestiona el kardex de inventario

$pdo = getConexion();

$idItem = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT id_item, nombre_item, categoria, unidad_medida, stock_actual, stock_minimo
                        FROM inventario WHERE id_item = :id");
$stmt->execute([':id' => $idItem]);
$insumo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$insumo) {
    $_SESSION['inventario_mensaje'] = 'El insumo solicitado no existe.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: inventario.php');
    exit;
}

$stmtMov = $pdo->prepare(
    "SELECT tipo, motivo, cantidad, stock_resultante, usuario, observaciones, fecha_movimiento
     FROM movimientos_inventario
     WHERE id_item = :id
     ORDER BY fecha_movimiento DESC, id_movimiento DESC
     LIMIT 15"
);
$stmtMov->execute([':id' => $idItem]);
$movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

$esCritico = $insumo['stock_actual'] <= $insumo['stock_minimo'];

$flashMensaje = $_SESSION['inventario_mensaje'] ?? null;
$flashTipo = $_SESSION['inventario_mensaje_tipo'] ?? 'exito';
unset($_SESSION['inventario_mensaje'], $_SESSION['inventario_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de <?= htmlspecialchars($insumo['nombre_item']) ?> - HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F5F5F5; margin: 0; }
        .contenedor { max-width: 820px; margin: 24px auto; padding: 0 20px; }
        .volver { text-decoration: none; color: #616161; font-size: 0.85rem; }
        h1 { margin-bottom: 2px; }
        .subtitulo { color: #777; font-size: 0.9rem; margin: 2px 0 16px; }
        .panel {
            background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 20px; margin-bottom: 20px;
        }
        .panel h3 { color: #2E7D32; font-size: 1rem; margin: 0 0 12px; }
        .stock-actual { font-size: 1.3rem; font-weight: bold; }
        .stock-actual.critico { color: #C62828; }
        .stock-actual.normal { color: #2E7D32; }

        .fila-form { display: grid; grid-template-columns: 1fr 1.4fr 0.8fr; gap: 12px; margin-bottom: 12px; }
        .fila-form label { display: block; font-size: 0.82rem; color: #444; margin-bottom: 4px; font-weight: bold; }
        .fila-form select, .fila-form input {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px;
            box-sizing: border-box; font-family: inherit; font-size: 0.88rem;
        }
        .fila-obs { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end; }

        table.kardex { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.kardex th { background: #E8F5E9; color: #2E7D32; text-align: left; padding: 8px; font-size: 0.82rem; }
        table.kardex td { border-bottom: 1px solid #eee; padding: 8px; font-size: 0.82rem; }
        .tipo-entrada { color: #2E7D32; font-weight: bold; }
        .tipo-salida { color: #C62828; font-weight: bold; }

        .alerta { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.88rem; }
        .alerta-error { background: #FFEBEE; color: #C62828; }
        .alerta-exito { background: #E8F5E9; color: #2E7D32; }
    </style>
</head>
<body>
<div class="contenedor">
    <a href="inventario.php" class="volver">&larr; Volver al listado</a>
    <h1><?= htmlspecialchars($insumo['nombre_item']) ?></h1>
    <p class="subtitulo"><?= htmlspecialchars($insumo['categoria']) ?> · Historial de movimientos (Kardex) — Semana 12</p>

    <?php if ($flashMensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>"><?= htmlspecialchars($flashMensaje) ?></div>
    <?php endif; ?>

    <div class="panel">
        <p>
            Stock actual:
            <span class="stock-actual <?= $esCritico ? 'critico' : 'normal' ?>">
                <?= (int) $insumo['stock_actual'] ?> <?= htmlspecialchars($insumo['unidad_medida']) ?>
            </span>
            (mínimo: <?= (int) $insumo['stock_minimo'] ?>)
        </p>

        <h3>Registrar movimiento</h3>
        <form method="POST" action="<?= BASE_URL ?>modules/inventario/movimiento_registrar.php">
            <input type="hidden" name="id_item" value="<?= (int) $insumo['id_item'] ?>">
            <div class="fila-form">
                <div>
                    <label>Tipo</label>
                    <select name="tipo" id="tipoMovimiento" onchange="actualizarMotivos()">
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                    </select>
                </div>
                <div>
                    <label>Motivo</label>
                    <select name="motivo" id="motivoMovimiento"></select>
                </div>
                <div>
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" min="1" step="1" required>
                </div>
            </div>
            <div class="fila-obs">
                <div>
                    <label>Observaciones (opcional)</label>
                    <input type="text" name="observaciones" maxlength="255">
                </div>
                <div>
                    <button type="submit" class="btn btn-primario">Registrar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3>Últimos movimientos</h3>
        <table class="kardex">
            <tr>
                <th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Cantidad</th><th>Stock resultante</th><th>Usuario</th>
            </tr>
            <?php if (count($movimientos) === 0): ?>
                <tr><td colspan="6">Aún no hay movimientos registrados para este insumo.</td></tr>
            <?php endif; ?>
            <?php foreach ($movimientos as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['fecha_movimiento']) ?></td>
                    <td class="tipo-<?= htmlspecialchars($m['tipo']) ?>">
                        <?= $m['tipo'] === 'entrada' ? 'Entrada' : 'Salida' ?>
                    </td>
                    <td><?= htmlspecialchars($m['motivo']) ?></td>
                    <td><?= (int) $m['cantidad'] ?></td>
                    <td><?= (int) $m['stock_resultante'] ?></td>
                    <td><?= htmlspecialchars($m['usuario']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
    const motivosEntrada = ['Compra a proveedor', 'Ajuste de inventario'];
    const motivosSalida = ['Consumo operativo', 'Pérdida o daño', 'Ajuste de inventario'];

    function actualizarMotivos() {
        const tipo = document.getElementById('tipoMovimiento').value;
        const select = document.getElementById('motivoMovimiento');
        const opciones = tipo === 'entrada' ? motivosEntrada : motivosSalida;
        select.innerHTML = '';
        opciones.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            select.appendChild(opt);
        });
    }
    actualizarMotivos();
</script>
</body>
</html>
