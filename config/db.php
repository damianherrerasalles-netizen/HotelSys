<?php
// config/db.php — Conexión PDO a MySQL
// HotelSys — Hotel Plaza Hostal

// Semana 13 Día 4 — Sin esto, PHP calcula la fecha/hora en UTC por defecto
// mientras que MySQL (CURDATE(), NOW()) usa la hora real del sistema
// (Bogotá, UTC-5). El desfase de 5 horas hace que, de noche, PHP ya "vea"
// el día siguiente mientras MySQL todavía está en el día actual — por
// ejemplo, esto rompía el Widget 3 del Dashboard ejecutivo (tareas
// completadas hoy), que comparaba una fecha de PHP contra CURDATE().
date_default_timezone_set('America/Bogota');

define('BASE_URL', 'http://localhost/hotelsys/');

define('DB_HOST', 'localhost');
define('DB_NAME', 'hotelsys_plaza');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getConexion(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En producción nunca mostrar el mensaje real
            error_log("Error de conexión: " . $e->getMessage());
            die(json_encode([
                'error' => 'No se pudo conectar a la base de datos.'
            ]));
        }
    }

    return $pdo;
}