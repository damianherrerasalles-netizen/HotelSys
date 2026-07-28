<?php
// modules/personal/personal_procesar.php
// (ajusta la ruta si tu proyecto organiza los procesadores en otro lugar,
//  ej. views/personal_procesar.php — dime y ajusto las rutas relativas)

$rutaBase = '../../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin puede crear/editar personal, incluso llegando directo al POST

$pdo = getConexion();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'views/personal.php');
    exit;
}

// ---- Determinar modo: alta o edición ----
$esEdicion = isset($_POST['id_personal']) && $_POST['id_personal'] !== '';
$idPersonal = $esEdicion ? (int) $_POST['id_personal'] : null;

// ---- Recoger y limpiar datos del formulario ----
$nombres = trim($_POST['nombres'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$tipoDocumento = trim($_POST['tipo_documento'] ?? '');
$numDocumento = trim($_POST['num_documento'] ?? '');
$cargo = trim($_POST['cargo'] ?? '');
$turno = trim($_POST['turno'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$contactoEmergencia = trim($_POST['contacto_emergencia'] ?? '');
$telEmergencia = trim($_POST['tel_emergencia'] ?? '');
$fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');

// URL de retorno en caso de error (mantiene el ?id=X si es edición)
$urlFormulario = BASE_URL . 'views/personal_form.php' . ($esEdicion ? '?id=' . $idPersonal : '');

// ---- Validación de campos obligatorios ----
$camposObligatorios = [
    'nombres' => $nombres,
    'apellidos' => $apellidos,
    'tipo_documento' => $tipoDocumento,
    'num_documento' => $numDocumento,
    'cargo' => $cargo,
    'turno' => $turno,
];

foreach ($camposObligatorios as $campo => $valor) {
    if ($valor === '') {
        $_SESSION['personal_mensaje'] = 'El campo "' . $campo . '" es obligatorio.';
        $_SESSION['personal_mensaje_tipo'] = 'error';
        header('Location: ' . $urlFormulario);
        exit;
    }
}

// Validación de ENUMs contra valores permitidos (defensa adicional, por si
// alguien manipula el <select> del formulario o envía el POST directamente)
$tiposDocumentoValidos = ['CC', 'CE', 'TI'];
$cargosValidos = ['Administrador', 'Recepcionista', 'Mucama', 'Mantenimiento', 'Otro'];
$turnosValidos = ['Mañana', 'Tarde', 'Noche', 'Rotativo'];

if (!in_array($tipoDocumento, $tiposDocumentoValidos, true)) {
    $_SESSION['personal_mensaje'] = 'Tipo de documento no válido.';
    $_SESSION['personal_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}
if (!in_array($cargo, $cargosValidos, true)) {
    $_SESSION['personal_mensaje'] = 'Cargo no válido.';
    $_SESSION['personal_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}
if (!in_array($turno, $turnosValidos, true)) {
    $_SESSION['personal_mensaje'] = 'Turno no válido.';
    $_SESSION['personal_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}

// ---- Normalización de campos opcionales (vacío -> NULL) ----
$telefono = $telefono !== '' ? $telefono : null;
$email = $email !== '' ? $email : null;
$contactoEmergencia = $contactoEmergencia !== '' ? $contactoEmergencia : null;
$telEmergencia = $telEmergencia !== '' ? $telEmergencia : null;
$fechaIngreso = $fechaIngreso !== '' ? $fechaIngreso : null;

try {
    // ---- Validación de unicidad de num_documento ----
    if ($esEdicion) {
        $sqlCheck = "SELECT COUNT(*) FROM personal WHERE num_documento = :doc AND id_personal != :id";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':doc' => $numDocumento, ':id' => $idPersonal]);
    } else {
        $sqlCheck = "SELECT COUNT(*) FROM personal WHERE num_documento = :doc";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':doc' => $numDocumento]);
    }

    if ((int) $stmtCheck->fetchColumn() > 0) {
        $_SESSION['personal_mensaje'] = 'Ya existe un colaborador registrado con ese número de documento.';
        $_SESSION['personal_mensaje_tipo'] = 'error';
        header('Location: ' . $urlFormulario);
        exit;
    }

    // ---- INSERT o UPDATE ----
    // Nota: usuario_sistema, password_hash y rol se excluyen intencionalmente
    // (campos legado, autenticación real vive en la tabla usuarios).
    if ($esEdicion) {
        $sql = "UPDATE personal SET
                    nombres = :nombres,
                    apellidos = :apellidos,
                    tipo_documento = :tipo_documento,
                    num_documento = :num_documento,
                    cargo = :cargo,
                    turno = :turno,
                    telefono = :telefono,
                    email = :email,
                    contacto_emergencia = :contacto_emergencia,
                    tel_emergencia = :tel_emergencia,
                    fecha_ingreso = :fecha_ingreso
                WHERE id_personal = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':tipo_documento' => $tipoDocumento,
            ':num_documento' => $numDocumento,
            ':cargo' => $cargo,
            ':turno' => $turno,
            ':telefono' => $telefono,
            ':email' => $email,
            ':contacto_emergencia' => $contactoEmergencia,
            ':tel_emergencia' => $telEmergencia,
            ':fecha_ingreso' => $fechaIngreso,
            ':id' => $idPersonal,
        ]);

        $_SESSION['personal_mensaje'] = 'Colaborador actualizado correctamente.';
        $_SESSION['personal_mensaje_tipo'] = 'exito';
    } else {
        $sql = "INSERT INTO personal (
                    nombres, apellidos, tipo_documento, num_documento, cargo, turno,
                    telefono, email, contacto_emergencia, tel_emergencia, fecha_ingreso, activo
                ) VALUES (
                    :nombres, :apellidos, :tipo_documento, :num_documento, :cargo, :turno,
                    :telefono, :email, :contacto_emergencia, :tel_emergencia, :fecha_ingreso, 1
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':tipo_documento' => $tipoDocumento,
            ':num_documento' => $numDocumento,
            ':cargo' => $cargo,
            ':turno' => $turno,
            ':telefono' => $telefono,
            ':email' => $email,
            ':contacto_emergencia' => $contactoEmergencia,
            ':tel_emergencia' => $telEmergencia,
            ':fecha_ingreso' => $fechaIngreso,
        ]);

        $_SESSION['personal_mensaje'] = 'Colaborador registrado correctamente.';
        $_SESSION['personal_mensaje_tipo'] = 'exito';
    }

    header('Location: ' . BASE_URL . 'views/personal.php');
    exit;

} catch (PDOException $e) {
    // No exponemos el detalle técnico al usuario final
    error_log('Error en personal_procesar.php: ' . $e->getMessage());
    $_SESSION['personal_mensaje'] = 'Ocurrió un error al guardar el colaborador. Intenta nuevamente.';
    $_SESSION['personal_mensaje_tipo'] = 'error';
    header('Location: ' . $urlFormulario);
    exit;
}