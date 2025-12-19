<?php
session_start();

// Validaciones iniciales
if (empty($_SESSION['cart'])) {

    if (isset($_GET['tx']) || isset($_GET['st']) || (isset($_GET['payment_method']) && $_GET['payment_method'] === 'card_successful')) {

        if (isset($_SESSION['user_id'])) {
            $_SESSION['cart_success'] = 'Pago CONFIRMADO. Puede ver los detalles en su historial de pedidos.';
            header('Location: ../mis_pedidos.php'); 
            exit;
        } else {
            $_SESSION['cart_error'] = 'Sesión expirada. Por favor, inicia sesión para verificar el pago.';
            header('Location: ../../auth/login.php');
            exit;
        }
    }

    $_SESSION['cart_error'] = 'Tu carrito está vacío.';
    header('Location: view_cart.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'view_cart.php';
    header("Location: ../../auth/login.php?error=Debes+iniciar+sesion+para+confirmar+la+reserva");
    exit;
}

include('../../Config/conexion.php');

$cart   = $_SESSION['cart'];
$userId = (int)$_SESSION['user_id'];

$createdReservations = [];

// --- Detección del método de pago ---
if (isset($_GET['payment_method']) && $_GET['payment_method'] === 'card_successful') {
    $paymentMethodGlobal = 'Tarjeta Crédito';
} elseif (isset($_GET['tx']) || (isset($_POST['payment_status']) && $_POST['payment_status'] === 'Completed')) {
    $paymentMethodGlobal = 'PayPal';
} else {
    $paymentMethodGlobal = 'PayPal';
}

// Iniciar transacción
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

        // Validación extra de fechas (por si el carrito trae cosas raras)
        try {
            $ci = new DateTime($check_in);
            $co = new DateTime($check_out);
            if ($co <= $ci) throw new Exception('Fechas inválidas en el carrito.');
        } catch (Exception $e) {
            throw new Exception('Fechas inválidas en el carrito.');
        }

        $check_in_es  = (new DateTime($check_in))->format('d/m/Y');
        $check_out_es = (new DateTime($check_out))->format('d/m/Y');

        // 0) Seleccionar habitaciones REALMENTE libres para ese rango (CRÍTICO)
        // Regla de solapamiento:
        // hay choque si (res.CheckIn < miCheckOut) AND (res.CheckOut > miCheckIn)
        $sqlRoom = "
            SELECT r.Id
            FROM Rooms r
            WHERE r.Id_Hotel = ?
              AND r.Id_RoomType = ?
              AND r.Available = 1
              AND r.Id NOT IN (
                  SELECT rr.Id_Room
                  FROM Reservation res
                  INNER JOIN Reservation_Rooms rr ON rr.Id_Reservation = res.Id
                  WHERE res.Status = 'Confirmada'
                    AND res.CheckIn_Date < ?
                    AND res.CheckOut_Date > ?
              )
            LIMIT ?
        ";
        $stmtRoom = $conn->prepare($sqlRoom);
        $stmtRoom->bind_param("iissi", $hotelId, $roomTypeId, $check_out, $check_in, $roomsCount);
        $stmtRoom->execute();
        $resRoom = $stmtRoom->get_result();

        $roomsFound = [];
        while ($rowR = $resRoom->fetch_assoc()) {
            $roomsFound[] = (int)$rowR['Id'];
        }
        $stmtRoom->close();

        if (count($roomsFound) < $roomsCount) {
            throw new Exception('No hay suficientes habitaciones disponibles para esas fechas.');
        }

        // 1) Crear Reserva
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

        // 2) Asignar habitaciones (las que ya sabemos que están libres)
        foreach ($roomsFound as $roomId) {
            $sqlRR = "INSERT INTO Reservation_Rooms (Id_Reservation, Id_Room) VALUES (?, ?)";
            $stmtRR = $conn->prepare($sqlRR);
            $stmtRR->bind_param("ii", $reservationId, $roomId);
            $stmtRR->execute();
            $stmtRR->close();
        }

        // --- 3. GENERAR FACTURA ---
        $baseImponible = $priceNight * $nights * $roomsCount;
        $ivaPorcentaje = 0.21;
        $ivaCantidad   = $baseImponible * $ivaPorcentaje;
        $totalFactura  = $baseImponible + $ivaCantidad;

        $invoiceNumber = 'FACT-' . date('Ymd') . '-' . $reservationId;
        $invoiceDate   = date('Y-m-d');
        $invoiceStatus = 'Pagada';

        $sqlInv = "INSERT INTO Invoices (Id_Reservation, Id_User, InvoiceNumber, Date, Subtotal, IVA, Total, PaymentMethod, Status) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInv = $conn->prepare($sqlInv);
        $stmtInv->bind_param("iisssddss", $reservationId, $userId, $invoiceNumber, $invoiceDate, $baseImponible, $ivaCantidad, $totalFactura, $paymentMethodGlobal, $invoiceStatus);
        $stmtInv->execute();
        $invoiceId = $stmtInv->insert_id;
        $stmtInv->close();

        $descItem = "Estancia Hotel ($nights noches)";
        $qtyItem  = $roomsCount;

        $sqlItem = "INSERT INTO InvoiceItems (Id_Invoice, Description, Quantity, UnitPrice, Total) VALUES (?, ?, ?, ?, ?)";
        $stmtItem = $conn->prepare($sqlItem);
        $stmtItem->bind_param("isidd", $invoiceId, $descItem, $qtyItem, $priceNight, $baseImponible);
        $stmtItem->execute();
        $stmtItem->close();

        // Obtener nombre del hotel
        $hotelName = 'Hotel #' . $hotelId;
        $resH = $conn->query("SELECT Name FROM Hotels WHERE Id = $hotelId");
        if ($rowH = $resH->fetch_assoc()) $hotelName = $rowH['Name'];

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
