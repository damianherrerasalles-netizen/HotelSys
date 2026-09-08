<?php
// views/tareas_personal.php
// Semana 13 Día 4 — Tareas diarias del personal (Widget 3 del Dashboard ejecutivo)
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

// Fecha a consultar — por defecto, hoy
$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

// Colaboradores activos, para el formulario de asignación
$stmtPersonal = $pdo->query(
    "SELECT id_personal, nombres, apellidos FROM personal WHERE activo = 1 ORDER BY nombres ASC"
);
$colaboradores = $stmtPersonal->fetchAll(PDO::FETCH_ASSOC);

// Tareas del día seleccionado
$stmtTareas = $pdo->prepare(
    "SELECT t.id_tarea, t.descripcion, t.completada, t.fecha_completada,
            p.nombres, p.apellidos
     FROM tareas_personal t
     JOIN personal p ON p.id_personal = t.id_personal
     WHERE t.fecha = :fecha
     ORDER BY t.completada ASC, t.id_tarea ASC"
);
$stmtTareas->execute([':fecha' => $fecha]);
$tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

$totalTareas = count($tareas);
$totalCompletadas = count(array_filter($tareas, fn($t) => (int) $t['completada'] === 1));

$flashMensaje = $_SESSION['tareas_mensaje'] ?? null;
$flashTipo = $_SESSION['tareas_mensaje_tipo'] ?? 'exito';
unset($_SESSION['tareas_mensaje'], $_SESSION['tareas_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas del Personal - HotelSys</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f4f6f5; color: #222; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .header h1 { color: #2E7D32; font-size: 24px; }
        .subtitulo { color: #777; font-size: 13px; margin: -14px 0 20px; }
        .btn-volver {
            background-color: #2E7D32; color: #fff; text-decoration: none;
            padding: 8px 16px; border-radius: 6px; font-size: 13px;
        }
        .btn-volver:hover { background-color: #256428; }
        .alerta { padding: 10px 14px; border-radius: 6px; margin-bottom: 18px; font-size: 13px; }
        .alerta-error { background: #FFEBEE; color: #C62828; }
        .alerta-exito { background: #E8F5E9; color: #2E7D32; }
        .panel {
            background: #fff; border-radius: 10px; box-shadow: 0 1px 5px rgba(0,0,0,0.08);
            padding: 20px; margin-bottom: 20px;
        }
        .panel h3 { color: #2E7D32; font-size: 15px; margin-bottom: 14px; }
        .fila-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .campo { display: flex; flex-direction: column; gap: 4px; }
        .campo label { font-size: 12px; color: #555; font-weight: bold; }
        .campo input[type="text"], .campo input[type="date"], .campo select {
            padding: 8px 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 13px;
        }
        .campo.descripcion { flex: 1; min-width: 220px; }
        .btn-agregar {
            background-color: #2E7D32; color: #fff; border: none; padding: 9px 20px;
            border-radius: 6px; cursor: pointer; font-size: 13px;
        }
        .btn-agregar:hover { background-color: #256428; }
        .selector-fecha { display: flex; gap: 10px; align-items: center; margin-bottom: 4px; }
        .selector-fecha input[type="date"] { padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; }
        .resumen { font-size: 13px; color: #555; margin-bottom: 12px; }
        .resumen strong { color: #2E7D32; }
        table.tareas { width: 100%; border-collapse: collapse; }
        table.tareas th { background: #E8F5E9; color: #2E7D32; text-align: left; padding: 8px; font-size: 12px; }
        table.tareas td { border-bottom: 1px solid #eee; padding: 8px; font-size: 13px; vertical-align: middle; }
        .tarea-completada .desc { text-decoration: line-through; color: #999; }
        .badge-pendiente { background: #FFF3E0; color: #E65100; font-size: 11px; font-weight: bold; padding: 2px 9px; border-radius: 10px; }
        .badge-completada { background: #C8E6C9; color: #2E7D32; font-size: 11px; font-weight: bold; padding: 2px 9px; border-radius: 10px; }
        .btn-toggle {
            border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;
            text-decoration: none; display: inline-block;
        }
        .btn-completar { background: #2E7D32; color: #fff; }
        .btn-completar:hover { background: #256428; }
        .btn-reabrir { background: #eee; color: #555; }
        .btn-reabrir:hover { background: #ddd; }
        .sin-datos { color: #888; font-size: 13px; padding: 14px 0; }
    </style>
</head>
<body>

<div class="header">
    <h1>📋 Tareas del Personal - HotelSys</h1>
    <a href="dashboard.php" class="btn-volver">← Dashboard</a>
</div>
<p class="subtitulo">Hotel Plaza Hostal — Widget 3 del Dashboard ejecutivo (Semana 13)</p>

<?php if ($flashMensaje): ?>
    <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>"><?= htmlspecialchars($flashMensaje) ?></div>
<?php endif; ?>

<div class="panel">
    <h3>Registrar nueva tarea</h3>
    <form method="post" action="<?= BASE_URL ?>modules/personal/tarea_procesar.php" class="fila-form">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <div class="campo descripcion">
            <label>Descripción</label>
            <input type="text" name="descripcion" maxlength="255" placeholder="Ej. Limpiar habitaciones piso 2" required>
        </div>
        <div class="campo">
            <label>Asignar a</label>
            <select name="id_personal" required>
                <option value="">Seleccione...</option>
                <?php foreach ($colaboradores as $c): ?>
                    <option value="<?= $c['id_personal'] ?>">
                        <?= htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <button type="submit" class="btn-agregar">+ Agregar tarea</button>
        </div>
    </form>
</div>

<div class="panel">
    <form method="get" action="tareas_personal.php" class="selector-fecha">
        <label>Ver tareas del:</label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" onchange="this.form.submit()">
    </form>
    <p class="resumen">
        <?php if ($totalTareas > 0): ?>
            <strong><?= $totalCompletadas ?> de <?= $totalTareas ?></strong> tareas completadas
            (<?= round($totalCompletadas / $totalTareas * 100) ?>%)
        <?php else: ?>
            No hay tareas registradas para esta fecha.
        <?php endif; ?>
    </p>
    <table class="tareas">
        <tr>
            <th>Tarea</th><th>Asignada a</th><th>Estado</th><th></th>
        </tr>
        <?php if ($totalTareas === 0): ?>
            <tr><td colspan="4" class="sin-datos">Aún no hay tareas para esta fecha.</td></tr>
        <?php endif; ?>
        <?php foreach ($tareas as $t): ?>
            <tr class="<?= $t['completada'] ? 'tarea-completada' : '' ?>">
                <td class="desc"><?= htmlspecialchars($t['descripcion']) ?></td>
                <td><?= htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']) ?></td>
                <td>
                    <?php if ($t['completada']): ?>
                        <span class="badge-completada">Completada</span>
                    <?php else: ?>
                        <span class="badge-pendiente">Pendiente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($t['completada']): ?>
                        <a href="<?= BASE_URL ?>modules/personal/tarea_estado.php?id=<?= $t['id_tarea'] ?>&accion=reabrir&fecha=<?= htmlspecialchars($fecha) ?>"
                           class="btn-toggle btn-reabrir">Reabrir</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>modules/personal/tarea_estado.php?id=<?= $t['id_tarea'] ?>&accion=completar&fecha=<?= htmlspecialchars($fecha) ?>"
                           class="btn-toggle btn-completar">Marcar completada</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
