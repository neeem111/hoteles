<?php
session_start();
include ('../../Config/conexion.php');

// Debe estar logueado para poder añadir al carrito
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '../index.php';
    header("Location: ../../auth/login.php?error=Debes+iniciar+sesion+para+continuar");
    exit;
}

$userId     = (int)$_SESSION['user_id'];
$userName   = $_SESSION['user_name']   ?? '';
$userEmail  = $_SESSION['user_email'] ?? '';

// --- 1. Leer parámetros ---
$hotel_id     = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_type_id = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;

$check_in_pre  = isset($_GET['check_in']) ? $_GET['check_in'] : null;
$check_out_pre = isset($_GET['check_out']) ? $_GET['check_out'] : null;
$price_pre     = isset($_GET['price']) ? (float)$_GET['price'] : 0.0; // Recibimos el precio para pre-cargar
$max_rooms_pre = isset($_GET['max_rooms']) ? (int)$_GET['max_rooms'] : 1; // Número máximo de habitaciones disponibles para este tipo

// Validación básica de IDs (las fechas se validarán más abajo)
if ($hotel_id <= 0 || $room_type_id <= 0) {
    header("Location: ../index.php?error=Hotel+o+habitacion+no+valido");
    exit;
}

// Si por algún motivo el número máximo de habitaciones es menor que 1, redirigimos
if ($max_rooms_pre < 1) {
    header("Location: ../../Publico/hotel.php?hotel_id=" . $hotel_id . "&error=No+hay+habitaciones+disponibles+para+este+tipo");
    exit;
}

// --- 2. Cargar datos del hotel ---
$sqlHotel = "SELECT Id, Name, City, Address FROM Hotels WHERE Id = ?";
$stmtH = $conn->prepare($sqlHotel);
$stmtH->bind_param("i", $hotel_id);
$stmtH->execute();
$resH = $stmtH->get_result();
$hotel = $resH->fetch_assoc();
$stmtH->close();

if (!$hotel) {
    header("Location: ../index.php?error=Hotel+no+encontrado");
    exit;
}

// --- 3. Cargar datos del tipo de habitación + comprobar que hay rooms disponibles ---
// NOTA: Esta consulta debe reflejar la lógica de hotel.php para asegurar que la disponibilidad
// sea por FECHA y no solo por r.Available=1. Como ya filtramos en hotel.php, aquí nos enfocamos en el precio/tipo.

$sqlTipo = "SELECT 
                rt.Id,
                rt.Name,
                rt.Guests,
                rt.CostPerNight
            FROM RoomType rt
            WHERE rt.Id = ?";

$stmtT = $conn->prepare($sqlTipo);
$stmtT->bind_param("i", $room_type_id);
$stmtT->execute();
$resT = $stmtT->get_result();
$tipo = $resT->fetch_assoc();
$stmtT->close();

if (!$tipo) {
    header("Location: ../../Publico/hotel.php?hotel_id=" . $hotel_id . "&error=Tipo+de+habitacion+no+encontrado");
    exit;
}

// Sobreescribir CostPerNight si se pasó en la URL, aunque el valor de la BD es el estándar
$precio_final = $price_pre > 0 ? $price_pre : (float)$tipo['CostPerNight'];


// --- 4. Obtener rangos de fechas ya reservadas para este hotel + tipo ---

$sqlOcupadas = "
    SELECT 
        res.CheckIn_Date,
        res.CheckOut_Date
    FROM Reservation res
    INNER JOIN Reservation_Rooms rr ON rr.Id_Reservation = res.Id
    INNER JOIN Rooms r ON rr.Id_Room = r.Id
    WHERE r.Id_Hotel = ?
      AND r.Id_RoomType = ?
      AND res.Status = 'Confirmada'
    ORDER BY res.CheckIn_Date ASC
";

$stmtOcc = $conn->prepare($sqlOcupadas);
$stmtOcc->bind_param("ii", $hotel_id, $room_type_id);
$stmtOcc->execute();
$resOcc = $stmtOcc->get_result();

