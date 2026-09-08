<?php
// modules/inventario/inventario_procesar.php
// Semana 11 — Módulo de Inventario
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
require_once $rutaBase . 'includes/validaciones_inventario.php'; // Semana 12 Día 4
requerirAdmin(); // Solo admin puede crear/editar/desactivar inventario

$pdo = getConexion();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/inventario.php');
    exit;
}

// ---- Determinar modo: alta o edición ----
$esEdicion = isset($_POST['id_item']) && $_POST['id_item'] !== '';
$idItem = $esEdicion ? (int) $_POST['id_item'] : null;

// ---- Recoger y limpiar datos del formulario ----
$nombreItem = trim($_POST['nombre_item'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$unidadMedida = trim($_POST['unidad_medida'] ?? '');
$stockActual = trim($_POST['stock_actual'] ?? '');
$stockMinimo = trim($_POST['stock_minimo'] ?? '');
$precioUnitario = trim($_POST['precio_unitario'] ?? '');
$proveedor = trim($_POST['proveedor'] ?? '');
$telefonoProveedor = trim($_POST['telefono_proveedor'] ?? '');
$ultimaCompra = trim($_POST['ultima_compra'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// URL de retorno en caso de error (mantiene el ?id=X si es edición)
$urlFormulario = BASE_URL . 'views/inventario_form.php' . ($esEdicion ? '?id=' . $idItem : '');

// ---- Validación de campos obligatorios ----
// Semana 12: el stock_actual ya no se edita desde este formulario cuando es
// una edición — solo se pide como "stock inicial" al dar de alta un insumo
// nuevo. Los cambios posteriores de stock quedan a cargo del Kardex
// (movimientos_inventario), ver inventario_kardex.php.
$camposObligatorios = [
    'nombre_item' => $nombreItem,
    'categoria' => $categoria,
    'unidad_medida' => $unidadMedida,
    'stock_minimo' => $stockMinimo,
];
if (!$esEdicion) {
    $camposObligatorios['stock_actual'] = $stockActual;
}

foreach ($camposObligatorios as $campo => $valor) {
    if ($valor === '') {
        $_SESSION['inventario_mensaje'] = 'El campo "' . $campo . '" es obligatorio.';
        $_SESSION['inventario_mensaje_tipo'] = 'error';
        header('Location: ' . $urlFormulario);
        exit;
    }
}

// Validación de nombre (longitud) — la obligatoriedad ya se verificó arriba
$errorNombre = validarNombreInsumo($nombreItem);
if ($errorNombre !== null) {
    $_SESSION['inventario_mensaje'] = $errorNombre;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// Validación de categoría contra valores permitidos (defensa adicional, por si
// alguien manipula el <select> del formulario o envía el POST directamente)
$errorCategoria = validarCategoriaInventario($categoria);
if ($errorCategoria !== null) {
    $_SESSION['inventario_mensaje'] = $errorCategoria;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// Validación numérica de stock (no negativos)
$errorStockMinimo = validarEnteroNoNegativo($stockMinimo);
if ($errorStockMinimo !== null) {
    $_SESSION['inventario_mensaje'] = 'Stock mínimo: ' . $errorStockMinimo;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}
$stockMinimo = (int) $stockMinimo;

if (!$esEdicion) {
    $errorStockActual = validarEnteroNoNegativo($stockActual);
    if ($errorStockActual !== null) {
        $_SESSION['inventario_mensaje'] = 'Stock inicial: ' . $errorStockActual;
        $_SESSION['inventario_mensaje_tipo'] = 'error';
        header('Location: ' . $urlFormulario);
        exit;
    }
    $stockActual = (int) $stockActual;
}

// Precio unitario: opcional, por defecto 0.00
$errorPrecio = validarNumeroNoNegativo($precioUnitario);
if ($errorPrecio !== null) {
    $_SESSION['inventario_mensaje'] = 'Precio unitario: ' . $errorPrecio;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}
$precioUnitario = $precioUnitario === '' ? 0.00 : (float) $precioUnitario;

// Teléfono del proveedor: opcional, solo dígitos si se envía
$errorTelefono = validarTelefonoProveedor($telefonoProveedor);
if ($errorTelefono !== null) {
    $_SESSION['inventario_mensaje'] = $errorTelefono;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// Observaciones: opcional, respeta el límite de la columna VARCHAR(255)
$errorObservaciones = validarObservaciones($observaciones);
if ($errorObservaciones !== null) {
    $_SESSION['inventario_mensaje'] = $errorObservaciones;
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// Nombre duplicado: no permitir dos insumos activos con el mismo nombre
// (comparación insensible a mayúsculas/minúsculas) — Semana 12 Día 4
$sqlDuplicado = "SELECT id_item FROM inventario WHERE LOWER(nombre_item) = LOWER(:nombre) AND activo = 1";
$paramsDuplicado = [':nombre' => $nombreItem];
if ($esEdicion) {
    $sqlDuplicado .= " AND id_item != :id_actual";
    $paramsDuplicado[':id_actual'] = $idItem;
}
$stmtDuplicado = $pdo->prepare($sqlDuplicado);
$stmtDuplicado->execute($paramsDuplicado);
if ($stmtDuplicado->fetch()) {
    $_SESSION['inventario_mensaje'] = 'Ya existe un insumo activo con ese nombre.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// ---- Normalización de campos opcionales (vacío -> NULL) ----
$proveedor = $proveedor !== '' ? $proveedor : null;
$telefonoProveedor = $telefonoProveedor !== '' ? $telefonoProveedor : null;
$ultimaCompra = $ultimaCompra !== '' ? $ultimaCompra : null;
$observaciones = $observaciones !== '' ? $observaciones : null;

try {
    if ($esEdicion) {
        // Nota Semana 12: stock_actual se excluye deliberadamente de este UPDATE.
        // Aunque el campo del formulario está deshabilitado, esta es la garantía
        // real de que nadie puede alterar el stock por esta vía — el único camino
        // es registrar un movimiento (ver modules/inventario/movimiento_registrar.php).
        $sql = "UPDATE inventario SET
                    nombre_item = :nombre_item,
                    categoria = :categoria,
                    unidad_medida = :unidad_medida,
                    stock_minimo = :stock_minimo,
                    precio_unitario = :precio_unitario,
                    proveedor = :proveedor,
                    telefono_proveedor = :telefono_proveedor,
                    ultima_compra = :ultima_compra,
                    observaciones = :observaciones
                WHERE id_item = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre_item' => $nombreItem,
            ':categoria' => $categoria,
            ':unidad_medida' => $unidadMedida,
            ':stock_minimo' => $stockMinimo,
            ':precio_unitario' => $precioUnitario,
            ':proveedor' => $proveedor,
            ':telefono_proveedor' => $telefonoProveedor,
            ':ultima_compra' => $ultimaCompra,
            ':observaciones' => $observaciones,
            ':id' => $idItem,
        ]);

        $_SESSION['inventario_mensaje'] = 'Insumo actualizado correctamente.';
        $_SESSION['inventario_mensaje_tipo'] = 'exito';
    } else {
        $sql = "INSERT INTO inventario (
                    nombre_item, categoria, unidad_medida, stock_actual, stock_minimo,
                    precio_unitario, proveedor, telefono_proveedor, ultima_compra, observaciones, activo
                ) VALUES (
                    :nombre_item, :categoria, :unidad_medida, :stock_actual, :stock_minimo,
                    :precio_unitario, :proveedor, :telefono_proveedor, :ultima_compra, :observaciones, 1
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre_item' => $nombreItem,
            ':categoria' => $categoria,
            ':unidad_medida' => $unidadMedida,
            ':stock_actual' => $stockActual,
            ':stock_minimo' => $stockMinimo,
            ':precio_unitario' => $precioUnitario,
            ':proveedor' => $proveedor,
            ':telefono_proveedor' => $telefonoProveedor,
            ':ultima_compra' => $ultimaCompra,
            ':observaciones' => $observaciones,
        ]);

        $_SESSION['inventario_mensaje'] = 'Insumo registrado correctamente.';
        $_SESSION['inventario_mensaje_tipo'] = 'exito';
    }

    header('Location: ' . BASE_URL . 'views/inventario.php');
    exit;

} catch (PDOException $e) {
    // No exponemos el detalle técnico al usuario final
    error_log('Error en inventario_procesar.php: ' . $e->getMessage());
    $_SESSION['inventario_mensaje'] = 'Ocurrió un error al guardar el insumo. Intenta nuevamente.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}
