<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: ../auth/login.php");
    exit();
}

$nombre = htmlspecialchars($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }

        .header {
            background-color: #343a40;
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 14px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-6px);
        }

        .card h3 {
            margin-top: 0;
            font-size: 22px;
            color: #343a40;
        }

        .card a {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            margin-top: 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .card a:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>

<div class="header">
    <h1>Bienvenido, <?= $nombre ?></h1>
    <a href="../auth/logout.php" class="logout-btn">Cerrar Sesión</a>
</div>

<div class="container">
    <h2 style="text-align:center; margin-bottom: 30px;">Panel de Administración</h2>

    <div class="cards">

    <div class="card">
        <h3>Administrar Usuarios</h3>
        <p>Registrar nuevos usuarios para la plataforma.</p>
        <a href="register.php">Ir a Registro</a>
    </div>

    <div class="card">
        <h3>Ver Tienda</h3>
        <p>Explora la vista del cliente como un usuario regular.</p>
        <a href="../Cliente/index.php">Ir a la Tienda</a>
    </div>

    <div class="card">
        <h3>Registros de Sesión</h3>
        <p>Consulta los inicios y cierres de sesión de todos los usuarios.</p>
        <a href="logs.php">Ver Logs</a>
    </div>
    
    <div class="card">
        <h3>Gestión de Reservas</h3>
        <p>Ver y modificar el estado de todas las reservas de los clientes.</p>
        <a href="reservas.php">Administrar Reservas</a>
    </div>

    <div class="card">
        <h3>Gestión de Habitaciones</h3>
        <p>Marcar habitaciones como operativas (disponibles) o no operativas (mantenimiento).</p>
        <a href="gestion_habitaciones.php">Administrar Habitaciones</a>
    </div>
    </div>
</div>

<!-- Mantener sesión activa -->
<script src="../Assets/js/keepalive.js"></script>

</body>
</html>
