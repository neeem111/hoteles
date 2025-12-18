<?php
session_start();

// Validaciones iniciales
if (empty($_SESSION['cart'])) {
    
    // --- NUEVA LÓGICA DE DETECCIÓN DE RETORNO DE PAGO ---
    // Si el carrito está vacío PERO hay una variable de retorno de pago (tx, st, o custom success)
    // Asumimos que la reserva fue procesada por PayPal en un script externo (como PayPal IPN)
    // o que el usuario está volviendo de un pago.
    
    // Si hay parámetros de éxito de PayPal en la URL, forzamos la redirección a confirmación.
    if (isset($_GET['tx']) || isset($_GET['st']) || (isset($_GET['payment_method']) && $_GET['payment_method'] === 'card_successful')) {
        
        // El problema de que te saca de la sesión es crítico y requiere un re-login forzado.
        // Si pierdes la sesión, no podemos usar $_SESSION['last_reservations'].
        // Pero si el usuario ya ha iniciado sesión antes, al menos podemos llevarlo al index.
        
        // Si el usuario está logueado al llegar aquí (el carrito se procesó por IPN/PDT)
        if (isset($_SESSION['user_id'])) {
            // No podemos saber el ID de las últimas reservas si el proceso fue externo,
            // pero podemos redirigir al listado general de pedidos.
            // Necesitas implementar un script para recuperar la última factura/reserva
            // basándote en el user_id y la fecha (muy complejo).
            
            // --- SOLUCIÓN TEMPORAL MÁS SEGURA ---
            // Simplemente redirigimos al usuario al listado de pedidos para que vea su nuevo ítem.
            // Asume que la reserva se creó correctamente y se le dará un mensaje genérico.
            $_SESSION['cart_success'] = 'Pago CONFIRMADO. Puede ver los detalles en su historial de pedidos.';
            header('Location: ../mis_pedidos.php'); 
            exit;
        } else {
            // Si la sesión se perdió y no podemos confirmar el pago, la única opción es index.
             $_SESSION['cart_error'] = 'Sesión expirada. Por favor, inicia sesión para verificar el pago.';
             header('Location: ../../auth/login.php');
             exit;
        }
    }
    // Si el carrito está vacío y no hay variables de pago, es un acceso normal y lo marcamos como error.
    $_SESSION['cart_error'] = 'Tu carrito está vacío.';
    header('Location: view_cart.php');
    exit;
}
// --- FIN LÓGICA DE DETECCIÓN DE RETORNO DE PAGO ---


if (!isset($_SESSION['user_id'])) {
    // Redirigir al usuario si no está logueado
    $_SESSION['redirect_after_login'] = 'Cliente/cart/view_cart.php';
    header("Location: ../../auth/login.php?error=Debes+iniciar+sesion+para+confirmar+la+reserva");
    exit;
}

include('../../conexion.php');

$cart   = $_SESSION['cart'];
$userId = (int)$_SESSION['user_id'];

$createdReservations = [];

// --- Detección del método de pago (Ajustada para el nuevo retorno de PayPal) ---
// NOTA: Esta sección solo se ejecuta si el carrito NO está vacío.
// Si el carrito SÍ está vacío, la lógica de arriba se encarga.

// 1. Detección de pago con tarjeta (simulación que viene de view_cart.php)
if (isset($_GET['payment_method']) && $_GET['payment_method'] === 'card_successful') {
    $paymentMethodGlobal = 'Tarjeta Crédito';
} 
// 2. Detección de pago con PayPal (retorno simulado o real con ID de transacción)
// La forma en que PayPal se comporta depende de la configuración de IPN/PDT. 
// Para una simulación simple que proviene de PayPal, lo mejor es usar la variable `return`.
elseif (isset($_GET['tx']) || (isset($_POST['payment_status']) && $_POST['payment_status'] === 'Completed')) {
    $paymentMethodGlobal = 'PayPal';
}
// 3. Fallback, asumimos que si el carrito tiene ítems, el usuario acaba de pasar por la pantalla de pago de PayPal/Tarjeta.
// Si el usuario llega aquí sin método de pago en la URL pero con el carrito lleno, lo forzamos.
else {
     // Si no hay tarjeta en la URL, asumimos que viene de PayPal (ya que no viene de la simulación de tarjeta).
     $paymentMethodGlobal = 'PayPal';
}

// ----------------------------------------------------


// Iniciar transacción para asegurar la integridad de la base de datos
$conn->begin_transaction();

