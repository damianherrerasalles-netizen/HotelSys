<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/check_auth.php';
// El require_once de arriba ya protege esta vista:
// si no hay $_SESSION['usuario_id'], redirige solo a login.php

$conexion = getConexion();

// Filtros opcionales vía GET
$filtroTipo = $_GET['tipo'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

$sql = "SELECT id_habitacion, numero_hab, tipo, piso, capacidad, precio_noche, estado
        FROM habitaciones
        WHERE 1=1";

$params = [];

if ($filtroTipo !== '') {
    $sql .= " AND tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}

if ($filtroEstado !== '') {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $filtroEstado;
}

$sql .= " ORDER BY numero_hab ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

function colorEstadoHabitacion($estado) {
    switch ($estado) {
        case 'Disponible':    return '#2E7D32'; // verde HotelSys
        case 'Ocupada':       return '#C62828'; // rojo
        case 'Mantenimiento': return '#F9A825'; // amarillo
        case 'Reservada':     return '#1565C0'; // azul
        default:              return '#757575'; // gris
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitaciones - HotelSys</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background-color: #f4f6f5;
            color: #222;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #2E7D32;
            font-size: 26px;
        }

        .btn-volver {
            background-color: #2E7D32;
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-volver:hover {
            background-color: #256428;
        }

        .filtros {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filtros select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .filtros button {
            background-color: #2E7D32;
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .filtros button:hover {
            background-color: #256428;
        }

        .filtros a {
            color: #2E7D32;
            text-decoration: none;
            font-size: 14px;
        }

        .filtros a:hover {
            text-decoration: underline;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            padding: 18px;
            width: 230px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            border-top: 5px solid #757575;
            transition: transform 0.15s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card h3 {
            font-size: 19px;
            color: #222;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 14px;
            margin-bottom: 6px;
            color: #444;
        }

        .card p strong {
            color: #111;
        }

        .badge {
            display: inline-block;
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
        }

        .card a.detalle {
            display: inline-block;
            margin-top: 12px;
            color: #2E7D32;
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
        }

        .card a.detalle:hover {
            text-decoration: underline;
        }

        .sin-resultados {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            color: #777;
        }

        .contador {
            margin-bottom: 15px;
            color: #555;
            font-size: 14px;
        }


        .btn-mantenimiento {
            margin-top: 10px;
            background-color: #F9A825;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px; 
            font-size: 12px;
            cursor: pointer;
            display: block;
        }
        .btn-mantenimiento:hover { background-color: #E19400; }
        .btn-mantenimiento.btn-disponible { background-color: #2E7D32; }
        .btn-mantenimiento.btn-disponible:hover { background-color: #256428; }
    </style>
</head>
<body>

    <div class="header">
        <h1>🏨 Habitaciones - HotelSys</h1>
        <a href="<?= BASE_URL ?>views/dashboard.php" class="btn-volver">← Volver al Dashboard</a>
    </div>

    <form method="get" action="habitaciones.php" class="filtros">
        <label>Tipo:</label>
        <select name="tipo">
            <option value="">Todos</option>
            <option value="Sencilla" <?= $filtroTipo == 'Sencilla' ? 'selected' : '' ?>>Sencilla</option>
            <option value="Doble" <?= $filtroTipo == 'Doble' ? 'selected' : '' ?>>Doble</option>
            <option value="Triple" <?= $filtroTipo == 'Triple' ? 'selected' : '' ?>>Triple</option>
            <option value="Suite" <?= $filtroTipo == 'Suite' ? 'selected' : '' ?>>Suite</option>
        </select>

        <label>Estado:</label>
        <select name="estado">
            <option value="">Todos</option>
            <option value="Disponible" <?= $filtroEstado == 'Disponible' ? 'selected' : '' ?>>Disponible</option>
            <option value="Ocupada" <?= $filtroEstado == 'Ocupada' ? 'selected' : '' ?>>Ocupada</option>
            <option value="Mantenimiento" <?= $filtroEstado == 'Mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
            <option value="Reservada" <?= $filtroEstado == 'Reservada' ? 'selected' : '' ?>>Reservada</option>
        </select>

        <button type="submit">Filtrar</button>
        <a href="habitaciones.php">Limpiar filtros</a>
    </form>

    <p class="contador"><?= count($habitaciones) ?> habitación(es) encontrada(s)</p>

    <?php if (count($habitaciones) > 0): ?>
        <div class="grid">
            <?php foreach ($habitaciones as $hab): ?>
                <div class="card" style="border-top-color: <?= colorEstadoHabitacion($hab['estado']) ?>;">
                    <h3>Hab. <?= htmlspecialchars($hab['numero_hab']) ?></h3>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($hab['tipo']) ?></p>
                    <p><strong>Piso:</strong> <?= htmlspecialchars($hab['piso']) ?></p>
                    <p><strong>Capacidad:</strong> <?= htmlspecialchars($hab['capacidad']) ?> pax</p>
                    <p><strong>Precio/noche:</strong> $<?= number_format($hab['precio_noche'], 0, ',', '.') ?></p>
                    <span class="badge" style="background-color: <?= colorEstadoHabitacion($hab['estado']) ?>;">
                        <?= htmlspecialchars($hab['estado']) ?>
                    </span>
                <?php if (esAdmin()): ?>
            <?php if ($hab['estado'] === 'Mantenimiento'): ?>
        <button
            class="btn-mantenimiento btn-disponible"
            data-id="<?= $hab['id_habitacion'] ?>"
            data-destino="Disponible"
        >Marcar Disponible</button>
    <?php elseif (in_array($hab['estado'], ['Disponible'])): ?>
        <button
            class="btn-mantenimiento"
            data-id="<?= $hab['id_habitacion'] ?>"
            data-destino="Mantenimiento"
        >Enviar a Mantenimiento</button>
    <?php else: ?>
        <p style="font-size:12px; color:#999; margin-top:8px;">
            Libere la reserva para poder enviar a mantenimiento.
        </p>
    <?php endif; ?>
<?php endif; ?>
                    <br>
                    <a href="habitacion_detalle.php?id=<?= $hab['id_habitacion'] ?>" class="detalle">Ver detalle →</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="sin-resultados">
            <p>No se encontraron habitaciones con los filtros seleccionados.</p>
        </div>
    <?php endif; ?>
<script>
document.querySelectorAll('.btn-mantenimiento').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const destino = this.dataset.destino;

        if (!confirm(`¿Confirmas cambiar esta habitación a "${destino}"?`)) return;

        fetch('<?= BASE_URL ?>modules/habitaciones/habitacion_actualizar_estado.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_habitacion=${id}&nuevo_estado=${destino}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.mensaje);
            if (data.exito) location.reload();
        })
        .catch(() => alert('Error de conexión con el servidor.'));
    });
});
</script>
</body>
</html>