<?php
// views/reservation/form.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar reserva - <?php echo htmlspecialchars($hotelView['Name']); ?></title>
    <link rel="stylesheet" href="styleCarlos.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 100px auto 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #a02040;
        }
        .resumen, .form-reserva {
            margin-bottom: 25px;
        }
        .resumen-item {
            margin-bottom: 8px;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        .btn-primary {
            margin-top: 20px;
            background-color: #a02040;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #801933;
        }
        .small-text {
            font-size: 0.9em;
            color: #666;
        }
        .nav {
            background:#a02040; 
            color:white; 
            padding:15px; 
            text-align:center; 
            position:fixed; 
            top:0; 
            width:100%; 
            z-index:1000;
        }
        .nav a {
            color:white; 
            text-decoration:none; 
            font-size:1.2em; 
            font-weight:bold;
        }
    </style>
</head>
<body>

<nav class="nav">
    <a href="index.php?page=hotel&hotel_id=<?= $hotelIdView ?>">Volver al hotel</a>
</nav>

<div class="container">
    <h1>Confirmar reserva</h1>

    <div class="resumen">
        <h2>Resumen de la selección</h2>
        <p><strong>Hotel:</strong> <?php echo htmlspecialchars($hotelView['Name']); ?> (<?php echo htmlspecialchars($ciudadView); ?>)</p>
        <p><strong>Tipo de habitación:</strong> <?php echo htmlspecialchars($roomTypeView['Name']); ?></p>
        <p><strong>Capacidad máxima:</strong> <?php echo (int)$roomTypeView['Guests']; ?> huésped(es)</p>
        <p><strong>Precio por noche:</strong> <?php echo $precioPorNocheView; ?> €</p>
        <p class="small-text">
            Habitaciones disponibles de este tipo: <?php echo $maxHabitacionesView; ?>
        </p>
    </div>

    <div class="form-reserva">
        <h2>Datos de la reserva</h2>

        <form action="reserva.php" method="POST">
            <input type="hidden" name="hotel_id" value="<?php echo (int)$hotelIdView; ?>">
            <input type="hidden" name="room_type_id" value="<?php echo (int)$roomTypeIdView; ?>">

            <label>Nombre del cliente</label>
            <input type="text" value="<?php echo htmlspecialchars($userNameView); ?>" disabled>

            <label>Email del cliente</label>
            <input type="email" value="<?php echo htmlspecialchars($userEmailView); ?>" disabled>

            <label for="check_in">Fecha de entrada</label>
            <input type="date" id="check_in" name="check_in" required>

            <label for="check_out">Fecha de salida</label>
            <input type="date" id="check_out" name="check_out" required>

            <label for="num_rooms">Número de habitaciones</label>
            <input 
                type="number" 
                id="num_rooms" 
                name="num_rooms" 
                min="1" 
                max="<?php echo max(1, $maxHabitacionesView); ?>" 
                value="1"
                required
            >

            <label for="num_guests">Número total de huéspedes</label>
            <input type="number" id="num_guests" name="num_guests" min="1" required>

            <label for="comments">Peticiones especiales (opcional)</label>
            <textarea id="comments" name="comments" placeholder="Ej: Cuna para bebé, piso alto, etc."></textarea>

            <label for="payment_method">Método de pago</label>
            <select id="payment_method" name="payment_method" required>
                <option value="">Selecciona una opción</option>
                <option value="Tarjeta">Tarjeta</option>
                <option value="PayPal">PayPal</option>
                <option value="Efectivo en recepción">Efectivo en recepción</option>
            </select>
            <p class="small-text">
                *No se guardan datos sensibles de la tarjeta, solo el método elegido para la factura.
            </p>

            <button type="submit" class="btn-primary">Confirmar reserva</button>
        </form>
    </div>
</div>

</body>
</html>