try {
    foreach ($cart as $hotelId => $item) {

        $hotelId      = (int)$item['hotel_id'];
        $roomTypeId   = isset($item['room_type_id']) ? (int)$item['room_type_id'] : 0;
        $check_in     = $item['check_in']  ?? null;
        $check_out    = $item['check_out'] ?? null;
        $nights       = isset($item['nights']) ? (int)$item['nights'] : 1;
        $roomsCount   = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
        $priceNight   = isset($item['precio']) ? (float)$item['precio'] : 0.0;
        $notes        = $item['notes'] ?? '';

        if ($hotelId <= 0 || $roomTypeId <= 0 || !$check_in || !$check_out) {
            throw new Exception('Datos incompletos en el carrito.');
        }

        // --- MANEJO DE FECHAS PARA BBDD Y VISUALIZACIÓN ---
        $check_in_es = (new DateTime($check_in))->format('d/m/Y');
        $check_out_es = (new DateTime($check_out))->format('d/m/Y');


        // 1. Crear Reserva
        $booking_date = date('Y-m-d');
        $status       = 'Confirmada';

        $sqlReserva = "INSERT INTO Reservation 
             (Id_User, CheckIn_Date, CheckOut_Date, Num_Nights, Booking_date, Status)
             VALUES (?, ?, ?, ?, ?, ?)";

        $stmtRes = $conn->prepare($sqlReserva);
        $stmtRes->bind_param("ississ", $userId, $check_in, $check_out, $nights, $booking_date, $status);
        $stmtRes->execute();
        $reservationId = $stmtRes->insert_id;
        $stmtRes->close();

        // 2. Asignar Habitaciones (La lógica de Available=1 ya está corregida)
        $sqlRoom = "SELECT Id FROM Rooms WHERE Id_Hotel = ? AND Id_RoomType = ? AND Available = 1 LIMIT ?";
        $stmtRoom = $conn->prepare($sqlRoom);
        $stmtRoom->bind_param("iii", $hotelId, $roomTypeId, $roomsCount);
        $stmtRoom->execute();
        $resRoom = $stmtRoom->get_result();

        $roomsFound = [];
        while ($rowR = $resRoom->fetch_assoc()) {
            $roomsFound[] = (int)$rowR['Id'];
        }
        $stmtRoom->close();

        if (count($roomsFound) < $roomsCount) {
            throw new Exception('No hay suficientes habitaciones disponibles.');
        }

        foreach ($roomsFound as $roomId) {
            $conn->query("INSERT INTO Reservation_Rooms (Id_Reservation, Id_Room) VALUES ($reservationId, $roomId)");
            // NOTA: Available = 1 se mantiene, no se actualiza aquí.
        }

        // --- 3. GENERAR FACTURA ---
        
        // Cálculos
        $baseImponible = $priceNight * $nights * $roomsCount;
        $ivaPorcentaje = 0.21; 
        $ivaCantidad   = $baseImponible * $ivaPorcentaje;
        $totalFactura  = $baseImponible + $ivaCantidad;
        
        $invoiceNumber = 'FACT-' . date('Ymd') . '-' . $reservationId;
        $invoiceDate   = date('Y-m-d');
        $invoiceStatus = 'Pagada';

        // Insertar Factura (Usa $paymentMethodGlobal)
        $sqlInv = "INSERT INTO Invoices (Id_Reservation, Id_User, InvoiceNumber, Date, Subtotal, IVA, Total, PaymentMethod, Status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInv = $conn->prepare($sqlInv);
        $stmtInv->bind_param("iisssddss", $reservationId, $userId, $invoiceNumber, $invoiceDate, $baseImponible, $ivaCantidad, $totalFactura, $paymentMethodGlobal, $invoiceStatus);
        $stmtInv->execute();
        
        $invoiceId = $stmtInv->insert_id; 
        $stmtInv->close();

        // Insertar Detalle Factura
        $descItem = "Estancia Hotel ($nights noches)";
        $qtyItem  = 1;
        $sqlItem = "INSERT INTO InvoiceItems (Id_Invoice, Description, Quantity, UnitPrice, Total) VALUES (?, ?, ?, ?, ?)";
        $stmtItem = $conn->prepare($sqlItem);
        $stmtItem->bind_param("isidd", $invoiceId, $descItem, $qtyItem, $baseImponible, $baseImponible);
        $stmtItem->execute();
        $stmtItem->close();

        // --- FIN GENERACIÓN FACTURA ---

        // Obtener nombre del hotel
        $hotelName = 'Hotel #' . $hotelId;
        $resH = $conn->query("SELECT Name FROM Hotels WHERE Id = $hotelId");
        if ($rowH = $resH->fetch_assoc()) $hotelName = $rowH['Name'];

        // Guardamos los datos para confirmation.php con el FORMATO ESPAÑOL
        $createdReservations[] = [
            'reservation_id' => $reservationId,
            'invoice_id'     => $invoiceId,
            'hotel_name'     => $hotelName,
            'check_in'       => $check_in_es,
            'check_out'      => $check_out_es,
            'nights'         => $nights,
            'total'          => $totalFactura
        ];
    }

    $conn->commit();
    $_SESSION['last_reservations'] = $createdReservations;
    unset($_SESSION['cart']);

    header('Location: confirmation.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['cart_error'] = 'Error al procesar la reserva: ' . $e->getMessage();
    header('Location: view_cart.php');
    exit;
}
?>