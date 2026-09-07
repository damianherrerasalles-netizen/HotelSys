<?php
// modules/inventario/inventario_procesar.php
// Semana 11 — Módulo de Inventario
$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
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
$camposObligatorios = [
    'nombre_item' => $nombreItem,
    'categoria' => $categoria,
    'unidad_medida' => $unidadMedida,
    'stock_actual' => $stockActual,
    'stock_minimo' => $stockMinimo,
];

foreach ($camposObligatorios as $campo => $valor) {
    if ($valor === '') {
        $_SESSION['inventario_mensaje'] = 'El campo "' . $campo . '" es obligatorio.';
        $_SESSION['inventario_mensaje_tipo'] = 'error';
        header('Location: ' . $urlFormulario);
        exit;
    }
}

// Validación de categoría contra valores permitidos (defensa adicional, por si
// alguien manipula el <select> del formulario o envía el POST directamente)
$categoriasValidas = ['Lencería', 'Aseo', 'Amenidades', 'Bebidas', 'Mantenimiento', 'Oficina', 'Alimentos', 'Otro'];

if (!in_array($categoria, $categoriasValidas, true)) {
    $_SESSION['inventario_mensaje'] = 'Categoría no válida.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// Validación numérica de stock (no negativos)
if (!ctype_digit($stockActual) || !ctype_digit($stockMinimo)) {
    $_SESSION['inventario_mensaje'] = 'El stock actual y el stock mínimo deben ser números enteros positivos.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}
$stockActual = (int) $stockActual;
$stockMinimo = (int) $stockMinimo;

// Precio unitario: opcional, por defecto 0.00
if ($precioUnitario === '') {
    $precioUnitario = 0.00;
} elseif (!is_numeric($precioUnitario) || (float) $precioUnitario < 0) {
    $_SESSION['inventario_mensaje'] = 'El precio unitario no es válido.';
    $_SESSION['inventario_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
} else {
    $precioUnitario = (float) $precioUnitario;
}

// ---- Normalización de campos opcionales (vacío -> NULL) ----
$proveedor = $proveedor !== '' ? $proveedor : null;
$telefonoProveedor = $telefonoProveedor !== '' ? $telefonoProveedor : null;
$ultimaCompra = $ultimaCompra !== '' ? $ultimaCompra : null;
$observaciones = $observaciones !== '' ? $observaciones : null;

try {
    if ($esEdicion) {
        $sql = "UPDATE inventario SET
                    nombre_item = :nombre_item,
                    categoria = :categoria,
                    unidad_medida = :unidad_medida,
                    stock_actual = :stock_actual,
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
            ':stock_actual' => $stockActual,
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
