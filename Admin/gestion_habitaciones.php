<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: ../auth/login.php");
    exit();
}

include("../conexion.php");

// 1. Consulta para obtener todas las habitaciones con detalles del hotel y tipo
// IMPORTANTE: Se usa R.Id como referencia en lugar de Room_Number
$sql = "SELECT 
            R.Id AS RoomId,
            R.Available,
            H.Name AS HotelName,
            H.City,
            RT.Name AS RoomTypeName
        FROM Rooms R
        INNER JOIN Hotels H ON R.Id_Hotel = H.Id
        INNER JOIN RoomType RT ON R.Id_RoomType = RT.Id
        ORDER BY H.Name, RT.Name, R.Id ASC";

$result = $conn->query($sql);

// Mensaje de éxito/error después de la actualización
$mensaje = isset($_SESSION['room_msg']) ? $_SESSION['room_msg'] : '';
unset($_SESSION['room_msg']);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Habitaciones - Admin</title>
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
            max-width: 1200px;
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
        
        .status-operativa { color: green; font-weight: bold; }
        .status-mantenimiento { color: red; font-weight: bold; }

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
        .msg-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Gestión de Habitaciones Operativas</h2>
    <a href="index.php">← Volver al Panel</a>
</div>

<div class="container">

    <?php if ($mensaje): ?>
        <div class="msg-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID Hab. (Interno)</th>
                <th>Hotel</th>
                <th>Ciudad</th>
                <th>Tipo de Habitación</th>
                <th>Estado Operativo</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $is_available = (int)$row['Available'];
                $status_class = $is_available ? 'status-operativa' : 'status-mantenimiento';
            ?>
            <tr>
                <td><?= $row['RoomId'] ?></td>
                <td><?= htmlspecialchars($row['HotelName']) ?></td>
                <td><?= htmlspecialchars($row['City']) ?></td>
                <td><?= htmlspecialchars($row['RoomTypeName']) ?></td>
                
                <form action="update_room_available.php" method="POST">
                    <input type="hidden" name="room_id" value="<?= $row['RoomId'] ?>">
                    
                    <td>
                        <select name="available" class="status-select <?= $status_class ?>">
                            <option value="1" <?= ($is_available == 1) ? 'selected' : '' ?>>
                                Operativa (Disponible)
                            </option>
                            <option value="0" <?= ($is_available == 0) ? 'selected' : '' ?>>
                                Mantenimiento (No disponible)
                            </option>
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