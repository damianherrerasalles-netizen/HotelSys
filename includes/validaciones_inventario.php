<?php
// includes/validaciones_inventario.php
// Semana 12 Día 4 — Funciones de validación reutilizables para el módulo de
// Inventario. Se extraen aquí (en vez de dejarlas repetidas dentro de cada
// procesador) para poder probarlas de forma aislada con un script de pruebas
// por línea de comandos (ver tests/test_validaciones_inventario.php), sin
// necesidad de simular peticiones HTTP ni tocar la base de datos.
//
// Convención: cada función recibe el valor ya "trim()" por el llamador y
// devuelve null si es válido, o un mensaje de error en español listo para
// mostrar al usuario si no lo es.

/**
 * Categorías válidas de inventario (debe coincidir con el ENUM de la BD).
 */
function categoriasValidasInventario(): array
{
    return ['Lencería', 'Aseo', 'Amenidades', 'Bebidas', 'Mantenimiento', 'Oficina', 'Alimentos', 'Otro'];
}

/**
 * Nombre de insumo: no vacío, longitud razonable (2 a 100 caracteres).
 */
function validarNombreInsumo(string $nombre): ?string
{
    if ($nombre === '') {
        return 'El nombre del insumo es obligatorio.';
    }
    $longitud = mb_strlen($nombre);
    if ($longitud < 2) {
        return 'El nombre del insumo debe tener al menos 2 caracteres.';
    }
    if ($longitud > 100) {
        return 'El nombre del insumo no puede superar los 100 caracteres.';
    }
    return null;
}

/**
 * Categoría: debe estar en la lista fija permitida (defensa adicional, por si
 * alguien manipula el <select> del formulario o envía el POST directamente).
 */
function validarCategoriaInventario(string $categoria): ?string
{
    if (!in_array($categoria, categoriasValidasInventario(), true)) {
        return 'Categoría no válida.';
    }
    return null;
}

/**
 * Entero no negativo (para stock_actual, stock_minimo, cantidades de
 * movimiento). Acepta solo dígitos: sin signo, sin decimales, sin espacios.
 */
function validarEnteroNoNegativo(string $valor): ?string
{
    if (!ctype_digit($valor)) {
        return 'Debe ser un número entero positivo (sin decimales ni signos).';
    }
    return null;
}

/**
 * Número decimal no negativo (para precio_unitario). Un valor vacío se
 * considera válido — el llamador decide si lo reemplaza por 0.00.
 */
function validarNumeroNoNegativo(string $valor): ?string
{
    if ($valor === '') {
        return null;
    }
    if (!is_numeric($valor) || (float) $valor < 0) {
        return 'Debe ser un número mayor o igual a cero.';
    }
    return null;
}

/**
 * Teléfono de proveedor: opcional. Si se envía, solo dígitos, entre 7 y 15
 * caracteres (permite prefijos internacionales sin el símbolo '+').
 */
function validarTelefonoProveedor(string $telefono): ?string
{
    if ($telefono === '') {
        return null;
    }
    if (!ctype_digit($telefono)) {
        return 'El teléfono del proveedor debe contener solo números.';
    }
    if (strlen($telefono) < 7 || strlen($telefono) > 15) {
        return 'El teléfono del proveedor debe tener entre 7 y 15 dígitos.';
    }
    return null;
}

/**
 * Observaciones: opcional, longitud máxima acorde a la columna VARCHAR(255)
 * usada tanto en inventario.observaciones como en
 * movimientos_inventario.observaciones.
 */
function validarObservaciones(string $observaciones): ?string
{
    if (mb_strlen($observaciones) > 255) {
        return 'Las observaciones no pueden superar los 255 caracteres.';
    }
    return null;
}