$rangosOcupados = [];
while ($row = $resOcc->fetch_assoc()) {
    // Almacenar los rangos en formato JSON para que JavaScript los lea fácilmente
    $rangosOcupados[] = [
        'start' => $row['CheckIn_Date'], 
        'end' => $row['CheckOut_Date']
    ];
}
$stmtOcc->close();

// Convertir a JSON para pasar a JavaScript
$rangosOcupados_json = json_encode($rangosOcupados);


// Fechas mínimas para el datepicker
$hoy     = (new DateTime())->format('Y-m-d');
$manana  = (new DateTime('+1 day'))->format('Y-m-d');

// Mensajes del carrito o validaciones
$cartError   = $_SESSION['cart_error']   ?? '';
$cartSuccess = $_SESSION['cart_success'] ?? '';
unset($_SESSION['cart_error'], $_SESSION['cart_success']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar fechas - <?php echo htmlspecialchars($hotel['Name']); ?></title>
    <link rel="stylesheet" href="../../Assets/css/styleCarlos.css">
    <style>
        /* [Estilos CSS: Se mantienen los tuyos, omitidos por brevedad, pero incluyendo .error-message] */
        body {
            background-color:#f8f9fa;
            font-family: Arial, sans-serif;
            margin:0;
            padding:0;
        }
        .wrapper {
            max-width: 1100px;
            margin: 40px auto;
            padding: 10px;
        }
        .layout {
            display: grid;
            grid-template-columns: 2fr 1.4fr;
            gap: 25px;
        }
        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background:#fff;
            border-radius:12px;
            box-shadow:0 4px 15px rgba(0,0,0,0.08);
            padding:20px 24px;
        }
        .hotel-header h1 {
            margin:0 0 5px 0;
            color:#a02040;
            font-size:1.8rem;
        }
        .hotel-header p {
            margin:3px 0;
            color:#555;
        }
        .section-title {
            font-size:1.1rem;
            margin-top:0;
            margin-bottom:10px;
            color:#343a40;
        }
        .summary-row {
            display:flex;
            justify-content:space-between;
            font-size:0.95rem;
            margin-bottom:6px;
        }
        .summary-label {
            color:#666;
        }
        .summary-value {
            font-weight:600;
        }
        .price-highlight {
            font-size:1.4rem;
            font-weight:700;
            color:#28a745;
        }
        .price-note {
            font-size:0.85rem;
            color:#777;
        }
        .msg-error {
            background:#f8d7da;
            color:#721c24;
            padding:10px 12px;
            border-radius:8px;
            margin-bottom:15px;
            font-size:0.9rem;
        }
        .msg-ok {
            background:#d4edda;
            color:#155724;
            padding:10px 12px;
            border-radius:8px;
            margin-bottom:15px;
            font-size:0.9rem;
        }
        .field {
            margin-bottom:15px;
        }
        .field label {
            display:block;
            font-weight:600;
            margin-bottom:5px;
            color:#333;
        }
        .field input,
        .field textarea {
            width:100%;
            padding:9px 10px;
            border-radius:8px;
            border:1px solid #ced4da;
            font-size:0.95rem;
            box-sizing:border-box;
            resize: vertical;
        }
        .field input:focus,
        .field textarea:focus {
            outline:none;
            border-color:#a02040;
            box-shadow:0 0 0 2px rgba(160,32,64,0.15);
        }
        .field textarea {
            min-height:70px;
            max-height:200px;
        }
        .btn-primary {
            background:#a02040;
            color:white;
            padding:11px 20px;
            border:none;
            border-radius:999px;
            cursor:pointer;
            font-weight:600;
            font-size:0.95rem;
            width:100%;
            margin-top:5px;
        }
        .btn-primary:hover {
            background:#801933;
        }
        .back-link {
            display:inline-block;
            margin-top:15px;
            text-decoration:none;
            color:#007bff;
            font-size:0.9rem;
        }
        .back-link:hover {
            text-decoration:underline;
        }
        .badges {
            margin-top:8px;
            font-size:0.85rem;
        }
        .badge {
            display:inline-block;
            padding:3px 9px;
            border-radius:999px;
            background:#f1f3f5;
            color:#555;
            margin-right:6px;
            margin-bottom:4px;
        }
        .occupied-list {
            max-height:140px;
            overflow-y:auto;
            font-size:0.85rem;
            padding-left:18px;
            color:#555;
        }
        .hint {
            font-size:0.85rem;
            color:#777;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: -10px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .disabled-btn {
            background:#ccc !important;
            cursor: not-allowed !important;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="layout">
        <div class="card">
            <div class="hotel-header">
                <h1><?php echo htmlspecialchars($hotel['Name']); ?></h1>
                <p>📍 <?php echo htmlspecialchars($hotel['City']); ?> — <?php echo htmlspecialchars($hotel['Address']); ?></p>
            </div>

            <hr style="border:none;border-top:1px solid #eee;margin:15px 0;">

            <p class="section-title">Habitación seleccionada</p>
            <div class="summary-row">
                <span class="summary-label">Tipo:</span>
                <span class="summary-value"><?php echo htmlspecialchars($tipo['Name']); ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Capacidad máx.:</span>
                <span class="summary-value"><?php echo (int)$tipo['Guests']; ?> huéspedes</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Habitaciones libres para estas fechas:</span>
                <span class="summary-value"><?php echo (int)$max_rooms_pre; ?></span>
            </div>
            <div style="margin-top:10px;">
                <span class="price-highlight">
                    <?php echo number_format($precio_final, 2); ?> €/noche
                </span>
                <div class="price-note">
                    Precio por habitación y por noche.
                </div>
            </div>

            <div class="badges">
                <span class="badge">Usuario: <?php echo htmlspecialchars($userName); ?></span>
                <span class="badge">Email: <?php echo htmlspecialchars($userEmail); ?></span>
            </div>

            <hr style="border:none;border-top:1px solid #eee;margin:15px 0;">

            <p class="section-title">Fechas ya reservadas para este tipo</p>
            <?php if (count($rangosOcupados) === 0): ?>
                <p class="hint">Por ahora no hay reservas registradas para este tipo de habitación.</p>
            <?php else: ?>
                <p class="hint">Evita seleccionar fechas que se solapen con estos rangos:</p>
                <ul class="occupied-list">
                    <?php foreach ($rangosOcupados as $r): ?>
                        <li>Del <?php echo htmlspecialchars($r['start']); ?> al <?php echo htmlspecialchars($r['end']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="../../Publico/hotel.php?hotel_id=<?php echo (int)$hotel['Id']; ?>" class="back-link">← Volver al hotel</a>
        </div>

        <div class="card">
            <p class="section-title">Selecciona fechas y añade al carrito</p>

            <?php if ($cartError): ?>
                <div class="msg-error"><?php echo htmlspecialchars($cartError); ?></div>
            <?php endif; ?>

            <?php if ($cartSuccess): ?>
                <div class="msg-ok"><?php echo htmlspecialchars($cartSuccess); ?></div>
            <?php endif; ?>

            <form method="POST" action="../cart/add_reservation.php" id="booking-form">
                <input type="hidden" name="hotel_id" value="<?php echo (int)$hotel['Id']; ?>">
                <input type="hidden" name="room_type_id" value="<?php echo (int)$tipo['Id']; ?>">
                <input type="hidden" name="price_per_night" value="<?php echo $precio_final; ?>">

                <div class="field">
                    <label for="check_in">Fecha de entrada</label>
                    <input type="date" 
                           name="check_in" 
                           id="check_in" 
                           required 
                           min="<?php echo $hoy; ?>"
                           value="<?php echo htmlspecialchars($check_in_pre); ?>"
                           >
                </div>

                <div class="field">
                    <label for="check_out">Fecha de salida</label>
                    <input type="date" 
                           name="check_out" 
                           id="check_out" 
                           required 
                           min="<?php echo $manana; ?>"
                           value="<?php echo htmlspecialchars($check_out_pre); ?>"
                           >
                </div>
                
                <div class="error-message" id="date-overlap-error" style="display:none;">
                    ⚠️ El rango de fechas seleccionado se solapa con una reserva existente.
                </div>

                <div class="field">
                    <label for="num_rooms">Número de habitaciones</label>
                    <input 
                        type="number" 
                        name="num_rooms" 
                        id="num_rooms" 
                        min="1"
                        max="<?php echo (int)$max_rooms_pre; ?>"
                        value="1"
                        required
                    >
                    <small class="hint">Máximo permitido: <?php echo (int)$max_rooms_pre; ?> habitaciones para este tipo.</small>
                </div>

                <div class="field">
                    <label for="notes">Notas para el hotel (opcional)</label>
                    <textarea name="notes" id="notes" placeholder="Ej: Llegaré tarde por la noche, necesito cuna, etc."></textarea>
                </div>

                <p class="hint">
                    Esta acción solo añade la habitación a tu carrito.   
                    La reserva definitiva se realizará más adelante, al confirmar el carrito.
                </p>

                <button type="submit" class="btn-primary" id="add-to-cart-btn">
                    Añadir al carrito
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Pasar los rangos de reserva ocupados a JavaScript
    const occupiedRanges = <?php echo $rangosOcupados_json; ?>;
    const form = document.getElementById('booking-form');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const errorDiv = document.getElementById('date-overlap-error');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const numRoomsInput = document.getElementById('num_rooms');
    const maxRooms = <?php echo (int)$max_rooms_pre; ?>;

    // Función auxiliar para convertir fecha YYYY-MM-DD a objeto Date
    function parseDate(dateString) {
        const parts = dateString.split('-');
        // new Date(year, monthIndex, day) - monthIndex es 0-based
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    // Función CRÍTICA: Verificar si el rango solicitado solapa con un rango ocupado
    function checkOverlap() {
        errorDiv.style.display = 'none';
        addToCartBtn.classList.remove('disabled-btn');
        addToCartBtn.disabled = false;

        const checkInValue = checkInInput.value;
        const checkOutValue = checkOutInput.value;

        if (!checkInValue || !checkOutValue) {
            // No podemos validar sin fechas, pero permitimos el envío (la validación PHP se encargará)
            return;
        }

        const newStart = parseDate(checkInValue);
        const newEnd = parseDate(checkOutValue);

        // 1. Validación de orden de fechas (Check-out debe ser después de Check-in)
        if (newEnd <= newStart) {
            errorDiv.textContent = 'La salida debe ser posterior a la entrada.';
            errorDiv.style.display = 'block';
            addToCartBtn.classList.add('disabled-btn');
            addToCartBtn.disabled = true;
            return;
        }

        // 2. Validación de solapamiento con reservas existentes
        for (const range of occupiedRanges) {
            const occupiedStart = parseDate(range.start);
            const occupiedEnd = parseDate(range.end);

            // Criterio de solapamiento:
            // Ocupado si (nueva entrada < fecha fin ocupada) Y (nueva salida > fecha inicio ocupada)
            if (newStart < occupiedEnd && newEnd > occupiedStart) {
                errorDiv.textContent = '⚠️ El rango de fechas seleccionado se solapa con una reserva existente. Por favor, selecciona otras fechas.';
                errorDiv.style.display = 'block';
                addToCartBtn.classList.add('disabled-btn');
                addToCartBtn.disabled = true;
                return;
            }
        }
    }
    
    // Asignar el listener a los cambios de fecha
    checkInInput.addEventListener('change', checkOverlap);
    checkOutInput.addEventListener('change', checkOverlap);

    // Ejecutar al cargar la página si las fechas ya están pre-rellenas
    if (checkInInput.value && checkOutInput.value) {
        checkOverlap();
    }

    // Validar y limitar el número de habitaciones al máximo disponible
    if (numRoomsInput) {
        numRoomsInput.addEventListener('input', function (e) {
            let value = parseInt(e.target.value, 10);

            if (isNaN(value) || value < 1) {
                e.target.value = 1;
                return;
            }

            if (value > maxRooms) {
                e.target.value = maxRooms;
            }
        });

        numRoomsInput.addEventListener('blur', function (e) {
            let value = parseInt(e.target.value, 10);

            if (isNaN(value) || value < 1) {
                e.target.value = 1;
            } else if (value > maxRooms) {
                e.target.value = maxRooms;
            }
        });
    }
</script>

</body>
</html>