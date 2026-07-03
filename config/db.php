<?php
// config/db.php — Conexión PDO a MySQL
// HotelSys — Hotel Plaza Hostal

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
