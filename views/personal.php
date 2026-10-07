<?php
// views/personal.php
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

// ---- Filtros ----
$busqueda = trim($_GET['busqueda'] ?? '');
$filtroCargo = trim($_GET['cargo'] ?? '');
$filtroEstado = $_GET['estado'] ?? 'activos'; // activos | inactivos | todos

$condiciones = [];
$params = [];

if ($busqueda !== '') {
    $condiciones[] = "(CONCAT(nombres, ' ', apellidos) LIKE :busqueda1 OR num_documento LIKE :busqueda2)";
    $params[':busqueda1'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
}

if ($filtroCargo !== '') {
    $condiciones[] = "cargo = :cargo";
    $params[':cargo'] = $filtroCargo;
}

if ($filtroEstado === 'activos') {
    $condiciones[] = "activo = 1";
} elseif ($filtroEstado === 'inactivos') {
    $condiciones[] = "activo = 0";
}
// 'todos' no agrega condición

$whereSql = count($condiciones) > 0 ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$sql = "SELECT id_personal, nombres, apellidos, tipo_documento, num_documento,
               cargo, telefono, turno, activo, fecha_ingreso
        FROM personal
        $whereSql
        ORDER BY activo DESC, apellidos ASC, nombres ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$personal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargos disponibles para el filtro (del ENUM real)
$cargosDisponibles = ['Administrador', 'Recepcionista', 'Mucama', 'Mantenimiento', 'Otro'];

// Colores por cargo para las tarjetas/badges
$colorCargo = [
    'Administrador'  => '#2E7D32',
    'Recepcionista'  => '#1976D2',
    'Mucama'         => '#7B1FA2',
    'Mantenimiento'  => '#EF6C00',
    'Otro'           => '#616161',
];

$flashMensaje = $_SESSION['personal_mensaje'] ?? null;
$flashTipo = $_SESSION['personal_mensaje_tipo'] ?? 'exito';
unset($_SESSION['personal_mensaje'], $_SESSION['personal_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Personal - HotelSys</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        /* Ajuste Semana 20 (hallazgo #5, Semana 19 Día 4): esta vista no tenía
           ninguna navegación hacia el resto del sistema. Mismo nav-hotelsys
           que ya usan views/reservas.php, views/habitaciones.php, etc. */
        .nav-hotelsys {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2E7D32;
            padding: 10px 20px;
            margin-bottom: 15px;
        }
        .nav-hotelsys a {
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
        }
        .nav-hotelsys a:hover {
            text-decoration: underline;
        }
        .nav-rol {
            color: #E8F5E9;
        }
        .personal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .tarjeta-personal {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 16px;
            border-left: 5px solid #2E7D32;
        }
        .tarjeta-personal.inactivo {
            opacity: 0.6;
            border-left-color: #9E9E9E;
        }
        .badge-cargo {
            display: inline-block;
            color: #fff;
            font-size: 12px;
            padding: 3px 10px;
            border-radius: 12px;
            margin-bottom: 8px;
        }
        .badge-inactivo {
            background: #9E9E9E;
            color: #fff;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 6px;
        }
        .filtros-personal {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 14px;
        }
        .filtros-personal input, .filtros-personal select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .info-linea {
            font-size: 13.5px;
            color: #444;
            margin: 3px 0;
        }
        .acciones-personal {
            margin-top: 10px;
            display: flex;
            gap: 8px;
        }
        .acciones-personal a {
            font-size: 13px;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 6px;
        }
        .btn-ver { background: #E8F5E9; color: #2E7D32; }
        .btn-editar { background: #E3F2FD; color: #1976D2; }
    </style>
</head>
<body>

<nav class="nav-hotelsys">
    <div>
        <a href="<?= BASE_URL ?>views/dashboard.php">← Dashboard</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/reservas.php">Reservas</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/clientes.php">Clientes</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/habitaciones.php">Habitaciones</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/personal.php">Personal</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/tareas_personal.php">Tareas</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/facturas.php">Facturas</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/cierre_caja.php">Caja</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/mantenimientos.php">Mantenimiento</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>views/reportes.php">Reportes</a>
    </div>
    <span class="nav-rol">
        Sesión: <strong><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></strong>
    </span>
    <a href="<?= BASE_URL ?>views/logout.php">Cerrar sesión</a>
</nav>

<div class="contenedor">
    <h1>Gestión de Personal</h1>
    <p class="subtitulo">Hotel Plaza Hostal — Colaboradores registrados ·
        <a href="<?= BASE_URL ?>views/tareas_personal.php">Ver tareas del personal →</a>
    </p>

    <?php if ($flashMensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>">
            <?= htmlspecialchars($flashMensaje) ?>
        </div>
    <?php endif; ?>

    <div class="barra-superior">
        <?php if (esAdmin()): ?>
            <a href="personal_form.php" class="btn btn-primario">+ Nuevo Colaborador</a>
        <?php endif; ?>
    </div>

    <form method="GET" class="filtros-personal">
        <input type="text" name="busqueda" placeholder="Buscar por nombre o documento..."
               value="<?= htmlspecialchars($busqueda) ?>">

        <select name="cargo">
            <option value="">Todos los cargos</option>
            <?php foreach ($cargosDisponibles as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $filtroCargo === $c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="estado">
            <option value="activos" <?= $filtroEstado === 'activos' ? 'selected' : '' ?>>Activos</option>
            <option value="inactivos" <?= $filtroEstado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
            <option value="todos" <?= $filtroEstado === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>

        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <a href="personal.php" class="btn btn-texto">Limpiar</a>
    </form>

    <p class="conteo-resultados"><?= count($personal) ?> colaborador(es) encontrado(s)</p>

    <div class="personal-grid">
        <?php foreach ($personal as $p): ?>
            <?php $color = $colorCargo[$p['cargo']] ?? '#616161'; ?>
            <div class="tarjeta-personal <?= $p['activo'] == 0 ? 'inactivo' : '' ?>"
                 style="border-left-color: <?= $color ?>;">

                <span class="badge-cargo" style="background: <?= $color ?>;">
                    <?= htmlspecialchars($p['cargo']) ?>
                </span>
                <?php if ($p['activo'] == 0): ?>
                    <span class="badge-inactivo">Inactivo</span>
                <?php endif; ?>

                <h3><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?></h3>

                <p class="info-linea">
                    <?= htmlspecialchars($p['tipo_documento']) ?>: <?= htmlspecialchars($p['num_documento']) ?>
                </p>
                <p class="info-linea">Turno: <?= htmlspecialchars($p['turno']) ?></p>
                <?php if ($p['telefono']): ?>
                    <p class="info-linea">Tel: <?= htmlspecialchars($p['telefono']) ?></p>
                <?php endif; ?>
                <?php if ($p['fecha_ingreso']): ?>
                    <p class="info-linea">Ingreso: <?= htmlspecialchars($p['fecha_ingreso']) ?></p>
                <?php endif; ?>

                <div class="acciones-personal">
                    <a href="personal_detalle.php?id=<?= $p['id_personal'] ?>" class="btn-ver">Ver detalle</a>
                    <?php if (esAdmin()): ?>
                        <a href="personal_form.php?id=<?= $p['id_personal'] ?>" class="btn-editar">Editar</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($personal) === 0): ?>
            <p>No se encontraron colaboradores con los filtros aplicados.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
