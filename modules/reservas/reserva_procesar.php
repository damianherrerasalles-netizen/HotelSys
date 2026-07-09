<?php
// modules/reservas/reserva_procesar.php
// HotelSys — Hotel Plaza Hostal
// Procesa la creación de una reserva: valida solapamiento de fechas
// (regla que NO se puede aplicar solo con SQL) y luego inserta.

require_once __DIR__ . '/../../config/db.php'; // Define BASE_URL y getConexion()
require_once __DIR__ . '/../../includes/check_auth.php';

$pdo = getConexion();

function volverConError(string $msg): void {
    $_SESSION['reserva_mensaje'] = $msg;
    $_SESSION['reserva_mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volverConError('Método no permitido.');
}

// --- 1. Recoger y validar datos básicos del formulario ---
$id_cliente     = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$id_habitacion  = filter_input(INPUT_POST, 'id_habitacion', FILTER_VALIDATE_INT);
$fecha_entrada  = $_POST['fecha_entrada'] ?? '';
$fecha_salida   = $_POST['fecha_salida'] ?? '';
$num_personas   = filter_input(INPUT_POST, 'num_personas', FILTER_VALIDATE_INT);
$canal_origen   = $_POST['canal_origen'] ?? '';
$observaciones  = trim($_POST['observaciones'] ?? '');

$canalesValidos = ['WhatsApp', 'Telefono', 'Presencial', 'Web'];

if (!$id_cliente || !$id_habitacion || !$num_personas || $num_personas < 1) {
    volverConError('Todos los campos obligatorios deben estar completos.');
}
if (!in_array($canal_origen, $canalesValidos, true)) {
    volverConError('Canal de origen inválido.');
}

// Validar formato y lógica de fechas
$fechaEntradaObj = DateTime::createFromFormat('Y-m-d', $fecha_entrada);
$fechaSalidaObj  = DateTime::createFromFormat('Y-m-d', $fecha_salida);

if (!$fechaEntradaObj || !$fechaSalidaObj) {
    volverConError('Las fechas ingresadas no son válidas.');
}
if ($fechaEntradaObj < new DateTime('today')) {
    volverConError('La fecha de entrada no puede ser anterior a hoy.');
}
if ($fechaSalidaObj <= $fechaEntradaObj) {
    volverConError('La fecha de salida debe ser posterior a la fecha de entrada.');
}

// --- 2. Validar que la habitación exista y esté disponible ---
$stmtHab = $pdo->prepare(
    "SELECT precio_noche, estado FROM habitaciones WHERE id_habitacion = :id AND activa = 1"
);
$stmtHab->execute([':id' => $id_habitacion]);
$habitacion = $stmtHab->fetch(PDO::FETCH_ASSOC);

if (!$habitacion) {
    volverConError('La habitación seleccionada no existe o no está activa.');
}

// --- 2.1 VALIDACIÓN CLAVE: rechazar si la habitación está en Mantenimiento ---
// Una habitación en Mantenimiento no puede reservarse sin importar las fechas elegidas.
if ($habitacion['estado'] === 'Mantenimiento') {
    volverConError('La habitación seleccionada está en Mantenimiento y no puede reservarse en este momento.');
}

// --- 3. VALIDACIÓN CLAVE: solapamiento de fechas (no se puede hacer solo en SQL) ---
// Dos rangos de fechas se cruzan si: entrada_nueva < salida_existente Y salida_nueva > entrada_existente
$stmtSolape = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM reservas
     WHERE id_habitacion = :id_habitacion
       AND estado NOT IN ('Cancelada', 'Finalizada')
       AND fecha_entrada < :fecha_salida
       AND fecha_salida > :fecha_entrada"
);
$stmtSolape->execute([
    ':id_habitacion' => $id_habitacion,
    ':fecha_entrada'  => $fecha_entrada,
    ':fecha_salida'   => $fecha_salida,
]);
$solape = $stmtSolape->fetch(PDO::FETCH_ASSOC);

if ((int)$solape['total'] > 0) {
    volverConError('Esa habitación ya tiene una reserva activa que se cruza con esas fechas.');
}

// --- 4. Calcular noches y total (num_noches es columna generada, no se inserta) ---
$intervalo  = $fechaEntradaObj->diff($fechaSalidaObj);
$num_noches = (int)$intervalo->days;
$precio_noche_aplicado = (float)$habitacion['precio_noche'];
$total_calculado = $num_noches * $precio_noche_aplicado;

// --- 5. Insertar la reserva (prepared statement, sin id_personal por ahora) ---
try {
    $stmtInsert = $pdo->prepare(
        "INSERT INTO reservas
            (id_cliente, id_habitacion, fecha_entrada, fecha_salida, num_personas,
             estado, canal_origen, precio_noche_aplicado, total_calculado, observaciones)
         VALUES
            (:id_cliente, :id_habitacion, :fecha_entrada, :fecha_salida, :num_personas,
             'Pendiente', :canal_origen, :precio_noche_aplicado, :total_calculado, :observaciones)"
    );
    $stmtInsert->execute([
        ':id_cliente'             => $id_cliente,
        ':id_habitacion'          => $id_habitacion,
        ':fecha_entrada'          => $fecha_entrada,
        ':fecha_salida'           => $fecha_salida,
        ':num_personas'           => $num_personas,
        ':canal_origen'           => $canal_origen,
        ':precio_noche_aplicado'  => $precio_noche_aplicado,
        ':total_calculado'        => $total_calculado,
        ':observaciones'          => $observaciones !== '' ? $observaciones : null,
    ]);

    $_SESSION['reserva_mensaje'] = 'Reserva creada correctamente.';
    $_SESSION['reserva_mensaje_tipo'] = 'exito';
    header('Location: ' . BASE_URL . 'views/reservas.php');
    exit();

} catch (PDOException $e) {
    error_log('Error al crear reserva: ' . $e->getMessage());
    volverConError('Ocurrió un error al guardar la reserva. Intenta de nuevo.');
}