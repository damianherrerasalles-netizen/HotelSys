<?php
$contrasenas = [
    'admin'          => 'Admin2026*',
    'recepcionista'  => 'Recep2026*',
];

foreach ($contrasenas as $rol => $pass) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    echo "<b>$rol:</b> $hash <br><br>";
}