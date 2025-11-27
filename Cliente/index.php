<?php
session_start(); // NECESARIO PARA LEER LA SESIÓN

// Define la información de la cadena hotelera
$nombreCadena = "Hoteles Nueva España S.L.";
$ciudadesDisponibles = ['Valencia', 'Santander', 'Toledo'];
$tiposHabitacion = [
    'Individual',
    'Doble Estándar',
    'Doble para Dos', 
    'Suite de Lujo'
];

// Conexión a Base de Datos
include('../conexion.php'); 

$filtroCiudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';

if ($conn->connect_error) {
    die("Error de conexión, revisa conexion.php");
}

$sql = "SELECT Id, Name, City, Address FROM Hotels";
$hoteles = [];

// Consulta con filtro
if (!empty($filtroCiudad) && in_array($filtroCiudad, $ciudadesDisponibles)) {
    $sql .= " WHERE City = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $filtroCiudad);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $resultado = false;
    }
} else {
    $resultado = $conn->query($sql);
}

// Cargar resultados
if ($resultado && $resultado->num_rows > 0) {
    while($row = $resultado->fetch_assoc()) {
        $row['PrecioSimulado'] = rand(50, 200);
        $hoteles[] = $row;
    }
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌟 Hoteles Nueva España S.L. - Portal de Reservas</title>

    <style>
        :root {
            --color-primary: #dc3545;
            --color-secondary: #ffc107;
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

        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .hotel-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.4s ease;
            display: flex;
            flex-direction: column;
        }
        .hotel-card:hover {
            transform: translateY(-8px);
        }

        .hotel-content { padding: 20px; }

        .hotel-name {
            color: var(--color-primary);
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.8em;
            border-left: 4px solid var(--color-secondary);
            padding-left: 10px;
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
        }
    </style>
</head>
<body>

<!-- 🔵 BLOQUE SUPERIOR DERECHA: LOGIN O SESIÓN -->
<?php if (isset($_SESSION['user_name'])): ?>

    <div style="
        position: absolute;
        top: 20px;
        right: 20px;
        background: white;
        padding: 10px 15px;
        border-radius: 10px;
        display: flex;
        gap: 12px;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-size: 0.9em;
        font-weight: 600;">
        
        <span>👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>

        <a href="../logout.php"
            style="
                background: #dc3545;
                color: white;
                padding: 6px 10px;
                border-radius: 6px;
                text-decoration: none;
                font-weight: bold;">
            Cerrar sesión
        </a>
    </div>

<?php else: ?>

    <div style="
        position: absolute;
        top: 20px;
        right: 20px;
        background: white;
        padding: 10px 15px;
        border-radius: 10px;
        display: flex;
        gap: 12px;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-size: 0.9em;
        font-weight: 600;">
        
        <a href="index.php?page=client_login"
            style="
                background: #28a745;
                color: white;
                padding: 6px 14px;
                border-radius: 6px;
                text-decoration: none;
                font-weight: bold;">
            🔐 Iniciar sesión
        </a>
    </div>

<?php endif; ?>


<header class="header">
    <h1><?= $nombreCadena ?> 🇪🇸</h1>
    <p>Tu portal de reservas de alta calidad en las mejores ciudades de España.</p>
</header>

<div class="container">

    <div class="room-types">
        <h3>Nuestros Tipos de Habitaciones Disponibles</h3>
        <div class="room-list">
            <?php foreach ($tiposHabitacion as $tipo): ?>
                <span><?= $tipo ?></span>
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
                    <option value="<?= $ciudad ?>" <?= $filtroCiudad === $ciudad ? 'selected' : '' ?>>
                        <?= $ciudad ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">🔍 Buscar</button>
        </form>

        <?php if (!empty($filtroCiudad)): ?>
            <a href="index.php" style="text-decoration:none;font-weight:600;color:#000;">
                ❌ Limpiar Filtro
            </a>
        <?php endif; ?>
    </div>

    <?php if (count($hoteles) > 0): ?>
        <div class="hotel-grid">
            <?php foreach ($hoteles as $hotel): ?>
                <div class="hotel-card">
                    <div class="hotel-content">
                        <h3 class="hotel-name"><?= htmlspecialchars($hotel['Name']) ?></h3>

                        <p><strong>📍 Ciudad:</strong> <?= htmlspecialchars($hotel['City']) ?></p>
                        <p><strong>🗺️ Dirección:</strong> <?= htmlspecialchars($hotel['Address']) ?></p>

                        <div class="price-tag">
                            Desde $<?= $hotel['PrecioSimulado'] ?>/noche
                        </div>

                        <a href="reserva.php?hotel_id=<?= $hotel['Id'] ?>" class="btn-reserve">
                            Reservar Ahora
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="no-results">
            <p>⚠️ No se encontraron hoteles con los filtros aplicados.</p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
