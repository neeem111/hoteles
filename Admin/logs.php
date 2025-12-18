<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: ../auth/login.php");
    exit();
}

include("../Config/conexion.php");

$sql = "SELECT * FROM login_logs ORDER BY login_time DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registros de Sesiones</title>
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
            max-width: 1100px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #007bff;
            color: white;
            text-align: left;
        }

        .active {
            font-weight: bold;
            color: green;
        }

        .inactive {
            color: red;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Registros de Sesiones</h2>
    <a href="index.php">← Volver al Panel</a>
</div>

<div class="container">

    <table>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Inicio de Sesión</th>
            <th>Fin de Sesión</th>
            <th>Duración</th>
            <th>Estado</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['user_name']) ?></td>
                <td><?= $row['login_time'] ?></td>
                <td><?= $row['logout_time'] ?? '-' ?></td>
                <td><?= $row['duration'] ?></td>
                <td>
                    <?php if ($row['duration'] === "Sesión activa"): ?>
                        <span class="active">🟢 Activa</span>
                    <?php else: ?>
                        <span class="inactive">🔴 Cerrada</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>

    </table>

</div>

<script src="../Assets/js/keepalive.js"></script>

</body>
</html>