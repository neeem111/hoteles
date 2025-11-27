<?php
// views/reservation/success.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva creada</title>
    <link rel="stylesheet" href="styleCarlos.css">
</head>
<body>
<div class="container">
    <h1>¡Reserva completada con éxito!</h1>
    <p>Tu número de reserva es: <strong><?php echo htmlspecialchars($reservationIdView); ?></strong></p>
    <p>Puedes consultar tus reservas en la sección <a href="mis_reservas.php">Mis reservas</a>.</p>
    <p><a href="index.php">Volver al inicio</a></p>
</div>
</body>
</html>
