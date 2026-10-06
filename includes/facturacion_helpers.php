<?php
// includes/facturacion_helpers.php
// Semana 15 Día 2 — Lógica compartida de la Actividad X (Facturación).
//
// Se usa tanto desde modules/reservas/reserva_actualizar_estado.php (al
// generar la factura automáticamente en el checkout) como desde
// modules/facturacion/cargo_extra_registrar.php (al registrar un cargo
// extra) — para no duplicar el cálculo de totales ni la resolución de
// "quién" hizo cada operación.

// --- Deuda técnica rastreada: usuarios (login) y personal (colaboradores)
// nunca se vincularon por FK. En vez de tocar las tablas ni los módulos ya
// cerrados (Auth, Personal, Reservas), esta función resuelve el id_personal
// del colaborador conectado por coincidencia de email entre ambas tablas.
// Si no hay coincidencia (o personal.email está vacío), devuelve null — el
// mismo comportamiento que reservas.id_personal ya tiene hoy para toda
// reserva existente.
function resolverIdPersonalDesdeSesion(PDO $pdo, int $usuarioId): ?int {
    // Nota: usuarios.email y personal.email quedaron con collations distintas
    // (utf8mb4_unicode_ci vs utf8mb4_general_ci) desde que se crearon esas
    // tablas en módulos distintos. Se fuerza la comparación a una sola
    // collation aquí mismo, sin alterar las tablas de Auth/Personal.
    $stmt = $pdo->prepare(
        "SELECT p.id_personal
         FROM usuarios u
         JOIN personal p ON p.email COLLATE utf8mb4_unicode_ci = u.email COLLATE utf8mb4_unicode_ci
            AND p.activo = 1
         WHERE u.id = :id_usuario
         LIMIT 1"
    );
    $stmt->execute([':id_usuario' => $usuarioId]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    return $fila ? (int) $fila['id_personal'] : null;
}

// --- Genera la factura de una reserva al hacer checkout (Finalizada). ---
// subtotal = total_calculado de la reserva (habitación) + suma de sus
// cargos_extras; iva_valor = subtotal x 19%; total = subtotal + iva_valor.
// Se asume que ya se está dentro de una transacción abierta por quien llama
// (reserva_actualizar_estado.php) y que la reserva existe.
function generarFacturaAutomatica(PDO $pdo, int $idReserva, ?int $idPersonal): int {
    $stmtReserva = $pdo->prepare(
        "SELECT id_cliente, total_calculado FROM reservas WHERE id_reserva = :id"
    );
    $stmtReserva->execute([':id' => $idReserva]);
    $reserva = $stmtReserva->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        throw new RuntimeException("No se encontró la reserva #{$idReserva} para facturar.");
    }

    $stmtCargos = $pdo->prepare(
        "SELECT COALESCE(SUM(valor), 0) AS total_cargos
         FROM cargos_extras
         WHERE id_reserva = :id"
    );
    $stmtCargos->execute([':id' => $idReserva]);
    $totalCargosExtra = (float) $stmtCargos->fetch(PDO::FETCH_ASSOC)['total_cargos'];

    $ivaPorcentaje = 19.00;
    $subtotal      = (float) $reserva['total_calculado'] + $totalCargosExtra;
    $ivaValor      = round($subtotal * ($ivaPorcentaje / 100), 2);
    $total         = round($subtotal + $ivaValor, 2);

    $stmtInsertar = $pdo->prepare(
        "INSERT INTO facturas
            (id_reserva, id_cliente, id_personal, subtotal, iva_porcentaje, iva_valor, total, estado)
         VALUES
            (:id_reserva, :id_cliente, :id_personal, :subtotal, :iva_porcentaje, :iva_valor, :total, 'Pendiente')"
    );
    $stmtInsertar->execute([
        ':id_reserva'     => $idReserva,
        ':id_cliente'     => $reserva['id_cliente'],
        ':id_personal'    => $idPersonal,
        ':subtotal'       => $subtotal,
        ':iva_porcentaje' => $ivaPorcentaje,
        ':iva_valor'      => $ivaValor,
        ':total'          => $total,
    ]);

    return (int) $pdo->lastInsertId();
}
