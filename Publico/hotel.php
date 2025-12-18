<?php
session_start();
// La ruta de conexión es correcta
include('../Config/conexion.php'); 

// 1. Obtener y validar el ID del hotel
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;

if ($hotel_id <= 0) {
    header("Location: ../Cliente/index.php?error=Hotel+no+especificado");
    exit();
}

// 2. OBTENER LAS FECHAS DEL INDEX
$check_in  = $_GET['check_in'] ?? null;
$check_out = $_GET['check_out'] ?? null;

// Validar que se hayan recibido fechas y que sean válidas (Check-out > Check-in)
if (!$check_in || !$check_out || $check_in >= $check_out) {
    // En caso de fechas inválidas, redirigimos al index
    header("Location: ../Cliente/index.php?error=Selecciona+fechas+validas");
    exit();
}

// --- FUNCIONES PARA OBTENER DATOS DE LA BBDD (Omitidas por brevedad, no cambian) ---

function obtenerDetallesHotel($conn, $id) {
    $sql = "SELECT Id, Name, City, Address FROM Hotels WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $hotel = $resultado->fetch_assoc();
    $stmt->close();
    return $hotel;
}

/**
 * Función para obtener los tipos de habitación disponibles
 * para el rango de fechas SOLICITADO.
 */
function obtenerTiposHabitacionDisponiblesPorHotelYFecha($conn, $hotelId, $checkIn, $checkOut) {
    
    // El SQL tiene 3 placeholders: HotelId, CheckOut, CheckIn.
    $sql = "SELECT 
                rt.Id AS Id_RoomType,
                rt.Name,
                rt.Guests,
                rt.CostPerNight,
                (
                    SELECT COUNT(R.Id)
                    FROM Rooms R
                    WHERE R.Id_RoomType = rt.Id
                      AND R.Id_Hotel = ?             /* <-- Placeholder 1: Filtro por Hotel */
                      AND R.Available = 1            /* <-- Habitación Operativa */
                      AND R.Id NOT IN (
                            SELECT rr.Id_Room
                            FROM Reservation_Rooms rr
                            JOIN Reservation res ON rr.Id_Reservation = res.Id
                            WHERE res.CheckIn_Date < ? /* <-- Placeholder 2: CheckOut */
                              AND res.CheckOut_Date > ? /* <-- Placeholder 3: CheckIn */
                              AND res.Status = 'Confirmada'
                      )
                ) AS AvailableRooms
            FROM RoomType rt
            HAVING AvailableRooms > 0
            ORDER BY rt.CostPerNight ASC";


    $stmt = $conn->prepare($sql);
    
    // BINDEO: Orden crítico (1: i, 2: s, 3: s)
    $stmt->bind_param("iss", $hotelId, $checkOut, $checkIn);
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    $tipos = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $tipos;
}


// --- EJECUCIÓN ---

$hotel = obtenerDetallesHotel($conn, $hotel_id);

if (!$hotel) {
    header("Location: ../Cliente/index.php?error=Hotel+no+encontrado");
    exit();
}

// *** APLICACIÓN DEL FORMATO ESPAÑOL ***
try {
    $check_in_es = (new DateTime($check_in))->format('d/m/Y');
    $check_out_es = (new DateTime($check_out))->format('d/m/Y');
} catch (Exception $e) {
    // Si la conversión falla (por seguridad, aunque la validación anterior debería evitarlo)
    $check_in_es = $check_in;
    $check_out_es = $check_out;
}

$tiposHabitacion = obtenerTiposHabitacionDisponiblesPorHotelYFecha($conn, $hotel_id, $check_in, $check_out);

$ciudad = $hotel['City'];

$habitacionesDisponibles = [];

// Aplicar la lógica de precios y reformatear
foreach ($tiposHabitacion as $tipo) {
    $tipo['PrecioNoche'] = number_format($tipo['CostPerNight'], 2, '.', '');
    $habitacionesDisponibles[] = $tipo;
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
    <link rel="stylesheet" href="../styleCarlos.css">
    <style>
        /* ... (Estilos CSS) ... */
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
        /* Estilo para mostrar las fechas de la reserva */
        .date-filter-info {
            background: #f0f0f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.1em;
            color: var(--color-dark);
            border: 1px solid #e0e0e5;
        }
        .date-filter-info strong {
            color: var(--color-primary);
            font-weight: 700;
        }
    </style>
</head>
<body>

    <nav style="background:#a02040; color:white; padding:15px; text-align:center; position:fixed; top:0; width:100%; z-index:1000;">
        <a href="../Cliente/index.php" style="color:white; text-decoration:none; font-size:1.5em; font-weight:bold;">← Volver a Hoteles</a>
        <span style="float:right; margin-right: 20px; font-size:1.1em;">
            <?php echo $is_logged_in ? "Bienvenido, " . $user_name : '<a href="auth/login.php" style="color:white;">Iniciar Sesión</a>'; ?>
        </span>
    </nav>

    <div class="container">
        <div class="hotel-header">
            <h1><?php echo htmlspecialchars($hotel['Name']); ?></h1>
            <p class="hotel-info">📍 <strong><?php echo htmlspecialchars($ciudad); ?></strong> | Dirección: <?php echo htmlspecialchars($hotel['Address']); ?></p>
        </div>
        
        <div class="date-filter-info">
            Buscando habitaciones entre <strong><?php echo htmlspecialchars($check_in_es); ?></strong> y <strong><?php echo htmlspecialchars($check_out_es); ?></strong>.
        </div>

        <h2>Tipos de Habitación Disponibles</h2>

        <ul class="room-listing">
            <?php if (count($habitacionesDisponibles) === 0): ?>
                <li>
                    <div style="padding: 20px; text-align: center; color: #dc3545; font-weight: bold; background: #fff3f3; border-radius: 8px;">
                        ⚠️ Lo sentimos, no hay habitaciones disponibles para el rango de fechas seleccionado.
                        <p style="font-size:0.9em; font-weight:normal; margin-top:10px;">
                            Por favor, vuelve al listado de hoteles para seleccionar otras fechas.
                        </p>
                    </div>
                </li>
            <?php else: ?>
                <?php foreach ($habitacionesDisponibles as $room): ?>
                <li class="room-card">
                    <div class="room-details">
                        <h3><?php echo htmlspecialchars($room['Name']); ?></h3>
                        <p>Máximo de Huéspedes: <strong><?php echo htmlspecialchars($room['Guests']); ?></strong></p>
                        <p style="color: var(--color-dark);">Habitaciones libres: <strong><?php echo (int)$room['AvailableRooms']; ?></strong></p>
                    </div>
                    <div class="room-price">
                        <strong>$<?php echo htmlspecialchars($room['PrecioNoche']); ?></strong>
                        <p style="color: #999;">Precio por noche</p>

                        <a href="../Cliente/booking/booking_form.php?hotel_id=<?php echo $hotel['Id']; ?>&room_type_id=<?php echo $room['Id_RoomType']; ?>&check_in=<?php echo urlencode($check_in); ?>&check_out=<?php echo urlencode($check_out); ?>&price=<?php echo $room['PrecioNoche']; ?>" class="btn-select">
                            Seleccionar
                        </a>
                    </div>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

    </div>
</body>
</html>