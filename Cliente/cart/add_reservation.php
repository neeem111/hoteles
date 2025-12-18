<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Debe estar logueado para añadir al carrito
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '../index.php';
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

// --- INICIO DE VALIDACIÓN DE FECHAS CRÍTICA ---
try {
    $checkInDate  = new DateTime($check_in);
    $checkOutDate = new DateTime($check_out);
    
    // 1. Validar que la salida sea estrictamente posterior a la entrada
    if ($checkOutDate <= $checkInDate) {
        $_SESSION['cart_error'] = 'La fecha de salida debe ser posterior a la fecha de entrada. Por favor, revisa tus fechas.';
        header('Location: ../index.php');
        exit;
    }
    
    // 2. Calcular la diferencia (si la validación anterior pasa)
    $diff         = $checkInDate->diff($checkOutDate);
    $nights       = (int)$diff->days;
    
} catch (Exception $e) {
    // Esto captura errores si el formato de fecha es totalmente irreconocible
    $_SESSION['cart_error'] = 'Fechas inválidas o con formato incorrecto.';
    header('Location: ../index.php');
    exit;
}


// Inicializar carrito si no existe
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
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

        // Si es otro tipo/fechas, sobreescribimos (simplificación para el proyecto)
        // NOTA: En un proyecto real, esto debería ser un nuevo ítem en el carrito,
        // usando una clave compuesta, no sobrescribiendo el hotel completo.
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