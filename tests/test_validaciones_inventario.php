<?php
// tests/test_validaciones_inventario.php
// Semana 12 Día 4 — Script de pruebas para las funciones de
// includes/validaciones_inventario.php. Se ejecuta por línea de comandos:
//   php tests/test_validaciones_inventario.php
// No requiere base de datos ni servidor web: prueba las funciones puras
// directamente, con varios casos válidos e inválidos por cada una.

require_once __DIR__ . '/../includes/validaciones_inventario.php';

$totalPruebas = 0;
$totalFallidas = 0;

/**
 * Compara el resultado real contra el esperado y muestra PASS/FAIL.
 * $esperadoValido = true  -> se espera que la función devuelva null (sin error)
 * $esperadoValido = false -> se espera que la función devuelva un mensaje (string)
 */
function verificar(string $descripcion, $resultado, bool $esperadoValido): void
{
    global $totalPruebas, $totalFallidas;
    $totalPruebas++;

    $esValido = ($resultado === null);
    $ok = ($esValido === $esperadoValido);

    if (!$ok) {
        $totalFallidas++;
    }

    $estado = $ok ? 'PASS' : 'FAIL';
    $detalle = $esperadoValido
        ? ($esValido ? 'aceptado correctamente' : 'se rechazó y no debía: "' . $resultado . '"')
        : ($esValido ? 'se aceptó y debía rechazarse' : 'rechazado correctamente: "' . $resultado . '"');

    echo "[$estado] $descripcion -- $detalle\n";
}

echo "==========================================================\n";
echo "HotelSys -- Pruebas de validaciones de Inventario (Semana 12 Dia 4)\n";
echo "==========================================================\n\n";

echo "-- validarNombreInsumo() --\n";
verificar('Nombre valido "Toallas de bano"', validarNombreInsumo('Toallas de baño'), true);
verificar('Nombre vacio', validarNombreInsumo(''), false);
verificar('Nombre de 1 caracter ("X")', validarNombreInsumo('X'), false);
verificar('Nombre de exactamente 100 caracteres', validarNombreInsumo(str_repeat('A', 100)), true);
verificar('Nombre de 101 caracteres (muy largo)', validarNombreInsumo(str_repeat('A', 101)), false);

echo "\n-- validarCategoriaInventario() --\n";
verificar('Categoria valida "Bebidas"', validarCategoriaInventario('Bebidas'), true);
verificar('Categoria inexistente "Electronica"', validarCategoriaInventario('Electronica'), false);
verificar('Categoria vacia', validarCategoriaInventario(''), false);

echo "\n-- validarEnteroNoNegativo() --\n";
verificar('Entero positivo "15"', validarEnteroNoNegativo('15'), true);
verificar('Cero "0"', validarEnteroNoNegativo('0'), true);
verificar('Negativo "-5"', validarEnteroNoNegativo('-5'), false);
verificar('Decimal "3.5"', validarEnteroNoNegativo('3.5'), false);
verificar('Texto "abc"', validarEnteroNoNegativo('abc'), false);
verificar('Vacio ""', validarEnteroNoNegativo(''), false);

echo "\n-- validarNumeroNoNegativo() --\n";
verificar('Vacio (opcional, valido) ""', validarNumeroNoNegativo(''), true);
verificar('Decimal positivo "4500.50"', validarNumeroNoNegativo('4500.50'), true);
verificar('Cero "0"', validarNumeroNoNegativo('0'), true);
verificar('Negativo "-100"', validarNumeroNoNegativo('-100'), false);
verificar('Texto "gratis"', validarNumeroNoNegativo('gratis'), false);

echo "\n-- validarTelefonoProveedor() --\n";
verificar('Vacio (opcional, valido) ""', validarTelefonoProveedor(''), true);
verificar('Telefono valido "3157654321"', validarTelefonoProveedor('3157654321'), true);
verificar('Con letras "315-ABCD"', validarTelefonoProveedor('315-ABCD'), false);
verificar('Muy corto "123"', validarTelefonoProveedor('123'), false);
verificar('Muy largo (16 digitos)', validarTelefonoProveedor(str_repeat('1', 16)), false);

echo "\n-- validarObservaciones() --\n";
verificar('Vacio (opcional, valido) ""', validarObservaciones(''), true);
verificar('Observacion normal', validarObservaciones('Compra de reposicion mensual'), true);
verificar('Exactamente 255 caracteres', validarObservaciones(str_repeat('A', 255)), true);
verificar('256 caracteres (muy larga)', validarObservaciones(str_repeat('A', 256)), false);

echo "\n==========================================================\n";
echo "Total de pruebas: $totalPruebas -- Fallidas: $totalFallidas\n";
if ($totalFallidas === 0) {
    echo "RESULTADO FINAL: TODAS LAS PRUEBAS PASARON\n";
} else {
    echo "RESULTADO FINAL: HAY PRUEBAS FALLIDAS\n";
}
echo "==========================================================\n";

exit($totalFallidas === 0 ? 0 : 1);
