<?php
session_start();

// Validaciones
if (empty($_SESSION['cart'])) {
  $_SESSION['cart_error'] = 'Tu carrito está vacío.';
  header('Location: view_cart.php');
  exit;
}

if (!isset($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = 'cart/view_cart.php';
  header("Location: ../login.php?error=Debes+iniciar+sesion+para+confirmar+la+reserva");
  exit;
}

include('../conexion.php');

$cart   = $_SESSION['cart'];
$userId = (int)$_SESSION['user_id'];

$createdReservations = [];

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

    // 2. Asignar Habitaciones
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
      $conn->query("UPDATE Rooms SET Available = 0 WHERE Id = $roomId");
    }

    // --- 3. GENERAR FACTURA (ESTO ES LO QUE TE FALTABA) ---
    
    // Cálculos
    $baseImponible = $priceNight * $nights * $roomsCount;
    $ivaPorcentaje = 0.21; 
    $ivaCantidad   = $baseImponible * $ivaPorcentaje;
    $totalFactura  = $baseImponible + $ivaCantidad;
    
    $invoiceNumber = 'FACT-' . date('Ymd') . '-' . $reservationId;
    $invoiceDate   = date('Y-m-d');
    $paymentMethod = 'Tarjeta Crédito'; 
    $invoiceStatus = 'Pagada';

    // Insertar Factura
    $sqlInv = "INSERT INTO Invoices (Id_Reservation, Id_User, InvoiceNumber, Date, Subtotal, IVA, Total, PaymentMethod, Status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtInv = $conn->prepare($sqlInv);
    $stmtInv->bind_param("iisssddss", $reservationId, $userId, $invoiceNumber, $invoiceDate, $baseImponible, $ivaCantidad, $totalFactura, $paymentMethod, $invoiceStatus);
    $stmtInv->execute();
    
    // ¡IMPORTANTE! Guardamos el ID de la factura recién creada
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

    // Guardamos los datos para confirmation.php
    $createdReservations[] = [
      'reservation_id' => $reservationId,
      'invoice_id'     => $invoiceId, // AQUI se pasa el ID al siguiente paso
      'hotel_name'     => $hotelName,
      'check_in'       => $check_in,
      'check_out'      => $check_out,
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
  $_SESSION['cart_error'] = 'Error: ' . $e->getMessage();
  header('Location: view_cart.php');
  exit;
}
?>