<?php
session_start();
include('conexion.php');

// --- 1. Comprobar que el usuario ha iniciado sesión ---
if (!isset($_SESSION['user_id'])) {
    // Guardamos a dónde quería ir el usuario
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

    header("Location: login.php?msg=Debes+iniciar+sesion+para+reservar");
    exit();
}

// --- 2. Obtener y validar parámetros GET ---
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_type_id = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;

if ($hotel_id <= 0 || $room_type_id <= 0) {
    header("Location: index.php?error=Datos+de+hotel+o+habitacion+no+validos");
    exit();
}

// --- 3. Funciones auxiliares ---

function obtenerDetallesHotel($conn, $id) {
    $sql = "SELECT Id, Name, City, Address FROM Hotels WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->fetch_assoc();
}

function obtenerTipoHabitacionPorId($conn, $roomTypeId) {
    $sql = "SELECT Id, Name, Guests, CostPerNight 
            FROM RoomType 
            WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roomTypeId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->fetch_assoc();
}

// (Opcional) contar cuántas habitaciones de ese tipo hay en ese hotel
function contarHabitacionesDisponibles($conn, $hotelId, $roomTypeId) {
    $sql = "SELECT COUNT(*) AS total
            FROM Rooms
            WHERE Id_Hotel = ? AND Id_RoomType = ? AND Available = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $hotelId, $roomTypeId);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    return (int)$resultado['total'];
}

// --- 4. Obtener datos de BBDD ---

$hotel = obtenerDetallesHotel($conn, $hotel_id);
$roomType = obtenerTipoHabitacionPorId($conn, $room_type_id);
$maxHabitaciones = contarHabitacionesDisponibles($conn, $hotel_id, $room_type_id);

if (!$hotel || !$roomType) {
    header("Location: index.php?error=Hotel+o+tipo+de+habitacion+no+encontrado");
    exit();
}

$precioPorNoche = number_format($roomType['CostPerNight'], 2, '.', '');
$ciudad = $hotel['City'];

// Datos de usuario desde la sesión (si los guardaste ahí)
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar reserva - <?php echo htmlspecialchars($hotel['Name']); ?></title>
    <link rel="stylesheet" href="styleCarlos.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 100px auto 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #a02040;
        }
        .resumen, .form-reserva {
            margin-bottom: 25px;
        }
        .resumen-item {
            margin-bottom: 8px;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        .btn-primary {
            margin-top: 20px;
            background-color: #a02040;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #801933;
        }
        .small-text {
            font-size: 0.9em;
            color: #666;
        }
        .nav {
            background:#a02040; 
            color:white; 
            padding:15px; 
            text-align:center; 
            position:fixed; 
            top:0; 
            width:100%; 
            z-index:1000;
        }
        .nav a {
            color:white; 
            text-decoration:none; 
            font-size:1.2em; 
            font-weight:bold;
        }
    </style>
</head>
<body>

<nav class="nav">
    <a href="hotel.php?hotel_id=<?php echo $hotel['Id']; ?>">← Volver al hotel</a>
</nav>

<div class="container">
    <h1>Confirmar reserva</h1>

    <div class="resumen">
        <h2>Resumen de la selección</h2>
        <p class="resumen-item"><strong>Hotel:</strong> <?php echo htmlspecialchars($hotel['Name']); ?> (<?php echo htmlspecialchars($ciudad); ?>)</p>
        <p class="resumen-item"><strong>Tipo de habitación:</strong> <?php echo htmlspecialchars($roomType['Name']); ?></p>
        <p class="resumen-item"><strong>Capacidad máxima:</strong> <?php echo (int)$roomType['Guests']; ?> huésped(es)</p>
        <p class="resumen-item"><strong>Precio por noche:</strong> <?php echo $precioPorNoche; ?> €</p>
        <p class="resumen-item small-text">
            Habitaciones disponibles de este tipo en este hotel: <?php echo $maxHabitaciones; ?>
        </p>
    </div>

    <div class="form-reserva">
        <h2>Datos de la reserva</h2>
        <form action="reserva_proceso.php" method="POST">
            <!-- IDs ocultos que necesitamos para crear la reserva -->
            <input type="hidden" name="hotel_id" value="<?php echo (int)$hotel['Id']; ?>">
            <input type="hidden" name="room_type_id" value="<?php echo (int)$roomType['Id']; ?>">

            <!-- Datos del usuario (solo lectura, para que vea que se usará su cuenta) -->
            <label>Nombre del cliente</label>
            <input type="text" value="<?php echo htmlspecialchars($user_name); ?>" disabled>

            <label>Email del cliente</label>
            <input type="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>

            <!-- Fechas -->
            <label for="check_in">Fecha de entrada</label>
            <input type="date" id="check_in" name="check_in" required>

            <label for="check_out">Fecha de salida</label>
            <input type="date" id="check_out" name="check_out" required>

            <!-- Número de habitaciones y huéspedes -->
            <label for="num_rooms">Número de habitaciones</label>
            <input 
                type="number" 
                id="num_rooms" 
                name="num_rooms" 
                min="1" 
                max="<?php echo max(1, $maxHabitaciones); ?>" 
                value="1"
                required
            >

            <label for="num_guests">Número total de huéspedes</label>
            <input type="number" id="num_guests" name="num_guests" min="1" required>

            <!-- Comentarios / peticiones especiales -->
            <label for="comments">Peticiones especiales (opcional)</label>
            <textarea id="comments" name="comments" placeholder="Ej: Cuna para bebé, piso alto, etc."></textarea>

            <!-- Método de pago (solo tipo, sin datos sensibles) -->
            <label for="payment_method">Método de pago</label>
            <select id="payment_method" name="payment_method" required>
                <option value="">Selecciona una opción</option>
                <option value="Tarjeta">Tarjeta</option>
                <option value="PayPal">PayPal</option>
                <option value="Efectivo en recepción">Efectivo en recepción</option>
            </select>
            <p class="small-text">
                *No se guardan datos sensibles de la tarjeta, solo el método elegido para la factura.
            </p>

            <button type="submit" class="btn-primary">Confirmar reserva</button>
        </form>
    </div>
</div>

</body>
</html>
