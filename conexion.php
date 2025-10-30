<?php
$servidor = "localhost";
$usuario = "root";
$clave = "";
$basededatos = "mitienda_bd";

$conn = new mysqli($servidor, $usuario, $clave, $basededatos);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// No hacemos echo aquí para no romper el HTML en otras páginas
?>
