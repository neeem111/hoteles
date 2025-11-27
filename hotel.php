<?php
session_start();
include('conexion.php'); 

// 1. Obtener y validar el ID del hotel
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;

if ($hotel_id <= 0) {
    header("Location: Cliente/index.php?error=Hotel+no+especificado");
    exit();
}

// Lógica de precios definida
$tarifasBase = [
    'Toledo' => 20,
    'Valencia' => 30,
    'Santander' => 25
];
$incrementoPorCiudad = [
    'Toledo' => 15,
    'Valencia' => 12,
    'Santander' => 10
];

// --- FUNCIONES PARA OBTENER DATOS DE LA BBDD ---

// Función para obtener los detalles del hotel
function obtenerDetallesHotel($conn, $id) {
    $sql = "SELECT Id, Name, City, Address FROM Hotels WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->fetch_assoc();
}

// Función para obtener los tipos de habitación y sus costes (usando la BBDD RoomType)
function obtenerTiposHabitacion($conn) {
    // Ordenamos por CostPerNight para aplicar el incremento de forma consistente
    $sql = "SELECT Id, Name, Guests FROM RoomType ORDER BY CostPerNight ASC";
    $resultado = $conn->query($sql);
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

// --- EJECUCIÓN ---

$hotel = obtenerDetallesHotel($conn, $hotel_id);

if (!$hotel) {
    header("Location: Cliente/index.php?error=Hotel+no+encontrado");
    exit();
}

$tiposHabitacion = obtenerTiposHabitacion($conn);

$ciudad = $hotel['City'];
$precioBase = $tarifasBase[$ciudad] ?? 50;
$incremento = $incrementoPorCiudad[$ciudad] ?? 15;

$habitacionesDisponibles = [];
$contador = 0;

// Aplicar la lógica de precios
foreach ($tiposHabitacion as $tipo) {
    // Calculamos el precio: Base + (Contador * Incremento)
    $precioCalculado = $precioBase + ($contador * $incremento);
    $tipo['PrecioNoche'] = number_format($precioCalculado, 2, '.', '');
    $habitacionesDisponibles[] = $tipo;
    $contador++; 
}

// Comprobar estado de la sesión para el navbar
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';
$nombreCadena = "Hoteles Nueva España S.L.";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva en <?php echo htmlspecialchars($hotel['Name']); ?></title>
    <link rel="stylesheet" href="../styleCarlos.css"> <style>
        :root {
            --color-primary: #a02040;
            --color-secondary: #ffc107;
            --color-dark: #343a40;
            --color-light: #f8f9fa;
        }
        body {
            background-color: var(--color-light);
            font-family: Arial, sans-serif;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 100px auto 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .hotel-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--color-secondary);
            margin-bottom: 30px;
        }
        .hotel-header h1 {
            color: var(--color-primary);
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        .hotel-info {
            color: #555;
            font-size: 1.1em;
        }
        .room-listing {
            list-style: none;
            padding: 0;
        }
        .room-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            transition: box-shadow 0.3s;
        }
        .room-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .room-details h3 {
            margin: 0 0 5px 0;
            color: var(--color-dark);
            font-size: 1.4em;
        }
        .room-details p {
            margin: 5px 0;
            color: #777;
        }
        .room-price {
            text-align: right;
        }
        .room-price strong {
            display: block;
            font-size: 1.8em;
            color: #28a745; /* Verde para el precio */
            margin-bottom: 10px;
        }
        .btn-select {
            background-color: var(--color-primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        .btn-select:hover {
            background-color: #801933;
        }
    </style>
</head>
<body>

    <nav style="background:#a02040; color:white; padding:15px; text-align:center; position:fixed; top:0; width:100%; z-index:1000;">
        <a href="Cliente/index.php" style="color:white; text-decoration:none; font-size:1.5em; font-weight:bold;">← Volver a Hoteles</a>
        <span style="float:right; margin-right: 20px; font-size:1.1em;">
            <?php echo $is_logged_in ? "Bienvenido, " . $user_name : '<a href="login.php" style="color:white;">Iniciar Sesión</a>'; ?>
        </span>
    </nav>


    <div class="container">
        <div class="hotel-header">
            <h1><?php echo htmlspecialchars($hotel['Name']); ?></h1>
            <p class="hotel-info">📍 <strong><?php echo htmlspecialchars($ciudad); ?></strong> | Dirección: <?php echo htmlspecialchars($hotel['Address']); ?></p>
        </div>

        <h2>Tipos de Habitación Disponibles</h2>

        <ul class="room-listing">
            <?php foreach ($habitacionesDisponibles as $room): ?>
            <li class="room-card">
                <div class="room-details">
                    <h3><?php echo htmlspecialchars($room['Name']); ?></h3>
                    <p>Máximo de Huéspedes: <strong><?php echo htmlspecialchars($room['Guests']); ?></strong></p>
                    <p>ID Tipo: <?php echo htmlspecialchars($room['Id']); ?></p>
                </div>
                <div class="room-price">
                    <strong>$<?php echo htmlspecialchars($room['PrecioNoche']); ?></strong>
                    <p style="color: #999;">Precio por noche</p>
                    <a href="reserva_proceso.php?hotel_id=<?php echo $hotel['Id']; ?>&room_type_id=<?php echo $room['Id']; ?>" class="btn-select">
                        Seleccionar
                    </a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

    </div>
</body>
</html>