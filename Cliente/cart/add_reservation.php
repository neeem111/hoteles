<?php
session_start();
include('../../Config/conexion.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Debe estar logueado para añadir al carrito
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '../index.php';
    header("Location: ../../auth/login.php?error=Debes+iniciar+sesion+para+añadir+al+carrito");
    exit;
}

$hotel_id        = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
$room_type_id    = isset($_POST['room_type_id']) ? intval($_POST['room_type_id']) : 0;
$price_per_night = isset($_POST['price_per_night']) ? floatval($_POST['price_per_night']) : 0;
$check_in        = trim($_POST['check_in'] ?? '');
$check_out       = trim($_POST['check_out'] ?? '');
$notes           = trim($_POST['notes'] ?? '');
$num_rooms       = isset($_POST['num_rooms']) ? intval($_POST['num_rooms']) : 1;

// Validación básica de campos requeridos
if ($hotel_id <= 0 || $room_type_id <= 0 || $price_per_night <= 0 || $check_in === '' || $check_out === '' || $num_rooms <= 0) {
    $_SESSION['cart_error'] = 'Datos incompletos al añadir la habitación al carrito.';
    header('Location: ../index.php');
    exit;
}

// --- VALIDACIÓN DE FECHAS ---
try {
    $checkInDate  = new DateTime($check_in);
    $checkOutDate = new DateTime($check_out);

    if ($checkOutDate <= $checkInDate) {
        $_SESSION['cart_error'] = 'La fecha de salida debe ser posterior a la fecha de entrada. Por favor, revisa tus fechas.';
        header('Location: ../index.php');
        exit;
    }

    $diff   = $checkInDate->diff($checkOutDate);
    $nights = (int)$diff->days;

} catch (Exception $e) {
    $_SESSION['cart_error'] = 'Fechas inválidas o con formato incorrecto.';
    header('Location: ../index.php');
    exit;
}

// Inicializar carrito si no existe
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Calcula la disponibilidad REAL por rango de fechas:
 * disponibles = (Rooms habilitadas) - (Rooms ocupadas por reservas confirmadas que se solapen)
 */
function get_available_rooms($conn, $hotel_id, $room_type_id, $check_in, $check_out) {
    // Total de rooms físicas habilitadas
    $sqlTotal = "SELECT COUNT(*) AS total
                 FROM Rooms
                 WHERE Id_Hotel = ? AND Id_RoomType = ? AND Available = 1";
    $stmtT = $conn->prepare($sqlTotal);
    $stmtT->bind_param("ii", $hotel_id, $room_type_id);
    $stmtT->execute();
    $total = (int)$stmtT->get_result()->fetch_assoc()['total'];
    $stmtT->close();

    // Rooms ocupadas por reservas confirmadas que se solapen con el rango
    $sqlBooked = "
        SELECT COUNT(DISTINCT rr.Id_Room) AS booked
        FROM Reservation res
        INNER JOIN Reservation_Rooms rr ON rr.Id_Reservation = res.Id
        INNER JOIN Rooms r ON r.Id = rr.Id_Room
        WHERE r.Id_Hotel = ?
          AND r.Id_RoomType = ?
          AND r.Available = 1
          AND res.Status = 'Confirmada'
          AND res.CheckIn_Date < ?
          AND res.CheckOut_Date > ?
    ";
    $stmtB = $conn->prepare($sqlBooked);
    $stmtB->bind_param("iiss", $hotel_id, $room_type_id, $check_out, $check_in);
    $stmtB->execute();
    $booked = (int)$stmtB->get_result()->fetch_assoc()['booked'];
    $stmtB->close();

    $available = $total - $booked;
    if ($available < 0) $available = 0;

    return $available;
}

// --- VALIDACIÓN CRÍTICA: cantidad solicitada vs disponibilidad ---
$current_in_cart = 0;

// Si ya existe el mismo hotel con mismo tipo y mismas fechas, sumaría
if (isset($_SESSION['cart'][$hotel_id])) {
    $item = $_SESSION['cart'][$hotel_id];

    if (
        isset($item['room_type_id'], $item['check_in'], $item['check_out'], $item['cantidad']) &&
        $item['room_type_id'] == $room_type_id &&
        $item['check_in'] === $check_in &&
        $item['check_out'] === $check_out
    ) {
        $current_in_cart = (int)$item['cantidad'];
    }
}

$requested_total = $current_in_cart + $num_rooms;

$available = get_available_rooms($conn, $hotel_id, $room_type_id, $check_in, $check_out);

if ($requested_total > $available) {
    $_SESSION['cart_error'] = "Solo hay $available habitación(es) disponible(s) para ese rango de fechas. En tu carrito ya tienes $current_in_cart.";
    // Regresa al booking_form para que el usuario ajuste
    header("Location: ../booking/booking_form.php?hotel_id=$hotel_id&room_type_id=$room_type_id&check_in=$check_in&check_out=$check_out");
    exit;
}

/**
 * Si ya existe el mismo hotel con el mismo tipo y fechas, se suman habitaciones.
 */
if (isset($_SESSION['cart'][$hotel_id])) {
    $item = &$_SESSION['cart'][$hotel_id];

    // Si mismo tipo y mismas fechas → sumamos habitaciones
    if (
        isset($item['room_type_id'], $item['check_in'], $item['check_out']) &&
        $item['room_type_id'] == $room_type_id &&
        $item['check_in'] === $check_in &&
        $item['check_out'] === $check_out
    ) {
        $item['cantidad'] += $num_rooms;
    } else {
        // Si es otro tipo/fechas, sobreescribimos (tu simplificación actual)
        $item = [
            'hotel_id'     => $hotel_id,
            'precio'       => $price_per_night,
            'nights'       => $nights,
            'cantidad'     => $num_rooms,
            'check_in'     => $check_in,
            'check_out'    => $check_out,
            'room_type_id' => $room_type_id,
            'notes'        => $notes
        ];
    }
} else {
    // Nuevo hotel en el carrito
    $_SESSION['cart'][$hotel_id] = [
        'hotel_id'     => $hotel_id,
        'precio'       => $price_per_night,
        'nights'       => $nights,
        'cantidad'     => $num_rooms,
        'check_in'     => $check_in,
        'check_out'    => $check_out,
        'room_type_id' => $room_type_id,
        'notes'        => $notes
    ];
}

$_SESSION['cart_success'] = 'Habitación(es) añadida(s) al carrito.';
header('Location: view_cart.php');
exit;
