<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: ../auth/login.php");
    exit();
}

include("../conexion.php");

// 1. Consulta para obtener todas las reservas
$sql = "SELECT 
            r.Id AS ReservationId,
            r.CheckIn_Date,
            r.CheckOut_Date,
            r.Num_Nights,
            r.Status,
            u.Name AS UserName,
            h.Name AS HotelName,
            h.City,
            i.Total
        FROM Reservation r
        INNER JOIN Users u ON r.Id_User = u.Id
        LEFT JOIN Invoices i ON r.Id = i.Id_Reservation
        INNER JOIN Reservation_Rooms rr ON r.Id = rr.Id_Reservation
        INNER JOIN Rooms rm ON rr.Id_Room = rm.Id
        INNER JOIN Hotels h ON rm.Id_Hotel = h.Id
        GROUP BY r.Id, r.CheckIn_Date, r.CheckOut_Date, r.Num_Nights, r.Status, u.Name, h.Name, h.City, i.Total
        ORDER BY r.Booking_date DESC";

$result = $conn->query($sql);

// Estados permitidos
$statuses = ['Aceptada', 'Cancelada', 'En Proceso'];

// Mensaje de éxito/error después de la actualización
$mensaje = isset($_SESSION['reserva_msg']) ? $_SESSION['reserva_msg'] : '';
unset($_SESSION['reserva_msg']);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reservas - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
        }

        .header {
            background-color: #343a40;
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.9em;
        }

        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        table th {
            background-color: #007bff;
            color: white;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        
        .status-Aceptada { color: green; font-weight: bold; }
        .status-Cancelada { color: red; font-weight: bold; }
        .status-En_Proceso { color: orange; font-weight: bold; }

        .btn-save {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-save:hover {
            background-color: #1e7e34;
        }
        
        .msg-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Gestión de Reservas</h2>
    <a href="index.php">← Volver al Panel</a>
</div>

<div class="container">

    <?php if ($mensaje): ?>
        <div class="msg-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Hotel</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Noches</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                // Formato de fechas
                $checkInEs = (new DateTime($row['CheckIn_Date']))->format('d/m/Y');
                $checkOutEs = (new DateTime($row['CheckOut_Date']))->format('d/m/Y');
            ?>
            <tr>
                <td><?= $row['ReservationId'] ?></td>
                <td><?= htmlspecialchars($row['UserName']) ?></td>
                <td><?= htmlspecialchars($row['HotelName']) . ' (' . htmlspecialchars($row['City']) . ')' ?></td>
                <td><?= $checkInEs ?></td>
                <td><?= $checkOutEs ?></td>
                <td><?= $row['Num_Nights'] ?></td>
                <td><?= number_format($row['Total'] ?? 0, 2) ?> €</td>
                
                <form action="update_reserva.php" method="POST">
                    <input type="hidden" name="reservation_id" value="<?= $row['ReservationId'] ?>">
                    
                    <td>
                        <select name="status" class="status-select status-<?= str_replace(' ', '_', $row['Status']) ?>">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status ?>" <?= ($row['Status'] === $status) ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    
                    <td>
                        <button type="submit" class="btn-save">Guardar</button>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<script src="../keepalive.js"></script>

</body>
</html>