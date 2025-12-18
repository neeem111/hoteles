<?php
session_start();
include('../Config/conexion.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?error=Debes+iniciar+sesion+para+ver+tus+pedidos");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// 1. Consulta para obtener las reservas del usuario
// Usamos DISTINCT para agrupar por Id_Reservation, ya que puede haber varios Reservation_Rooms
$sql = "SELECT DISTINCT
            r.Id AS ReservationId,
            r.Booking_date,
            r.CheckIn_Date,
            r.CheckOut_Date,
            r.Status,
            r.Num_Nights,
            h.Name AS HotelName,
            h.City,
            i.InvoiceNumber,
            i.Total
        FROM Reservation r
        INNER JOIN Reservation_Rooms rr ON r.Id = rr.Id_Reservation
        INNER JOIN Rooms rm ON rr.Id_Room = rm.Id
        INNER JOIN Hotels h ON rm.Id_Hotel = h.Id
        LEFT JOIN Invoices i ON r.Id = i.Id_Reservation
        WHERE r.Id_User = ?
        ORDER BY r.Booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result();
$pedidos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$nombreCadena = "Hoteles Nueva España S.L.";
$user_name = htmlspecialchars($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - Hoteles NESL</title>
    <style>
        /* Estilos CSS simplificados para mantener la estética */
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
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: var(--color-primary);
            border-bottom: 2px solid var(--color-secondary);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .pedido-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: box-shadow 0.3s;
        }
        .pedido-card:hover {
            box-shadow: 0 6px 15px rgba(0,0,0,0.12);
        }
        .details {
            flex-grow: 1;
        }
        .details h3 {
            margin: 0 0 5px 0;
            font-size: 1.4em;
            color: var(--color-dark);
        }
        .details p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #555;
        }
        .details strong {
             /* Estilo adicional para que las negritas resalten un poco más si es necesario */
             color: var(--color-dark);
             font-weight: 700;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }
        .btn-detail {
            background-color: var(--color-primary);
            color: white;
        }
        .btn-detail:hover {
            background-color: #801933;
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
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand"><?php echo $nombreCadena; ?></a>
        <a href="index.php" class="btn btn-detail">← Volver a Hoteles</a>
    </nav>
    
    <div class="container">
        <h1>Mis Pedidos</h1>
        
        <?php if (count($pedidos) > 0): ?>
            <?php foreach ($pedidos as $pedido): 
                // Formatear fechas para mostrar
                $check_in_es = (new DateTime($pedido['CheckIn_Date']))->format('d/m/Y');
                $check_out_es = (new DateTime($pedido['CheckOut_Date']))->format('d/m/Y');
                $booking_date_es = (new DateTime($pedido['Booking_date']))->format('d/m/Y');
            ?>
                <div class="pedido-card">
                    <div class="details">
                        <h3>🏨 Reserva en <?php echo htmlspecialchars($pedido['HotelName']); ?> (<?php echo htmlspecialchars($pedido['City']); ?>)</h3>
                        
                        <p>
                            Fechas: Del <strong><?php echo $check_in_es; ?></strong> al <strong><?php echo $check_out_es; ?></strong> (<?php echo $pedido['Num_Nights']; ?> noches)
                        </p>
                        <p>
                            <strong>Total Pagado:</strong> <?php echo number_format($pedido['Total'], 2); ?> € | 
                            <strong>Fecha Pedido:</strong> <?php echo $booking_date_es; ?>
                        </p>
                        </div>
                    <div class="actions">
                        <a href="detalle_pedido.php?reserva_id=<?php echo $pedido['ReservationId']; ?>" class="btn btn-detail">
                            Ver Detalles
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="pedido-card">
                <p>Aún no tienes pedidos registrados.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>