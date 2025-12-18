<?php
session_start();
include('../Config/conexion.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?error=Acceso+denegado");
    exit();
}

$reservation_id = isset($_GET['reserva_id']) ? (int)$_GET['reserva_id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($reservation_id <= 0) {
    header("Location: mis_pedidos.php?error=Reserva+no+encontrada");
    exit();
}

// Consulta para obtener todos los detalles de la reserva y el hotel
// Incluye todos los campos necesarios de la Factura (i.)
$sql = "SELECT 
            r.*, 
            h.Name AS HotelName, 
            h.Address, 
            h.City,
            rt.Name AS RoomTypeName,
            i.Id AS InvoiceId,
            i.InvoiceNumber,
            i.Total,      
            i.Subtotal,
            i.IVA,
            i.PaymentMethod
        FROM Reservation r
        INNER JOIN Reservation_Rooms rr ON r.Id = rr.Id_Reservation
        INNER JOIN Rooms rm ON rr.Id_Room = rm.Id
        INNER JOIN Hotels h ON rm.Id_Hotel = h.Id
        INNER JOIN RoomType rt ON rm.Id_RoomType = rt.Id
        LEFT JOIN Invoices i ON r.Id = i.Id_Reservation
        WHERE r.Id = ? AND r.Id_User = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$resultado = $stmt->get_result();
$detalle = $resultado->fetch_assoc();
$stmt->close();

if (!$detalle) {
    header("Location: mis_pedidos.php?error=Reserva+no+autorizada");
    exit();
}

// Visualización (DD/MM/YYYY)
$check_in_es = (new DateTime($detalle['CheckIn_Date']))->format('d/m/Y');
$check_out_es = (new DateTime($detalle['CheckOut_Date']))->format('d/m/Y');
$booking_date_es = (new DateTime($detalle['Booking_date']))->format('d/m/Y');

// Acceso seguro a variables que pueden ser NULL
$total_pagado = $detalle['Total'] ?? 0.00;
$invoice_id = $detalle['InvoiceId'] ?? null;
$invoice_number = $detalle['InvoiceNumber'] ?? 'N/A';
$subtotal = $detalle['Subtotal'] ?? 0.00;
$iva = $detalle['IVA'] ?? 0.00;
$notes = $detalle['Notes'] ?? ''; 
$payment_method = $detalle['PaymentMethod'] ?? 'Pendiente';

$nombreCadena = "Hoteles Nueva España S.L.";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Pedido #<?php echo $reservation_id; ?></title>
    <style>
        :root {
            --color-primary: #a02040;
            --color-secondary: #ffc107;
            --color-dark: #343a40;
            --color-light: #f8f9fa;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-light);
            margin: 0;
            padding-top: 80px; 
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: var(--color-primary);
            border-bottom: 2px solid var(--color-secondary);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .navbar {
            background-color: #ffffff; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box; 
        }
        .navbar-brand {
            color: var(--color-primary);
            font-size: 1.8em;
            font-weight: 700;
            text-decoration: none;
        }
        .nav-link-btn {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95em;
            background-color: var(--color-primary); 
            color: white;
            border: 1px solid var(--color-primary);
            transition: background-color 0.2s, color 0.2s;
        }
        .nav-link-btn:hover {
            background-color: #801933;
        }
        
        .data-row {
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
        }
        .data-row strong {
            color: var(--color-dark);
            font-weight: 600;
        }
        .data-row span {
            color: #555;
        }
        .total-row {
            padding-top: 20px;
            font-size: 1.4em;
            color: var(--color-primary);
            font-weight: 700;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-primary {
            background-color: var(--color-primary);
            color: white;
        }
        .btn-primary:hover {
            background-color: #801933;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Hoteles Nueva España S.L.</a>
        
        <a href="mis_pedidos.php" class="nav-link-btn">← Mis Pedidos</a>
    </nav>
    <div class="container">
        <div class="card">
            <h1>Detalle del Pedido #<?php echo $detalle['Id']; ?></h1>

            <h2>Información General</h2>
            <div class="data-row">
                <strong>Fecha de Reserva:</strong>
                <span><?php echo $booking_date_es; ?></span>
            </div>
            <div class="data-row">
                <strong>Estado:</strong>
                <span style="color:<?php echo ($detalle['Status'] == 'Confirmada' ? '#28a745' : '#ffc107'); ?>">
                    <?php echo htmlspecialchars($detalle['Status']); ?>
                </span>
            </div>
            

            <h2>Detalles de la Estancia</h2>
            <div class="data-row">
                <strong>Hotel:</strong>
                <span><?php echo htmlspecialchars($detalle['HotelName']); ?> (<?php echo htmlspecialchars($detalle['City']); ?>)</span>
            </div>
            <div class="data-row">
                <strong>Dirección:</strong>
                <span><?php echo htmlspecialchars($detalle['Address']); ?></span>
            </div>
            <div class="data-row">
                <strong>Tipo de Habitación:</strong>
                <span><?php echo htmlspecialchars($detalle['RoomTypeName']); ?></span>
            </div>
            <div class="data-row">
                <strong>Check-in:</strong>
                <span><?php echo $check_in_es; ?></span>
            </div>
            <div class="data-row">
                <strong>Check-out:</strong>
                <span><?php echo $check_out_es; ?></span>
            </div>
            <div class="data-row">
                <strong>Número de Noches:</strong>
                <span><?php echo $detalle['Num_Nights']; ?></span>
            </div>
            
            <?php if (!empty($notes)): ?>
                <div class="data-row">
                    <strong>Notas:</strong>
                    <span><?php echo htmlspecialchars($notes); ?></span>
                </div>
            <?php endif; ?>

            <h2 style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">Resumen de Facturación</h2>
            <div class="data-row">
                <strong>Subtotal (Base Imponible):</strong>
                <span><?php echo number_format($subtotal, 2); ?> €</span>
            </div>
             <div class="data-row">
                <strong>IVA:</strong>
                <span><?php echo number_format($iva, 2); ?> €</span>
            </div>
            <div class="data-row total-row">
                <strong>Total Pagado:</strong>
                <span><?php echo number_format($total_pagado, 2); ?> €</span>
            </div>

            <hr style="margin: 30px 0;">

            <?php if ($invoice_id): ?>
                <a href="ver_factura.php?id=<?php echo $invoice_id; ?>" class="btn btn-primary" target="_blank">
                    📄 Descargar Factura (<?php echo htmlspecialchars($invoice_number); ?>)
                </a>
                
            <?php else: ?>
                <p style="color: #dc3545; font-weight: bold;">Factura no encontrada para esta reserva.</p>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
