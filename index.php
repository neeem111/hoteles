<?php
include('conexion.php'); 

// Verificar que la conexión sea exitosa antes de continuar
if ($conn->connect_error) {
    die("Error de conexión, revisa conexion.php");
}

// 1. Consulta para obtener todos los hoteles
$sql = "SELECT Id, Name, City, Address FROM Hotels";
$resultado = $conn->query($sql);

$hoteles = [];
if ($resultado && $resultado->num_rows > 0) {
    // Si hay resultados, almacenarlos en un array
    while($row = $resultado->fetch_assoc()) {
        $hoteles[] = $row;
    }
}
$conn->close(); // Cerrar la conexión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Reservas de Hoteles</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .hotel-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .hotel-content {
            padding: 20px;
        }
        .hotel-name {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5em;
        }
        .hotel-details p {
            margin: 5px 0;
            color: #555;
            font-size: 0.9em;
        }
        .no-results {
            text-align: center;
            padding: 50px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

    <header class="header">
        <h1>TravelNow</h1>
        <p>Encuentra tu próximo destino y reserva la habitación perfecta.</p>
    </header>

    <div class="container">
        <h2>Hoteles Disponibles</h2>

        <?php if (count($hoteles) > 0): ?>
            <div class="hotel-grid">
                <?php foreach ($hoteles as $hotel): ?>
                    <div class="hotel-card">
                        
                        <div class="hotel-content">
                            <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['Name']); ?></h3>
                            <div class="hotel-details">
                                <p><strong>Ciudad:</strong> <?php echo htmlspecialchars($hotel['City']); ?></p>
                                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($hotel['Address']); ?></p>
                            </div>
                            <a href="#" style="display: block; margin-top: 15px; text-align: center; background-color: #28a745; color: white; padding: 10px; border-radius: 5px; text-decoration: none;">Ver Habitaciones</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>No se encontraron hoteles en la base de datos.</p>
                <p>Asegúrate de haber insertado algunos datos en la tabla 'Hotels'.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>