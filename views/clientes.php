<?php
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

// Filtros desde GET
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$mostrarTodos = isset($_GET['mostrar']) && $_GET['mostrar'] === 'todos';
$filtroCiudad = isset($_GET['ciudad']) ? trim($_GET['ciudad']) : '';
$filtroDepartamento = isset($_GET['departamento']) ? trim($_GET['departamento']) : '';

// Construccion dinamica de la consulta
$sql = "SELECT id_cliente, tipo_documento, num_documento, nombres, apellidos,
               telefono, email, ciudad_origen, departamento_origen, activo
        FROM clientes
        WHERE 1=1";
$params = [];

if (!$mostrarTodos) {
    $sql .= " AND activo = 1";
}

if ($busqueda !== '') {
    $sql .= " AND (num_documento LIKE :busqueda1
                OR nombres LIKE :busqueda2
                OR apellidos LIKE :busqueda3
                OR CONCAT(nombres, ' ', apellidos) LIKE :busqueda4)";
    $like = '%' . $busqueda . '%';
    $params[':busqueda1'] = $like;
    $params[':busqueda2'] = $like;
    $params[':busqueda3'] = $like;
    $params[':busqueda4'] = $like;
}

if ($filtroCiudad !== '') {
    $sql .= " AND ciudad_origen = :ciudad";
    $params[':ciudad'] = $filtroCiudad;
}

if ($filtroDepartamento !== '') {
    $sql .= " AND departamento_origen = :departamento";
    $params[':departamento'] = $filtroDepartamento;
}

$sql .= " ORDER BY apellidos ASC, nombres ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listas para los selects de filtro (valores distintos ya existentes en la tabla)
$stmtCiudades = $pdo->query(
    "SELECT DISTINCT ciudad_origen FROM clientes
     WHERE ciudad_origen IS NOT NULL AND ciudad_origen != ''
     ORDER BY ciudad_origen"
);
$ciudadesDisponibles = $stmtCiudades->fetchAll(PDO::FETCH_COLUMN);

$stmtDepartamentos = $pdo->query(
    "SELECT DISTINCT departamento_origen FROM clientes
     WHERE departamento_origen IS NOT NULL AND departamento_origen != ''
     ORDER BY departamento_origen"
);
$departamentosDisponibles = $stmtDepartamentos->fetchAll(PDO::FETCH_COLUMN);

// Mensaje flash (mismo patron que reservas/habitaciones)
$mensaje = $_SESSION['cliente_mensaje'] ?? null;
$mensajeTipo = $_SESSION['cliente_mensaje_tipo'] ?? null;
unset($_SESSION['cliente_mensaje'], $_SESSION['cliente_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - HotelSys</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estilos.css">
</head>
<body>
<div class="contenedor">
    <h1>Clientes</h1>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-<?php echo htmlspecialchars($mensajeTipo); ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="clientes.php" class="barra-busqueda">
        <input type="text" name="buscar" placeholder="Buscar por nombre o documento"
               value="<?php echo htmlspecialchars($busqueda); ?>">

        <select name="ciudad" onchange="this.form.submit()">
            <option value="">Todas las ciudades</option>
            <?php foreach ($ciudadesDisponibles as $ciudad): ?>
                <option value="<?php echo htmlspecialchars($ciudad); ?>"
                    <?php echo $filtroCiudad === $ciudad ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ciudad); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="departamento" onchange="this.form.submit()">
            <option value="">Todos los departamentos</option>
            <?php foreach ($departamentosDisponibles as $depto): ?>
                <option value="<?php echo htmlspecialchars($depto); ?>"
                    <?php echo $filtroDepartamento === $depto ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($depto); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="filtro-todos">
            <input type="checkbox" name="mostrar" value="todos" <?php echo $mostrarTodos ? 'checked' : ''; ?>
                   onchange="this.form.submit()">
            Mostrar inactivos
        </label>

        <button type="submit">Buscar</button>
        <a href="cliente_form.php" class="boton boton-primario">+ Nuevo cliente</a>
    </form>

    <table class="tabla-datos">
        <thead>
        <tr>
            <th>Documento</th>
            <th>Nombre</th>
            <th>Telefono</th>
            <th>Origen</th>
            <th>Estado</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (count($clientes) === 0): ?>
            <tr>
                <td colspan="6">No se encontraron clientes con estos criterios.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['tipo_documento'] . ' ' . $c['num_documento']); ?></td>
                    <td><?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']); ?></td>
                    <td><?php echo htmlspecialchars($c['telefono'] ?? '-'); ?></td>
                    <td>
                        <?php
                        $origen = trim(($c['ciudad_origen'] ?? '') . ($c['departamento_origen'] ? ', ' . $c['departamento_origen'] : ''));
                        echo htmlspecialchars($origen !== '' ? $origen : '-');
                        ?>
                    </td>
                    <td>
                        <?php if ($c['activo']): ?>
                            <span class="badge badge-activo">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="cliente_detalle.php?id=<?php echo (int)$c['id_cliente']; ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>