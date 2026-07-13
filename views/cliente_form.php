<?php
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

$idCliente = isset($_GET['id']) ? (int)$_GET['id'] : null;
$modoEdicion = $idCliente !== null;
$cliente = [
    'tipo_documento'      => 'CC',
    'num_documento'       => '',
    'nombres'             => '',
    'apellidos'           => '',
    'telefono'            => '',
    'email'               => '',
    'ciudad_origen'       => '',
    'departamento_origen' => '',
    'fecha_nacimiento'    => '',
    'observaciones'       => '',
];

if ($modoEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
    $stmt->execute([':id' => $idCliente]);
    $encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$encontrado) {
        $_SESSION['cliente_mensaje'] = 'El cliente solicitado no existe.';
        $_SESSION['cliente_mensaje_tipo'] = 'error';
        header('Location: clientes.php');
        exit();
    }

    $cliente = $encontrado;
}

$mensaje = $_SESSION['cliente_mensaje'] ?? null;
$mensajeTipo = $_SESSION['cliente_mensaje_tipo'] ?? null;
unset($_SESSION['cliente_mensaje'], $_SESSION['cliente_mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $modoEdicion ? 'Editar cliente' : 'Nuevo cliente'; ?> - HotelSys</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estilos.css">
</head>
<body>
<div class="contenedor">
    <h1><?php echo $modoEdicion ? 'Editar cliente' : 'Nuevo cliente'; ?></h1>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-<?php echo htmlspecialchars($mensajeTipo); ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="cliente_procesar.php" class="formulario-cliente">
        <?php if ($modoEdicion): ?>
            <input type="hidden" name="id_cliente" value="<?php echo (int)$cliente['id_cliente']; ?>">
        <?php endif; ?>

        <div class="fila-formulario">
            <div class="campo">
                <label for="tipo_documento">Tipo de documento *</label>
                <select name="tipo_documento" id="tipo_documento" required>
                    <?php foreach (['CC', 'CE', 'PAS', 'NIT'] as $tipo): ?>
                        <option value="<?php echo $tipo; ?>"
                            <?php echo $cliente['tipo_documento'] === $tipo ? 'selected' : ''; ?>>
                            <?php echo $tipo; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="num_documento">Numero de documento *</label>
                <input type="text" name="num_documento" id="num_documento" required maxlength="20"
                       value="<?php echo htmlspecialchars($cliente['num_documento']); ?>">
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombres">Nombres *</label>
                <input type="text" name="nombres" id="nombres" required maxlength="80"
                       value="<?php echo htmlspecialchars($cliente['nombres']); ?>">
            </div>
            <div class="campo">
                <label for="apellidos">Apellidos *</label>
                <input type="text" name="apellidos" id="apellidos" required maxlength="80"
                       value="<?php echo htmlspecialchars($cliente['apellidos']); ?>">
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="telefono">Telefono *</label>
                <input type="text" name="telefono" id="telefono" required maxlength="15"
                       value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
            </div>
            <div class="campo">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" maxlength="100" placeholder="opcional"
                       value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="ciudad_origen">Ciudad de origen</label>
                <input type="text" name="ciudad_origen" id="ciudad_origen" maxlength="60" placeholder="opcional"
                       value="<?php echo htmlspecialchars($cliente['ciudad_origen'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="departamento_origen">Departamento de origen</label>
                <input type="text" name="departamento_origen" id="departamento_origen" maxlength="60" placeholder="opcional"
                       value="<?php echo htmlspecialchars($cliente['departamento_origen'] ?? ''); ?>">
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                       value="<?php echo htmlspecialchars($cliente['fecha_nacimiento'] ?? ''); ?>">
            </div>
        </div>

        <div class="campo campo-ancho">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" rows="3" placeholder="opcional"><?php
                echo htmlspecialchars($cliente['observaciones'] ?? '');
            ?></textarea>
        </div>

        <div class="acciones-formulario">
            <a href="clientes.php" class="boton">Cancelar</a>
            <button type="submit" class="boton boton-primario">Guardar cliente</button>
        </div>
    </form>
</div>
</body>
</html>