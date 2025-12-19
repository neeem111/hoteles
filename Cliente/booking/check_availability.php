<?php
session_start();
header('Content-Type: application/json');

include('../../Config/conexion.php');

$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_type_id = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

if ($hotel_id <= 0 || $room_type_id <= 0 || $check_in === '' || $check_out === '') {
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    $in = new DateTime($check_in);
    $out = new DateTime($check_out);
    if ($out <= $in) {
        echo json_encode(['ok' => false, 'error' => 'Fechas inválidas']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'Formato de fecha inválido']);
    exit;
}

// Total de rooms físicas "habilitadas" (Available=1) para ese hotel y tipo
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
      AND res.CheckIn_Date < ?   -- empieza antes de que yo termine
      AND res.CheckOut_Date > ?  -- termina después de que yo empiece
";
$stmtB = $conn->prepare($sqlBooked);
$stmtB->bind_param("iiss", $hotel_id, $room_type_id, $check_out, $check_in);
$stmtB->execute();
$booked = (int)$stmtB->get_result()->fetch_assoc()['booked'];
$stmtB->close();

$available = max(0, $total - $booked);

echo json_encode([
    'ok' => true,
    'total' => $total,
    'booked' => $booked,
    'available' => $available
]);
