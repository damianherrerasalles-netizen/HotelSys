<?php
// views/personal_form.php
$rutaBase = '../';
require_once $rutaBase . 'config/db.php';
require_once $rutaBase . 'includes/check_auth.php';
requerirAdmin(); // Solo admin puede crear/editar personal

$pdo = getConexion();

$esEdicion = isset($_GET['id']) && $_GET['id'] !== '';
$personal = [
    'id_personal' => '',
    'nombres' => '',
    'apellidos' => '',
    'tipo_documento' => 'CC',
    'num_documento' => '',
    'cargo' => 'Recepcionista',
    'telefono' => '',
    'email' => '',
    'turno' => 'Mañana',
    'contacto_emergencia' => '',
    'tel_emergencia' => '',
    'fecha_ingreso' => '',
];

if ($esEdicion) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT id_personal, nombres, apellidos, tipo_documento, num_documento,
                                   cargo, telefono, email, turno, contacto_emergencia,
                                   tel_emergencia, fecha_ingreso
                            FROM personal WHERE id_personal = :id");
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        $_SESSION['personal_mensaje'] = 'El colaborador solicitado no existe.';
        $_SESSION['personal_mensaje_tipo'] = 'error';
        header('Location: personal.php');
        exit;
    }

    $personal = $registro;
}

// Flash message (importante: este formulario necesita su propio bloque,
// por si el procesador redirige aquí con un error de validación)
$flashMensaje = $_SESSION['personal_mensaje'] ?? null;
$flashTipo = $_SESSION['personal_mensaje_tipo'] ?? 'exito';
unset($_SESSION['personal_mensaje'], $_SESSION['personal_mensaje_tipo']);

$tiposDocumento = ['CC', 'CE', 'TI'];
$cargosDisponibles = ['Administrador', 'Recepcionista', 'Mucama', 'Mantenimiento', 'Otro'];
$turnosDisponibles = ['Mañana', 'Tarde', 'Noche', 'Rotativo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $esEdicion ? 'Editar' : 'Nuevo' ?> Colaborador - HotelSys</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F5F5F5; margin: 0; }
        .contenedor-form {
            max-width: 640px; margin: 30px auto; background: #fff;
            border-radius: 10px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        h1 { color: #2E7D32; font-size: 1.3rem; margin-bottom: 4px; }
        .subtitulo { color: #777; font-size: 0.85rem; margin-bottom: 20px; }
        .fila { margin-bottom: 16px; }
        .fila label {
            display: block; font-size: 0.85rem; color: #444; margin-bottom: 4px; font-weight: bold;
        }
        .fila input, .fila select {
            width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 6px;
            box-sizing: border-box; font-size: 0.9rem;
        }
        .dos-columnas { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .seccion-titulo {
            color: #2E7D32; font-size: 0.95rem; margin: 24px 0 10px;
            border-bottom: 1px solid #E8F5E9; padding-bottom: 6px;
        }
        .botones { margin-top: 24px; display: flex; gap: 10px; }
        .btn {
            padding: 10px 18px; border-radius: 6px; text-decoration: none;
            font-size: 0.9rem; border: none; cursor: pointer;
        }
        .btn-guardar { background: #2E7D32; color: #fff; }
        .btn-cancelar { background: #eee; color: #444; }
        .alerta {
            padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.88rem;
        }
        .alerta-error { background: #FFEBEE; color: #C62828; }
        .alerta-exito { background: #E8F5E9; color: #2E7D32; }
        .obligatorio { color: #C62828; }
    </style>
</head>
<body>
<div class="contenedor-form">
    <h1><?= $esEdicion ? 'Editar Colaborador' : 'Nuevo Colaborador' ?></h1>
    <p class="subtitulo">Hotel Plaza Hostal — Módulo de Personal</p>

    <?php if ($flashMensaje): ?>
        <div class="alerta alerta-<?= htmlspecialchars($flashTipo) ?>">
            <?= htmlspecialchars($flashMensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>modules/personal/personal_procesar.php">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id_personal" value="<?= (int) $personal['id_personal'] ?>">
        <?php endif; ?>

        <div class="seccion-titulo">Datos personales</div>
        <div class="dos-columnas">
            <div class="fila">
                <label>Nombres <span class="obligatorio">*</span></label>
                <input type="text" name="nombres" required maxlength="80"
                       value="<?= htmlspecialchars($personal['nombres']) ?>">
            </div>
            <div class="fila">
                <label>Apellidos <span class="obligatorio">*</span></label>
                <input type="text" name="apellidos" required maxlength="80"
                       value="<?= htmlspecialchars($personal['apellidos']) ?>">
            </div>
        </div>

        <div class="dos-columnas">
            <div class="fila">
                <label>Tipo de documento <span class="obligatorio">*</span></label>
                <select name="tipo_documento" required>
                    <?php foreach ($tiposDocumento as $t): ?>
                        <option value="<?= $t ?>" <?= $personal['tipo_documento'] === $t ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fila">
                <label>Número de documento <span class="obligatorio">*</span></label>
                <input type="text" name="num_documento" required maxlength="20"
                       value="<?= htmlspecialchars($personal['num_documento']) ?>">
            </div>
        </div>

        <div class="seccion-titulo">Datos laborales</div>
        <div class="dos-columnas">
            <div class="fila">
                <label>Cargo <span class="obligatorio">*</span></label>
                <select name="cargo" required>
                    <?php foreach ($cargosDisponibles as $c): ?>
                        <option value="<?= $c ?>" <?= $personal['cargo'] === $c ? 'selected' : '' ?>>
                            <?= $c ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fila">
                <label>Turno <span class="obligatorio">*</span></label>
                <select name="turno" required>
                    <?php foreach ($turnosDisponibles as $t): ?>
                        <option value="<?= $t ?>" <?= $personal['turno'] === $t ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="fila">
            <label>Fecha de ingreso</label>
            <input type="date" name="fecha_ingreso"
                   value="<?= htmlspecialchars($personal['fecha_ingreso']) ?>">
        </div>

        <div class="seccion-titulo">Contacto</div>
        <div class="dos-columnas">
            <div class="fila">
                <label>Teléfono</label>
                <input type="text" name="telefono" maxlength="15"
                       value="<?= htmlspecialchars($personal['telefono']) ?>">
            </div>
            <div class="fila">
                <label>Email</label>
                <input type="email" name="email" maxlength="100"
                       value="<?= htmlspecialchars($personal['email']) ?>">
            </div>
        </div>

        <div class="seccion-titulo">Contacto de emergencia</div>
        <div class="dos-columnas">
            <div class="fila">
                <label>Nombre del contacto</label>
                <input type="text" name="contacto_emergencia" maxlength="80"
                       value="<?= htmlspecialchars($personal['contacto_emergencia']) ?>">
            </div>
            <div class="fila">
                <label>Teléfono de emergencia</label>
                <input type="text" name="tel_emergencia" maxlength="15"
                       value="<?= htmlspecialchars($personal['tel_emergencia']) ?>">
            </div>
        </div>

        <div class="botones">
            <button type="submit" class="btn btn-guardar">
                <?= $esEdicion ? 'Guardar cambios' : 'Registrar colaborador' ?>
            </button>
            <a href="personal.php" class="btn btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>