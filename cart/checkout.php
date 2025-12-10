<?php
session_start();

// Debe haber carrito
if (empty($_SESSION['cart'])) {
  $_SESSION['cart_error'] = 'Tu carrito está vacío.';
  header('Location: view_cart.php');
  exit;
}

// Debe estar logueado
if (!isset($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = 'cart/view_cart.php';
  header("Location: ../login.php?error=Debes+iniciar+sesion+para+confirmar+la+reserva");
  exit;
}

include('../conexion.php');

$cart   = $_SESSION['cart'];
$userId = (int)$_SESSION['user_id'];

// Array donde guardaremos info para la pantalla de confirmación
$createdReservations = [];

$conn->begin_transaction();

try {
  foreach ($cart as $hotelId => $item) {

    $hotelId      = (int)$item['hotel_id'];
    $roomTypeId   = isset($item['room_type_id']) ? (int)$item['room_type_id'] : 0;
    $check_in     = $item['check_in']  ?? null;
    $check_out    = $item['check_out'] ?? null;
    $nights       = isset($item['cantidad']) ? (int)$item['cantidad'] : 0;
    $priceNight   = isset($item['precio']) ? (float)$item['precio'] : 0.0;
    $notes        = $item['notes'] ?? '';

    if ($hotelId <= 0 || $roomTypeId <= 0 || !$check_in || !$check_out || $nights <= 0) {
      throw new Exception('Datos incompletos en el carrito para uno de los hoteles.');
    }

    // 1. Insertar en Reservation
    $booking_date = date('Y-m-d');
    $status       = 'Confirmada';

    $sqlReserva = "INSERT INTO Reservation 
            (Id_User, CheckIn_Date, CheckOut_Date, Num_Nights, Booking_date, Status)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmtRes = $conn->prepare($sqlReserva);
    if (!$stmtRes) {
      throw new Exception('Error al preparar la inserción de la reserva.');
    }
    $stmtRes->bind_param("ississ", $userId, $check_in, $check_out, $nights, $booking_date, $status);
    $stmtRes->execute();

    if ($stmtRes->affected_rows <= 0) {
      throw new Exception('No se pudo crear la reserva en la base de datos.');
    }

    $reservationId = $stmtRes->insert_id;
    $stmtRes->close();

    // 2. Buscar UNA habitación disponible de ese hotel y tipo
    // nº de habitaciones solicitadas en el carrito
    $roomsCount = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
    if ($roomsCount <= 0) {
      $roomsCount = 1;
    }

    // 2. Buscar suficientes habitaciones disponibles de ese hotel y tipo
    $sqlRoom = "SELECT Id 
            FROM Rooms
            WHERE Id_Hotel = ?
              AND Id_RoomType = ?
              AND Available = 1
            LIMIT ?";
    $stmtRoom = $conn->prepare($sqlRoom);
    if (!$stmtRoom) {
      throw new Exception('Error al preparar la búsqueda de habitaciones.');
    }
    $stmtRoom->bind_param("iii", $hotelId, $roomTypeId, $roomsCount);
    $stmtRoom->execute();
    $resRoom = $stmtRoom->get_result();

    $roomsFound = [];
    while ($rowR = $resRoom->fetch_assoc()) {
      $roomsFound[] = (int)$rowR['Id'];
    }
    $stmtRoom->close();

    if (count($roomsFound) < $roomsCount) {
      throw new Exception('No hay suficientes habitaciones disponibles para uno de los tipos seleccionados.');
    }

    // 3. Insertar en Reservation_Rooms y marcar cada habitación como no disponible
    foreach ($roomsFound as $roomId) {
      // Enlace reserva-habitación
      $sqlRR = "INSERT INTO Reservation_Rooms (Id_Reservation, Id_Room) VALUES (?, ?)";
      $stmtRR = $conn->prepare($sqlRR);
      if (!$stmtRR) {
        throw new Exception('Error al preparar la inserción en Reservation_Rooms.');
      }
      $stmtRR->bind_param("ii", $reservationId, $roomId);
      $stmtRR->execute();
      $stmtRR->close();

      // Habitación pasa a no disponible
      $sqlUp = "UPDATE Rooms SET Available = 0 WHERE Id = ?";
      $stmtUp = $conn->prepare($sqlUp);
      if (!$stmtUp) {
        throw new Exception('Error al preparar la actualización de habitaciones.');
      }
      $stmtUp->bind_param("i", $roomId);
      $stmtUp->execute();
      $stmtUp->close();
    }


    // 5. Obtener nombre del hotel para el resumen
    $hotelName = 'Hotel #' . $hotelId;
    $sqlHotel = "SELECT Name FROM Hotels WHERE Id = ?";
    $stmtH = $conn->prepare($sqlHotel);
    if ($stmtH) {
      $stmtH->bind_param("i", $hotelId);
      $stmtH->execute();
      $resH = $stmtH->get_result();
      if ($rowH = $resH->fetch_assoc()) {
        $hotelName = $rowH['Name'];
      }
      $stmtH->close();
    }

    // Precio total de esta reserva (hotel)
    $totalPrice = $priceNight * $nights;

    $createdReservations[] = [
      'reservation_id' => $reservationId,
      'hotel_name'     => $hotelName,
      'check_in'       => $check_in,
      'check_out'      => $check_out,
      'nights'         => $nights,
      'total'          => $totalPrice,
      'notes'          => $notes
    ];
  }

  // Si todo va bien, confirmamos
  $conn->commit();

  // Guardamos el resumen en sesión para mostrarlo en la pantalla final
  $_SESSION['last_reservations'] = $createdReservations;

  // Vaciamos el carrito
  unset($_SESSION['cart']);

  // Redirigimos a la pantalla de confirmación
  header('Location: confirmation.php');
  exit;
} catch (Exception $e) {
  $conn->rollback();
  $_SESSION['cart_error'] = 'No se pudo completar la reserva: ' . $e->getMessage();
  header('Location: view_cart.php');
  exit;
}
