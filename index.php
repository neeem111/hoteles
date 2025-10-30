<?php
// Define la información de la cadena hotelera
$nombreCadena = "Hoteles Nueva España S.L.";
$ciudadesDisponibles = ['Valencia', 'Santander', 'Toledo'];
$tiposHabitacion = [
    'Individual',
    'Doble Estándar',
    'Doble para Dos', // Añadido el tipo "solo para dos"
    'Suite de Lujo'
];

// Asegúrate de que este archivo exista y tenga tu código de conexión
include('conexion.php'); 

// Parámetros de filtrado
$filtroCiudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';

// Verificar que la conexión sea exitosa antes de continuar
if ($conn->connect_error) {
    die("Error de conexión, revisa conexion.php");
}

// 1. Consulta base para obtener todos los hoteles
$sql = "SELECT Id, Name, City, Address FROM Hotels";
$hoteles = [];

// 2. Añadir condición WHERE si hay un filtro de ciudad válido
if (!empty($filtroCiudad) && in_array($filtroCiudad, $ciudadesDisponibles)) {
    // Usar consultas preparadas para mayor seguridad
    $sql .= " WHERE City = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $filtroCiudad);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        // Manejar error de preparación de la consulta
        $resultado = false; 
    }
} else {
    // Consulta sin filtro
    $resultado = $conn->query($sql);
}

if ($resultado && $resultado->num_rows > 0) {
    // Si hay resultados, almacenarlos en un array
    while($row = $resultado->fetch_assoc()) {
        // Simulamos un precio aleatorio para el diseño, en un entorno real vendría de la BD
        $row['PrecioSimulado'] = rand(50, 200); 
        $hoteles[] = $row;
    }
}
// Importante: si se usó $stmt, cerrar el statement antes de cerrar la conexión
if (isset($stmt)) {
    $stmt->close();
}
$conn->close(); // Cerrar la conexión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌟 Hoteles Nueva España S.L. - Portal de Reservas</title>
    <style>
        :root {
            --color-primary: #dc3545; /* Rojo de la bandera española (o similar) */
            --color-secondary: #ffc107; /* Amarillo/Dorado */
            --color-dark: #343a40;
            --color-light: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-light);
            margin: 0;
            padding: 0;
        }
        
        .header {
            background-color: var(--color-primary);
            color: white;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        .header p {
            margin-top: 0;
            font-size: 1.1em;
            opacity: 0.9;
        }

        .container {
            padding: 20px;
            max-width: 1300px;
            margin: 30px auto;
        }
        
        h2 {
            color: var(--color-dark);
            text-align: center;
            margin-bottom: 40px;
            font-weight: 600;
            border-bottom: 3px solid var(--color-secondary);
            display: inline-block;
            padding-bottom: 5px;
            margin-left: 50%;
            transform: translateX(-50%);
        }

        /* --- Estilos del Filtro de Búsqueda --- */
        .search-filter {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
            align-items: center;
        }
        .search-filter label {
            font-weight: 600;
            color: var(--color-dark);
        }
        .search-filter select, .search-filter button {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            font-size: 1em;
        }
        .search-filter button {
            background-color: #28a745; /* Verde para Buscar */
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
        }
        .search-filter button:hover {
            background-color: #218838;
        }
        
        /* --- Estilos de la Cuadrícula de Hoteles --- */
        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }
        .hotel-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            display: flex;
            flex-direction: column;
        }
        .hotel-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }
        
        .hotel-content {
            padding: 20px;
            flex-grow: 1; /* Asegura que el contenido ocupe el espacio */
        }
        
        .hotel-name {
            color: var(--color-primary);
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.8em;
            border-left: 4px solid var(--color-secondary);
            padding-left: 10px;
        }
        .hotel-details p {
            margin: 8px 0;
            color: #555;
            font-size: 1em;
            line-height: 1.4;
        }
        .hotel-details strong {
            color: var(--color-dark);
        }

        .price-tag {
            background-color: var(--color-secondary);
            color: var(--color-dark);
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 700;
            font-size: 1.2em;
            display: inline-block;
            margin-top: 15px;
        }

        .btn-reserve {
            display: block; 
            margin-top: 20px; 
            text-align: center; 
            background-color: #007bff; 
            color: white; 
            padding: 12px; 
            border-radius: 8px; 
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-reserve:hover {
            background-color: #0056b3;
        }

        .no-results {
            text-align: center;
            padding: 60px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin: 50px auto;
            max-width: 600px;
        }

        /* --- Tipos de Habitaciones --- */
        .room-types {
            margin-top: 40px;
            text-align: center;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }
        .room-types h3 {
            color: var(--color-primary);
            margin-bottom: 15px;
        }
        .room-list {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .room-list span {
            background-color: var(--color-light);
            color: var(--color-dark);
            padding: 8px 15px;
            border: 1px dashed var(--color-primary);
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        /* Responsividad básica */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            .search-filter {
                flex-direction: column;
                align-items: stretch;
            }
            .search-filter select, .search-filter button {
                width: 100%;
            }
            .hotel-grid {
                 grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <h1><?php echo $nombreCadena; ?> 🇪🇸</h1>
        <p>Tu portal de reservas de alta calidad en las mejores ciudades de España.</p>
    </header>

    <div class="container">
        
        <div class="room-types">
            <h3>Nuestros Tipos de Habitaciones Disponibles</h3>
            <div class="room-list">
                <?php foreach ($tiposHabitacion as $tipo): ?>
                    <span><?php echo $tipo; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <h2>Encuentra tu Hotel</h2>
        
        <div class="search-filter">
            <form method="GET" action="index.php">
                <label for="ciudad">Filtrar por Ciudad:</label>
                <select name="ciudad" id="ciudad">
                    <option value="">Todas las Ciudades</option>
                    <?php foreach ($ciudadesDisponibles as $ciudad): ?>
                        <option value="<?php echo htmlspecialchars($ciudad); ?>" 
                            <?php echo ($filtroCiudad === $ciudad) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ciudad); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">🔍 Buscar</button>
            </form>
            <?php if (!empty($filtroCiudad)): ?>
                 <a href="index.php" style="text-decoration: none; color: var(--color-dark); font-weight: 600;">❌ Limpiar Filtro</a>
            <?php endif; ?>
        </div>
        
        <?php if (count($hoteles) > 0): ?>
            <div class="hotel-grid">
                <?php foreach ($hoteles as $hotel): ?>
                    <div class="hotel-card">
                        <div class="hotel-content">
                            <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['Name']); ?></h3>
                            <div class="hotel-details">
                                <p><strong>📍 Ciudad:</strong> <?php echo htmlspecialchars($hotel['City']); ?></p>
                                <p><strong>🗺️ Dirección:</strong> <?php echo htmlspecialchars($hotel['Address']); ?></p>
                            </div>
                            
                            <div class="price-tag">
                                Desde $<?php echo $hotel['PrecioSimulado']; ?>/noche
                            </div>
                            
                            <a href="reserva.php?hotel_id=<?php echo $hotel['Id']; ?>" class="btn-reserve">
                                Reservar Ahora
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <?php if (!empty($filtroCiudad)): ?>
                     <p>⚠️ No se encontraron hoteles en **<?php echo htmlspecialchars($filtroCiudad); ?>** que coincidan con la búsqueda.</p>
                     <p>Intenta con otra ciudad o <a href="index.php">muestra todos los hoteles</a>.</p>
                <?php else: ?>
                     <p>⚠️ No se encontraron hoteles en la base de datos.</p>
                     <p>Asegúrate de haber insertado datos en la tabla `Hotels`.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>