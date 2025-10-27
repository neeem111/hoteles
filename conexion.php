<?php
$servidor = "localhost";
$usuario = "root";
$clave = "";
$basededatos = "mitienda_bd"; // Nombre de tu base de datos

$conn = new mysqli($servidor, $usuario, $clave, $basededatos);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
} else {
    echo "✅ Conexión exitosa con la base de datos '$basededatos'";
}
?>
