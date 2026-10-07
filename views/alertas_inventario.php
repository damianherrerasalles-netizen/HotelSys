<?php
// views/alertas_inventario.php
// Semana 12 Día 2 — Alertas automáticas de stock crítico de inventario.
require_once '../config/db.php';
require_once '../includes/check_auth.php';
requerirAdmin(); // Solo admin gestiona alertas de inventario

$pdo = getConexion();

// --- Ajuste Semana 20 (hallazgo #6, Semana 19 Día 4) ---
// Antes, un insumo podía llegar a stock crítico sin pasar por un movimiento
// de Kardex registrado (p. ej. carga inicial de stock) y nunca generaba
// alerta, quedando desincronizado del KPI "Insumos en stock crítico" del
// Dashboard. Se reconcilia aquí, cada vez que un admin visita esta vista:
// genera una alerta 'abierta' (mismo INSERT que ya usa
// modules/inventario/movimiento_registrar.php) para todo insumo activo en
// stock crítico que todavía no tenga una — el NOT EXISTS lo hace idempotente.
$pdo->exec(
    "INSERT INTO alertas_inventario (id_item, stock_al_generar, stock_minimo_al_generar, estado)
     SELECT i.id_item, i.stock_actual, i.stock_minimo, 'abierta'
     FROM inventario i
     WHERE i.activo = 1
       AND i.stock_actual <= i.stock_minimo
       AND NOT EXISTS (
           SELECT 1 FROM alertas_inventario a
           WHERE a.id_item = i.id_item AND a.estado = 'abierta'
       )"
);

$stmtAbiertas = $pdo->query(
    "SELECT a.id_alerta, a.stock_al_generar, a.stock_minimo_al_generar, a.fecha_generada,
            i.id_item, i.nombre_item, i.categoria, i.unidad_medida
     FROM alertas_inventario a
     JOIN inventario i ON i.id_item = a.id_item
     WHERE a.estado = 'abierta'
     ORDER BY a.fecha_generada DESC"
);
$alertasAbiertas = $stmtAbiertas->fetchAll(PDO::FETCH_ASSOC);

$stmtHistorial = $pdo->query(
    "SELECT a.id_alerta, a.estado, a.stock_al_generar, a.stock_minimo_al_generar,
            a.fecha_generada, a.fecha_cierre, a.atendida_por,
            i.nombre_item, i.categoria, i.unidad_medida
     FROM alertas_inventario a
     JOIN inventario i ON i.id_item = a.id_item
     WHERE a.estado != 'abierta'
     ORDER BY a.fecha_cierre DESC
     LIMIT 20"
);
$historialAlertas = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

$flashMensaje = $_SESSION['inventario_mensaje'] ?? null;
$flashTipo = $_SESSION['inventario_mensaje_tipo'] ?? 'exito';
unset($_SESSION['inventario_mensaje'], $_SESSION['inventario_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas de Inventario - HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F5F5F5; margin: 0; }
        .contenedor { max-width: 980px; margin: 24px auto; padding: 0 20px; }
        .volver { text-decoration: none; color: #616161; font-size: 0.85rem; }
        h1 { margin-bottom: 2px; }
        .subtitulo { color: #777; font-size: 0.9rem; margin: 2px 0 16px; }
        .panel {
            background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 20px; margin-bottom: 20px;
        }
        .panel h3 { color: #2E7D32; font-size: 1rem; margin: 0 0 12px; }
        .panel.abiertas h3 { color: #C62828; }

        table.alertas { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.alertas th { background: #E8F5E9; color: #2E7D32; text-align: left; padding: 8px; font-size: 0.82rem; }
        .panel.abiertas table.alertas th { background: #FFEBEE; color: #C62828; }
        table.alertas td { border-bottom: 1px solid #eee; padding: 8px; font-size: 0.82rem; vertical-align: middle; }

        .badge-atendida { background: #C8E6C9; color: #2E7D32; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 10px; }
        .badge-auto { background: #E3F2FD; color: #1976D2; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 10px; }

        .btn-atender {
            background: #2E7D32; color: #fff; border: none; padding: 6px 12px;
            border-radius: 6px; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-block;
        }
        .sin-datos { color: #888; font-size: 0.85rem; padding: 10px 0; }

        .alerta { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.88rem; }
        .alerta-error { background: #FFEBEE; color: #C62828; }
        .alerta-exito { background: #E8F5E9; color: #2E7D32; }
    </style>
</head>
<body>
<div class="contenedor">
    <a href="inventario.php" class="volver">&larr; Volver a Inventario</a>
    <h1>Alertas de Inventario</h1>
    <p class="subtitulo">Hotel Plaza Hostal — Alertas automáticas de stock crítico (Semana 12)</p>

    <?php if ($flashMensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>"><?= htmlspecialchars($flashMensaje) ?></div>
    <?php endif; ?>

    <div class="panel abiertas">
        <h3>Alertas abiertas (<?= count($alertasAbiertas) ?>)</h3>
        <table class="alertas">
            <tr>
                <th>Insumo</th><th>Categoría</th><th>Stock al generar</th><th>Mínimo</th><th>Generada</th><th></th>
            </tr>
            <?php if (count($alertasAbiertas) === 0): ?>
                <tr><td colspan="6" class="sin-datos">No hay alertas abiertas actualmente.</td></tr>
            <?php endif; ?>
            <?php foreach ($alertasAbiertas as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nombre_item']) ?></td>
                    <td><?= htmlspecialchars($a['categoria']) ?></td>
                    <td><?= (int) $a['stock_al_generar'] ?> <?= htmlspecialchars($a['unidad_medida']) ?></td>
                    <td><?= (int) $a['stock_minimo_al_generar'] ?></td>
                    <td><?= htmlspecialchars($a['fecha_generada']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>modules/inventario/alerta_atender.php?id=<?= (int) $a['id_alerta'] ?>"
                           class="btn-atender"
                           onclick="return confirm('¿Marcar esta alerta como atendida?');">Marcar atendida</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="panel">
        <h3>Historial reciente</h3>
        <table class="alertas">
            <tr>
                <th>Insumo</th><th>Estado</th><th>Stock al generar</th><th>Generada</th><th>Cerrada</th><th>Atendida por</th>
            </tr>
            <?php if (count($historialAlertas) === 0): ?>
                <tr><td colspan="6" class="sin-datos">Aún no hay alertas cerradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($historialAlertas as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['nombre_item']) ?></td>
                    <td>
                        <?php if ($h['estado'] === 'atendida'): ?>
                            <span class="badge-atendida">Atendida</span>
                        <?php else: ?>
                            <span class="badge-auto">Auto-resuelta</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $h['stock_al_generar'] ?> <?= htmlspecialchars($h['unidad_medida']) ?></td>
                    <td><?= htmlspecialchars($h['fecha_generada']) ?></td>
                    <td><?= htmlspecialchars($h['fecha_cierre']) ?></td>
                    <td><?= htmlspecialchars($h['atendida_por']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
