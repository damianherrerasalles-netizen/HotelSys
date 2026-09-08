<?php
// views/inventario_form.php
// Semana 11 — Módulo de Inventario
$rutaBase = '../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin puede crear/editar inventario

$pdo = getConexion();

$esEdicion = isset($_GET['id']) && $_GET['id'] !== '';
$item = [
    'id_item' => '',
    'nombre_item' => '',
    'categoria' => 'Otro',
    'unidad_medida' => 'unidad',
    'stock_actual' => '0',
    'stock_minimo' => '5',
    'precio_unitario' => '',
    'proveedor' => '',
    'telefono_proveedor' => '',
    'ultima_compra' => '',
    'observaciones' => '',
];

if ($esEdicion) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT id_item, nombre_item, categoria, unidad_medida, stock_actual,
                                   stock_minimo, precio_unitario, proveedor, telefono_proveedor,
                                   ultima_compra, observaciones
                            FROM inventario WHERE id_item = :id");
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        $_SESSION['inventario_mensaje'] = 'El insumo solicitado no existe.';
        $_SESSION['inventario_mensaje_tipo'] = 'error';
        header('Location: inventario.php');
        exit;
    }

    $item = $registro;
}

// Flash message (por si el procesador redirige aquí con un error de validación)
$flashMensaje = $_SESSION['inventario_mensaje'] ?? null;
$flashTipo = $_SESSION['inventario_mensaje_tipo'] ?? 'exito';
unset($_SESSION['inventario_mensaje'], $_SESSION['inventario_mensaje_tipo']);

$categoriasDisponibles = ['Lencería', 'Aseo', 'Amenidades', 'Bebidas', 'Mantenimiento', 'Oficina', 'Alimentos', 'Otro'];
$unidadesSugeridas = ['unidad', 'litro', 'rollo', 'paquete', 'kit', 'caja'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $esEdicion ? 'Editar' : 'Nuevo' ?> Insumo - HotelSys</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F5F5F5; margin: 0; }
        .contenedor-form {
            max-width: 640px; margin: 30px auto; background: #fff;
            border-radius: 10px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        h1 { color: #2E7D32; font-size: 1.3rem; margin-bottom: 4px; }
        .subtitulo { color: #777; font-size: 0.85rem; margin-bottom: 20px; }
        .fila { margin-bottom: 16px; }
        .fila label {
            display: block; font-size: 0.85rem; color: #444; margin-bottom: 4px; font-weight: bold;
        }
        .fila input, .fila select, .fila textarea {
            width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 6px;
            box-sizing: border-box; font-size: 0.9rem; font-family: inherit;
        }
        .dos-columnas { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .seccion-titulo {
            color: #2E7D32; font-size: 0.95rem; margin: 24px 0 10px;
            border-bottom: 1px solid #E8F5E9; padding-bottom: 6px;
        }
        .botones { margin-top: 24px; display: flex; gap: 10px; }
        .btn {
            padding: 10px 18px; border-radius: 6px; text-decoration: none;
            font-size: 0.9rem; border: none; cursor: pointer;
        }
        .btn-guardar { background: #2E7D32; color: #fff; }
        .btn-cancelar { background: #eee; color: #444; }
        .alerta {
            padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.88rem;
        }
        .alerta-error { background: #FFEBEE; color: #C62828; }
        .alerta-exito { background: #E8F5E9; color: #2E7D32; }
        .obligatorio { color: #C62828; }
        .ayuda { font-size: 0.78rem; color: #888; margin-top: 3px; }
    </style>
</head>
<body>
<div class="contenedor-form">
    <h1><?= $esEdicion ? 'Editar Insumo' : 'Nuevo Insumo' ?></h1>
    <p class="subtitulo">Hotel Plaza Hostal — Módulo de Inventario</p>

    <?php if ($flashMensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>">
            <?= htmlspecialchars($flashMensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>modules/inventario/inventario_procesar.php">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id_item" value="<?= (int) $item['id_item'] ?>">
        <?php endif; ?>

        <div class="seccion-titulo">Identificación del insumo</div>
        <div class="fila">
            <label>Nombre del insumo <span class="obligatorio">*</span></label>
            <input type="text" name="nombre_item" required maxlength="100"
                   value="<?= htmlspecialchars($item['nombre_item']) ?>">
        </div>

        <div class="dos-columnas">
            <div class="fila">
                <label>Categoría <span class="obligatorio">*</span></label>
                <select name="categoria" required>
                    <?php foreach ($categoriasDisponibles as $c): ?>
                        <option value="<?= $c ?>" <?= $item['categoria'] === $c ? 'selected' : '' ?>>
                            <?= $c ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fila">
                <label>Unidad de medida <span class="obligatorio">*</span></label>
                <input type="text" name="unidad_medida" list="unidades" required maxlength="20"
                       value="<?= htmlspecialchars($item['unidad_medida']) ?>">
                <datalist id="unidades">
                    <?php foreach ($unidadesSugeridas as $u): ?>
                        <option value="<?= $u ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div class="seccion-titulo">Control de stock</div>
        <div class="dos-columnas">
            <div class="fila">
                <?php if ($esEdicion): ?>
                    <label>Stock actual</label>
                    <input type="number" value="<?= htmlspecialchars($item['stock_actual']) ?>" disabled>
                    <p class="ayuda">
                        El stock ya no se edita aquí — usa
                        <a href="inventario_kardex.php?id=<?= (int) $item['id_item'] ?>">Ver historial / Registrar movimiento</a>
                        para sumar o restar unidades con trazabilidad (Semana 12).
                    </p>
                <?php else: ?>
                    <label>Stock inicial <span class="obligatorio">*</span></label>
                    <input type="number" name="stock_actual" required min="0" step="1"
                           value="<?= htmlspecialchars($item['stock_actual']) ?>">
                    <p class="ayuda">A partir de este valor, los cambios de stock se registran como movimientos (entradas/salidas).</p>
                <?php endif; ?>
            </div>
            <div class="fila">
                <label>Stock mínimo (umbral de alerta) <span class="obligatorio">*</span></label>
                <input type="number" name="stock_minimo" required min="0" step="1"
                       value="<?= htmlspecialchars($item['stock_minimo']) ?>">
                <p class="ayuda">Cuando el stock actual llegue a este valor o menos, el insumo aparecerá como crítico.</p>
            </div>
        </div>

        <div class="fila">
            <label>Precio unitario (COP)</label>
            <input type="number" name="precio_unitario" min="0" step="0.01"
                   value="<?= htmlspecialchars($item['precio_unitario']) ?>">
        </div>

        <div class="seccion-titulo">Proveedor</div>
        <div class="dos-columnas">
            <div class="fila">
                <label>Nombre del proveedor</label>
                <input type="text" name="proveedor" maxlength="100"
                       value="<?= htmlspecialchars($item['proveedor']) ?>">
            </div>
            <div class="fila">
                <label>Teléfono del proveedor</label>
                <input type="text" name="telefono_proveedor" maxlength="15"
                       value="<?= htmlspecialchars($item['telefono_proveedor']) ?>">
            </div>
        </div>

        <div class="fila">
            <label>Fecha de última compra</label>
            <input type="date" name="ultima_compra"
                   value="<?= htmlspecialchars($item['ultima_compra']) ?>">
        </div>

        <div class="fila">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="3"><?= htmlspecialchars($item['observaciones']) ?></textarea>
        </div>

        <div class="botones">
            <button type="submit" class="btn btn-guardar">
                <?= $esEdicion ? 'Guardar cambios' : 'Registrar insumo' ?>
            </button>
            <a href="inventario.php" class="btn btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
