<?php
// Incluir la conexión
include("conexion.php");

// Consulta a la tabla 'productos'
$sql = "SELECT * FROM productos";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Tienda</title>
</head>
<body>
    <h1>Bienvenido a mi tienda</h1>
    <h2>Catálogo de productos:</h2>

    <?php
    if ($resultado && $resultado->num_rows > 0) {
        echo "<ul>";
        while ($fila = $resultado->fetch_assoc()) {
            echo "<li>" . $fila['nombre'] . " - $" . $fila['precio'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No hay productos disponibles.</p>";
    }

    // Cerrar conexión
    $conn->close();
    ?>
</body>
</html>
