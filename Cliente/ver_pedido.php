<?php
session_start();
include('../conexion.php');

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header('Location: mis_pedidos.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reserva_id = intval($_GET['id']);

// 1. Obtener datos generales de la Reserva y el Hotel
// Hacemos JOIN con Rooms y Hotels para saber dónde es la reserva.
// Usamos LIMIT 1 porque asumimos que una reserva es para un hotel específico.
$sql = "SELECT 
            r.Id, r.Booking_date, r.CheckIn_Date, r.CheckOut_Date, r.Num_Nights, r.Status,
            i.Total, i.Id AS InvoiceId,
            h.Name AS HotelName, h.City, h.Address
        FROM Reservation r
        LEFT JOIN Invoices i ON r.Id = i.Id_Reservation
        JOIN Reservation_Rooms rr ON r.Id = rr.Id_Reservation
        JOIN Rooms room ON rr.Id_Room = room.Id
        JOIN Hotels h ON room.Id_Hotel = h.Id
        WHERE r.Id = ? AND r.Id_User = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $reserva_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reserva = $result->fetch_assoc();
$stmt->close();

if (!$reserva) {
    die("Reserva no encontrada o no tienes permiso para verla.");
}

// 2. Obtener detalles de las habitaciones reservadas (Agrupadas por tipo)
$sqlDetalles = "SELECT rt.Name AS RoomTypeName, rt.CostPerNight, COUNT(rr.Id) as Cantidad
                FROM Reservation_Rooms rr
                JOIN Rooms room ON rr.Id_Room = room.Id
                JOIN RoomType rt ON room.Id_RoomType = rt.Id
                WHERE rr.Id_Reservation = ?
                GROUP BY rt.Name, rt.CostPerNight";

$stmt2 = $conn->prepare($sqlDetalles);
$stmt2->bind_param('i', $reserva_id);
$stmt2->execute();
$resultDetalles = $stmt2->get_result();
$habitaciones = [];
while ($row = $resultDetalles->fetch_assoc()) {
    $habitaciones[] = $row;
}
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Detalles de Reserva #<?php echo $reserva['Id']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styleCarlos.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .pedido-container { max-width: 800px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        
        .header-reserva { border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 20px; }
        .header-reserva h1 { color: #a02040; margin: 0; }
        .header-reserva p { color: #666; margin: 5px 0 0 0; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .info-label { font-size: 0.85rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .info-value { font-size: 1.1rem; color: #333; font-weight: 600; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #a02040; color: white; font-weight: 500; font-size: 0.9rem; }
        
        .total-section { text-align: right; margin-top: 20px; font-size: 1.5rem; color: #a02040; font-weight: bold; }
        
        .actions { margin-top: 30px; display: flex; gap: 15px; }
        .btn { flex: 1; padding: 12px; text-align: center; border-radius: 8px; text-decoration: none; font-weight: bold; transition: opacity 0.2s; }
        .btn-invoice { background: #28a745; color: white; }
        .btn-back { background: #343a40; color: white; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>

<div class="pedido-container">
    <div class="header-reserva">
        <h1>Reserva #<?php echo $reserva['Id']; ?></h1>
        <p>Realizada el <?php echo date('d/m/Y', strtotime($reserva['Booking_date'])); ?></p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Hotel</div>
            <div class="info-value"><?php echo htmlspecialchars($reserva['HotelName']); ?></div>
            <div style="font-size:0.9rem; color:#666;"><?php echo htmlspecialchars($reserva['Address']); ?>, <?php echo htmlspecialchars($reserva['City']); ?></div>
        </div>
        <div class="info-box">
            <div class="info-label">Fechas</div>
            <div class="info-value">
                <?php echo date('d/m/Y', strtotime($reserva['CheckIn_Date'])); ?> 
                ➡ 
                <?php echo date('d/m/Y', strtotime($reserva['CheckOut_Date'])); ?>
            </div>
            <div style="font-size:0.9rem; color:#666;"><?php echo $reserva['Num_Nights']; ?> Noches</div>
        </div>
    </div>

    <h3>Detalle de Habitaciones</h3>
    <table>
        <thead>
            <tr>
                <th>Tipo Habitación</th>
                <th>Cantidad</th>
                <th>Precio Base Noche</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($habitaciones as $hab): ?>
            <tr>
                <td><?php echo htmlspecialchars($hab['RoomTypeName']); ?></td>
                <td><?php echo $hab['Cantidad']; ?></td>
                <td>€<?php echo number_format($hab['CostPerNight'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        Total Pagado: €<?php echo number_format($reserva['Total'], 2); ?>
    </div>

    <div class="actions">
        <a href="mis_pedidos.php" class="btn btn-back">← Volver a Mis Reservas</a>
        
        <?php if ($reserva['InvoiceId']): ?>
            <a href="ver_factura.php?id=<?php echo $reserva['InvoiceId']; ?>" target="_blank" class="btn btn-invoice">
                🖨️ Ver / Descargar Factura
            </a>
        <?php else: ?>
            <button class="btn" style="background:#ccc; cursor:not-allowed;" disabled>Factura No Disponible</button>
        <?php endif; ?>
    </div>
</div>

</body>
</html>