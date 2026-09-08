<?php
// modules/inventario/inventario_exportar.php
// Semana 12 Día 3 — Reporte exportable de inventario en CSV.
// Respeta los mismos filtros (busqueda, categoria, estado, criticos) que
// views/inventario.php, para que el reporte coincida con lo que se ve en pantalla.
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin exporta el inventario

$pdo = getConexion();

// ---- Mismos filtros que views/inventario.php ----
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

$sql = "SELECT nombre_item, categoria, unidad_medida, stock_actual, stock_minimo,
               precio_unitario, proveedor, telefono_proveedor, ultima_compra, activo
        FROM inventario
        $whereSql
        ORDER BY (stock_actual <= stock_minimo) DESC, activo DESC, categoria ASC, nombre_item ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Generar el CSV ----
$nombreArchivo = 'inventario_hotelsys_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$salida = fopen('php://output', 'w');

// BOM UTF-8: sin esto, Excel en Windows muestra mal las tildes y la Ñ.
fwrite($salida, "\xEF\xBB\xBF");

fputcsv($salida, [
    'Nombre',
    'Categoría',
    'Unidad de medida',
    'Stock actual',
    'Stock mínimo',
    'Estado de stock',
    'Precio unitario',
    'Proveedor',
    'Teléfono proveedor',
    'Última compra',
    'Estado',
]);

foreach ($inventario as $item) {
    $esCritico = $item['stock_actual'] <= $item['stock_minimo'];
    fputcsv($salida, [
        $item['nombre_item'],
        $item['categoria'],
        $item['unidad_medida'],
        (int) $item['stock_actual'],
        (int) $item['stock_minimo'],
        $esCritico ? 'Crítico' : 'Normal',
        $item['precio_unitario'],
        $item['proveedor'],
        $item['telefono_proveedor'],
        $item['ultima_compra'],
        $item['activo'] == 1 ? 'Activo' : 'Inactivo',
    ]);
}

fclose($salida);
exit;
