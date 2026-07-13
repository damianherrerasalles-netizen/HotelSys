<?php
require_once '../config/db.php';
require_once '../includes/check_auth.php';

$pdo = getConexion();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clientes.php');
    exit();
}

function redirigirConMensaje(string $mensaje, string $tipo, string $destino = 'clientes.php'): void {
    $_SESSION['cliente_mensaje'] = $mensaje;
    $_SESSION['cliente_mensaje_tipo'] = $tipo;
    header('Location: ' . $destino);
    exit();
}

// Recoleccion y saneo basico de datos
$idCliente          = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
$tipoDocumento      = trim($_POST['tipo_documento'] ?? '');
$numDocumento       = trim($_POST['num_documento'] ?? '');
$nombres            = trim($_POST['nombres'] ?? '');
$apellidos          = trim($_POST['apellidos'] ?? '');
$telefono           = trim($_POST['telefono'] ?? '');
$email              = trim($_POST['email'] ?? '');
$ciudadOrigen       = trim($_POST['ciudad_origen'] ?? '');
$departamentoOrigen = trim($_POST['departamento_origen'] ?? '');
$fechaNacimiento    = trim($_POST['fecha_nacimiento'] ?? '');
$observaciones      = trim($_POST['observaciones'] ?? '');

$modoEdicion = $idCliente !== null && $idCliente > 0;

// Validacion de campos obligatorios
$tiposValidos = ['CC', 'CE', 'PAS', 'NIT'];
$errores = [];

if (!in_array($tipoDocumento, $tiposValidos, true)) {
    $errores[] = 'Tipo de documento invalido.';
}
if ($numDocumento === '' || strlen($numDocumento) > 20) {
    $errores[] = 'El numero de documento es obligatorio (maximo 20 caracteres).';
}
if ($nombres === '' || strlen($nombres) > 80) {
    $errores[] = 'Los nombres son obligatorios (maximo 80 caracteres).';
}
if ($apellidos === '' || strlen($apellidos) > 80) {
    $errores[] = 'Los apellidos son obligatorios (maximo 80 caracteres).';
}
if ($telefono === '' || strlen($telefono) > 15) {
    $errores[] = 'El telefono es obligatorio (maximo 15 caracteres).';
}

// Validaciones de campos opcionales (solo si vienen con dato)
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El email no tiene un formato valido.';
}
if ($fechaNacimiento !== '') {
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
    if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimiento) {
        $errores[] = 'La fecha de nacimiento no es valida.';
    }
}

if (!empty($errores)) {
    $destino = $modoEdicion ? 'cliente_form.php?id=' . $idCliente : 'cliente_form.php';
    redirigirConMensaje(implode(' ', $errores), 'error', $destino);
}

// Validar unicidad de num_documento (excluyendo el propio registro si es edicion)
$sqlDuplicado = "SELECT id_cliente FROM clientes WHERE num_documento = :num_documento";
$paramsDuplicado = [':num_documento' => $numDocumento];

if ($modoEdicion) {
    $sqlDuplicado .= " AND id_cliente != :id_cliente";
    $paramsDuplicado[':id_cliente'] = $idCliente;
}

$stmtDuplicado = $pdo->prepare($sqlDuplicado);
$stmtDuplicado->execute($paramsDuplicado);

if ($stmtDuplicado->fetch()) {
    $destino = $modoEdicion ? 'cliente_form.php?id=' . $idCliente : 'cliente_form.php';
    redirigirConMensaje('Ya existe un cliente registrado con ese numero de documento.', 'error', $destino);
}

// Normalizar campos opcionales vacios a NULL
$email              = $email !== '' ? $email : null;
$ciudadOrigen       = $ciudadOrigen !== '' ? $ciudadOrigen : null;
$departamentoOrigen = $departamentoOrigen !== '' ? $departamentoOrigen : null;
$fechaNacimiento    = $fechaNacimiento !== '' ? $fechaNacimiento : null;
$observaciones      = $observaciones !== '' ? $observaciones : null;

try {
    if ($modoEdicion) {
        $sql = "UPDATE clientes SET
                    tipo_documento = :tipo_documento,
                    num_documento = :num_documento,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    telefono = :telefono,
                    email = :email,
                    ciudad_origen = :ciudad_origen,
                    departamento_origen = :departamento_origen,
                    fecha_nacimiento = :fecha_nacimiento,
                    observaciones = :observaciones
                WHERE id_cliente = :id_cliente";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo_documento'      => $tipoDocumento,
            ':num_documento'       => $numDocumento,
            ':nombres'             => $nombres,
            ':apellidos'           => $apellidos,
            ':telefono'            => $telefono,
            ':email'               => $email,
            ':ciudad_origen'       => $ciudadOrigen,
            ':departamento_origen' => $departamentoOrigen,
            ':fecha_nacimiento'    => $fechaNacimiento,
            ':observaciones'       => $observaciones,
            ':id_cliente'          => $idCliente,
        ]);

        redirigirConMensaje('Cliente actualizado correctamente.', 'exito');
    } else {
        $sql = "INSERT INTO clientes
                    (tipo_documento, num_documento, nombres, apellidos, telefono,
                     email, ciudad_origen, departamento_origen, fecha_nacimiento, observaciones)
                VALUES
                    (:tipo_documento, :num_documento, :nombres, :apellidos, :telefono,
                     :email, :ciudad_origen, :departamento_origen, :fecha_nacimiento, :observaciones)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo_documento'      => $tipoDocumento,
            ':num_documento'       => $numDocumento,
            ':nombres'             => $nombres,
            ':apellidos'           => $apellidos,
            ':telefono'            => $telefono,
            ':email'               => $email,
            ':ciudad_origen'       => $ciudadOrigen,
            ':departamento_origen' => $departamentoOrigen,
            ':fecha_nacimiento'    => $fechaNacimiento,
            ':observaciones'       => $observaciones,
        ]);

        redirigirConMensaje('Cliente registrado correctamente.', 'exito');
    }
} catch (PDOException $e) {
    error_log('Error al guardar cliente: ' . $e->getMessage());
    $destino = $modoEdicion ? 'cliente_form.php?id=' . $idCliente : 'cliente_form.php';
    redirigirConMensaje('Ocurrio un error al guardar el cliente. Intenta de nuevo.', 'error', $destino);
}