<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: login.php?error=Acceso+solo+para+administradores");
    exit();
}

$nombre = htmlspecialchars($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="styleCarlos.css">
</head>
<body>
    <div class="admin-card">
        <h1>👋 Bienvenido, <?php echo $nombre; ?></h1>
        <p>Has iniciado sesión como <strong>Administrador</strong>.</p>

        <div class="admin-buttons">
            <a href="index.php" class="btn-primary">Ver Tienda</a>
            <a href="logout.php" class="btn-logout">Cerrar sesión</a>
        </div>
    </div>
</body>
</html>