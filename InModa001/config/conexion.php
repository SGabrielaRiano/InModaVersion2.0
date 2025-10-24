<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "inmoda_db";

$mysqli = new mysqli($servername, $username, $password, $database);

if ($mysqli->connect_error) {
    die("<h3 style='color:red; text-align:center; margin-top:20%;'>
    ❌ Error de conexión a la base de datos: " . $mysqli->connect_error . "<br>
    Verifica que el servidor MySQL esté activo y la base de datos 'inmoda_db' exista.
    </h3>");
}

$mysqli->set_charset("utf8mb4");
?>
