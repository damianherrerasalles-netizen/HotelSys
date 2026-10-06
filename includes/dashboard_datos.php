<?php
// includes/dashboard_datos.php
// Semana 14 Día 2 — Datos del Dashboard ejecutivo en una función compartida.
//
// Antes, estas ~10 consultas vivían directamente dentro de views/dashboard.php.
// Se extraen aquí para que las use tanto esa vista (al cargar la página) como
// el nuevo endpoint modules/dashboard/dashboard_datos.php, que las devuelve en
// JSON para la actualización automática del Día 3 — sin duplicar ninguna
// consulta entre los dos lugares.

function obtenerDatosDashboard(PDO $conexion): array {
    $stmtOcupacion = $conexion->query(
        "SELECT * FROM v_ocupacion_por_tipo ORDER BY FIELD(tipo, 'Sencilla','Doble','Triple','Suite')"
    );
    $ocupacionPorTipo = $stmtOcupacion->fetchAll(PDO::FETCH_ASSOC);

    // Mapa de habitaciones en tiempo real — Widget 1 (Semana 13 Día 2)
    $stmtHabitaciones = $conexion->query(
        "SELECT id_habitacion, numero_hab, tipo, estado FROM habitaciones ORDER BY numero_hab ASC"
    );
    $mapaHabitaciones = $stmtHabitaciones->fetchAll(PDO::FETCH_ASSOC);
    $totalHabitacionesDisponibles = count(array_filter(
        $mapaHabitaciones,
        fn($h) => $h['estado'] === 'Disponible'
    ));

    // Reservas hoy — Semana 14 Día 2: check-ins programados para hoy
    // (fecha_entrada = hoy), sin contar las reservas canceladas.
    $stmtReservasHoy = $conexion->query(
        "SELECT COUNT(*) AS total
         FROM reservas
         WHERE fecha_entrada = CURDATE() AND estado != 'Cancelada'"
    );
    $totalReservasHoy = (int) $stmtReservasHoy->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de clientes activos, para la tarjeta del modulo de Clientes
    $stmtClientes = $conexion->query("SELECT COUNT(*) AS total FROM clientes WHERE activo = 1");
    $totalClientesActivos = (int) $stmtClientes->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de personal activo, para la tarjeta del modulo de Personal
    $stmtPersonal = $conexion->query("SELECT COUNT(*) AS total FROM personal WHERE activo = 1");
    $totalPersonalActivo = (int) $stmtPersonal->fetch(PDO::FETCH_ASSOC)['total'];

    // Insumos en stock crítico, para el widget de alertas — Semana 11
    $stmtCriticos = $conexion->query(
        "SELECT COUNT(*) AS total FROM inventario WHERE stock_actual <= stock_minimo AND activo = 1"
    );
    $totalStockCritico = (int) $stmtCriticos->fetch(PDO::FETCH_ASSOC)['total'];

    // Alertas de inventario generadas automáticamente y aún abiertas — Semana 12 Día 2
    $stmtAlertas = $conexion->query("SELECT COUNT(*) AS total FROM alertas_inventario WHERE estado = 'abierta'");
    $totalAlertasAbiertas = (int) $stmtAlertas->fetch(PDO::FETCH_ASSOC)['total'];

    // Detalle de las alertas abiertas más antiguas, para el Widget 2 (Semana 13 Día 3)
    $stmtAlertasRecientes = $conexion->query(
        "SELECT a.stock_al_generar, a.stock_minimo_al_generar, i.nombre_item
         FROM alertas_inventario a
         JOIN inventario i ON i.id_item = a.id_item
         WHERE a.estado = 'abierta'
         ORDER BY a.fecha_generada ASC
         LIMIT 5"
    );
    $alertasRecientes = $stmtAlertasRecientes->fetchAll(PDO::FETCH_ASSOC);

    // Widget 3 (Semana 13 Día 4) — Caja del día: suma real de facturas pagadas
    // hoy. El módulo de Facturación (Actividad X) todavía no existe, así que
    // este valor mostrará $0 honestamente hasta que se construya esa actividad.
    $stmtCaja = $conexion->query(
        "SELECT COALESCE(SUM(total), 0) AS total_caja
         FROM facturas
         WHERE estado = 'Pagada' AND DATE(fecha_emision) = CURDATE()"
    );
    $totalCajaHoy = (float) $stmtCaja->fetch(PDO::FETCH_ASSOC)['total_caja'];

    // Widget 3 (Semana 13 Día 4) — % de tareas del personal completadas hoy
    $stmtTareasHoy = $conexion->query(
        "SELECT COUNT(*) AS total, SUM(completada) AS completadas
         FROM tareas_personal
         WHERE fecha = CURDATE()"
    );
    $filaTareasHoy = $stmtTareasHoy->fetch(PDO::FETCH_ASSOC);
    $totalTareasHoy = (int) $filaTareasHoy['total'];
    $totalTareasCompletadasHoy = (int) $filaTareasHoy['completadas'];
    $pctTareasCompletadasHoy = $totalTareasHoy > 0
        ? round($totalTareasCompletadasHoy / $totalTareasHoy * 100)
        : null;

    // Semana 15 Día 3 — "Ingresos del mes": la tarjeta ya existía como
    // placeholder ("Disponible en Mes 4") desde antes de que existiera el
    // módulo de Facturación. Ahora que la Actividad X ya genera facturas
    // Pagadas, se conecta con la suma real del mes en curso.
    $stmtIngresosMes = $conexion->query(
        "SELECT COALESCE(SUM(total), 0) AS total_mes
         FROM facturas
         WHERE estado = 'Pagada'
           AND YEAR(fecha_emision) = YEAR(CURDATE())
           AND MONTH(fecha_emision) = MONTH(CURDATE())"
    );
    $totalIngresosMes = (float) $stmtIngresosMes->fetch(PDO::FETCH_ASSOC)['total_mes'];

    return [
        'ocupacionPorTipo'             => $ocupacionPorTipo,
        'mapaHabitaciones'             => $mapaHabitaciones,
        'totalHabitacionesDisponibles' => $totalHabitacionesDisponibles,
        'totalReservasHoy'             => $totalReservasHoy,
        'totalClientesActivos'         => $totalClientesActivos,
        'totalPersonalActivo'          => $totalPersonalActivo,
        'totalStockCritico'            => $totalStockCritico,
        'totalAlertasAbiertas'         => $totalAlertasAbiertas,
        'alertasRecientes'             => $alertasRecientes,
        'totalCajaHoy'                 => $totalCajaHoy,
        'totalTareasHoy'               => $totalTareasHoy,
        'totalTareasCompletadasHoy'    => $totalTareasCompletadasHoy,
        'pctTareasCompletadasHoy'      => $pctTareasCompletadasHoy,
        'totalIngresosMes'             => $totalIngresosMes,
    ];
}
