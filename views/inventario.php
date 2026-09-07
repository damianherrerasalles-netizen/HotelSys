<?php
// views/inventario.php — Gestión de Inventario con alertas de stock crítico
// Semana 11 — HotelSys
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

// ---- Filtros ----
$busqueda = trim($_GET['busqueda'] ?? '');
$filtroCategoria = trim($_GET['categoria'] ?? '');
$filtroEstado = $_GET['estado'] ?? 'activos'; // activos | inactivos | todos
$soloCriticos = isset($_GET['criticos']) && $_GET['criticos'] === '1';

$condiciones = [];
$params = [];

if ($busqueda !== '') {
    $condiciones[] = "(nombre_item LIKE :busqueda OR proveedor LIKE :busqueda2)";
    $params[':busqueda'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
}

if ($filtroCategoria !== '') {
    $condiciones[] = "categoria = :categoria";
    $params[':categoria'] = $filtroCategoria;
}

if ($filtroEstado === 'activos') {
    $condiciones[] = "activo = 1";
} elseif ($filtroEstado === 'inactivos') {
    $condiciones[] = "activo = 0";
}
// 'todos' no agrega condición

if ($soloCriticos) {
    $condiciones[] = "stock_actual <= stock_minimo";
}

$whereSql = count($condiciones) > 0 ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$sql = "SELECT id_item, nombre_item, categoria, unidad_medida, stock_actual, stock_minimo,
               precio_unitario, proveedor, telefono_proveedor, ultima_compra, activo
        FROM inventario
        $whereSql
        ORDER BY (stock_actual <= stock_minimo) DESC, activo DESC, categoria ASC, nombre_item ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conteo de alertas activas para el aviso superior (misma regla que v_stock_critico)
$totalCriticos = (int) $pdo->query(
    "SELECT COUNT(*) FROM inventario WHERE stock_actual <= stock_minimo AND activo = 1"
)->fetchColumn();

// Categorías disponibles para el filtro (del ENUM real, incluye Bebidas — Semana 11)
$categoriasDisponibles = ['Lencería', 'Aseo', 'Amenidades', 'Bebidas', 'Mantenimiento', 'Oficina', 'Alimentos', 'Otro'];

// Colores por categoría para las tarjetas/badges
$colorCategoria = [
    'Lencería'      => '#1976D2',
    'Aseo'          => '#0097A7',
    'Amenidades'    => '#7B1FA2',
    'Bebidas'       => '#EF6C00',
    'Mantenimiento' => '#5D4037',
    'Oficina'       => '#455A64',
    'Alimentos'     => '#C0631A',
    'Otro'          => '#616161',
];

$flashMensaje = $_SESSION['inventario_mensaje'] ?? null;
$flashTipo = $_SESSION['inventario_mensaje_tipo'] ?? 'exito';
unset($_SESSION['inventario_mensaje'], $_SESSION['inventario_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; }
        .contenedor { max-width: 1100px; margin: 24px auto; padding: 0 20px; }
        .subtitulo { color: #777; font-size: 0.9rem; margin: 2px 0 16px; }
        .barra-superior { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .btn { padding: 9px 16px; border-radius: 6px; text-decoration: none; font-size: 0.88rem; border: none; cursor: pointer; display: inline-block; }
        .btn-primario { background: #2E7D32; color: #fff; }
        .btn-secundario { background: #E3F2FD; color: #1976D2; }
        .btn-texto { background: none; color: #616161; }
        .btn-peligro { background: #FDECEA; color: #C62828; }
        .btn-exito { background: #E8F5E9; color: #2E7D32; }

        .aviso-critico {
            background: #FDECEA; border: 1px solid #C62828; color: #B71C1C;
            padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.9rem;
        }
        .aviso-critico strong { font-size: 1rem; }

        .filtros-inventario { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .filtros-inventario input, .filtros-inventario select { padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .filtro-check { display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: #444; }

        .conteo-resultados { font-size: 0.85rem; color: #666; margin: 14px 0 8px; }

        .inventario-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .tarjeta-item {
            background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 16px; border-left: 5px solid #616161;
        }
        .tarjeta-item.critico { border-left-color: #C62828; background: #FFFBFB; }
        .tarjeta-item.inactivo { opacity: 0.55; }

        .badge-categoria {
            display: inline-block; color: #fff; font-size: 12px; padding: 3px 10px;
            border-radius: 12px; margin-bottom: 8px;
        }
        .badge-stock {
            display: inline-block; font-size: 11px; font-weight: bold; padding: 2px 8px;
            border-radius: 10px; margin-left: 6px;
        }
        .badge-stock.critico { background: #C62828; color: #fff; }
        .badge-stock.normal { background: #C8E6C9; color: #2E7D32; }
        .badge-inactivo { background: #9E9E9E; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-left: 6px; }

        .info-linea { font-size: 13.5px; color: #444; margin: 3px 0; }
        .stock-linea { font-size: 14px; font-weight: bold; margin: 6px 0 2px; }
        .stock-linea.critico { color: #C62828; }
        .stock-linea.normal { color: #2E7D32; }

        .acciones-item { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
        .acciones-item a { font-size: 12.5px; text-decoration: none; padding: 5px 10px; border-radius: 6px; }
    </style>
</head>
<body>
<div class="contenedor">
    <h1>Gestión de Inventario</h1>
    <p class="subtitulo">Hotel Plaza Hostal — Insumos y alertas de stock crítico</p>

    <?php if ($flashMensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>">
            <?= htmlspecialchars($flashMensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($totalCriticos > 0): ?>
        <div class="aviso-critico">
            <strong>&#9888; <?= $totalCriticos ?> insumo(s)</strong> en stock crítico (por debajo del mínimo).
            <a href="?criticos=1" style="color:#B71C1C; font-weight:bold;">Ver solo críticos &rarr;</a>
        </div>
    <?php endif; ?>

    <div class="barra-superior">
        <?php if (esAdmin()): ?>
            <a href="inventario_form.php" class="btn btn-primario">+ Nuevo Insumo</a>
        <?php endif; ?>
    </div>

    <form method="GET" class="filtros-inventario">
        <input type="text" name="busqueda" placeholder="Buscar por nombre o proveedor..."
               value="<?= htmlspecialchars($busqueda) ?>">

        <select name="categoria">
            <option value="">Todas las categorías</option>
            <?php foreach ($categoriasDisponibles as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $filtroCategoria === $c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="estado">
            <option value="activos" <?= $filtroEstado === 'activos' ? 'selected' : '' ?>>Activos</option>
            <option value="inactivos" <?= $filtroEstado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
            <option value="todos" <?= $filtroEstado === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>

        <label class="filtro-check">
            <input type="checkbox" name="criticos" value="1" <?= $soloCriticos ? 'checked' : '' ?>>
            Solo stock crítico
        </label>

        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <a href="inventario.php" class="btn btn-texto">Limpiar</a>
    </form>

    <p class="conteo-resultados"><?= count($inventario) ?> insumo(s) encontrado(s)</p>

    <div class="inventario-grid">
        <?php foreach ($inventario as $item): ?>
            <?php
                $color = $colorCategoria[$item['categoria']] ?? '#616161';
                $esCritico = $item['stock_actual'] <= $item['stock_minimo'];
            ?>
            <div class="tarjeta-item <?= $esCritico ? 'critico' : '' ?> <?= $item['activo'] == 0 ? 'inactivo' : '' ?>">

                <span class="badge-categoria" style="background: <?= $color ?>;">
                    <?= htmlspecialchars($item['categoria']) ?>
                </span>
                <?php if ($esCritico): ?>
                    <span class="badge-stock critico">CRÍTICO</span>
                <?php else: ?>
                    <span class="badge-stock normal">Normal</span>
                <?php endif; ?>
                <?php if ($item['activo'] == 0): ?>
                    <span class="badge-inactivo">Inactivo</span>
                <?php endif; ?>

                <h3><?= htmlspecialchars($item['nombre_item']) ?></h3>

                <p class="stock-linea <?= $esCritico ? 'critico' : 'normal' ?>">
                    <?= (int) $item['stock_actual'] ?> / <?= (int) $item['stock_minimo'] ?> <?= htmlspecialchars($item['unidad_medida']) ?>
                    <?php if ($esCritico): ?>
                        (faltan <?= $item['stock_minimo'] - $item['stock_actual'] ?>)
                    <?php endif; ?>
                </p>

                <?php if ($item['precio_unitario'] > 0): ?>
                    <p class="info-linea">Precio unitario: $<?= number_format($item['precio_unitario'], 0, ',', '.') ?></p>
                <?php endif; ?>
                <?php if ($item['proveedor']): ?>
                    <p class="info-linea">Proveedor: <?= htmlspecialchars($item['proveedor']) ?></p>
                <?php endif; ?>
                <?php if ($item['ultima_compra']): ?>
                    <p class="info-linea">Última compra: <?= htmlspecialchars($item['ultima_compra']) ?></p>
                <?php endif; ?>

                <?php if (esAdmin()): ?>
                    <div class="acciones-item">
                        <a href="inventario_form.php?id=<?= $item['id_item'] ?>" class="btn-secundario">Editar</a>
                        <?php if ($item['activo'] == 1): ?>
                            <a href="<?= BASE_URL ?>modules/inventario/inventario_estado.php?id=<?= $item['id_item'] ?>&accion=desactivar"
                               class="btn-peligro"
                               onclick="return confirm('¿Desactivar este insumo?');">Desactivar</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>modules/inventario/inventario_estado.php?id=<?= $item['id_item'] ?>&accion=activar"
                               class="btn-exito">Reactivar</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (count($inventario) === 0): ?>
            <p>No se encontraron insumos con los filtros aplicados.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
