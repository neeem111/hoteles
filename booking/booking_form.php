<?php
session_start();
include __DIR__ . '/../conexion.php';

// Debe estar logueado para poder añadir al carrito
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '../Cliente/index.php';
    header("Location: ../login.php?error=Debes+iniciar+sesion+para+continuar");
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$userName  = $_SESSION['user_name']  ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

// --- 1. Leer parámetros ---
$hotel_id     = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_type_id = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;

if ($hotel_id <= 0 || $room_type_id <= 0) {
    header("Location: ../Cliente/index.php?error=Hotel+o+habitacion+no+valido");
    exit;
}

// --- 2. Cargar datos del hotel ---
$sqlHotel = "SELECT Id, Name, City, Address FROM Hotels WHERE Id = ?";
$stmtH = $conn->prepare($sqlHotel);
$stmtH->bind_param("i", $hotel_id);
$stmtH->execute();
$resH = $stmtH->get_result();
$hotel = $resH->fetch_assoc();

if (!$hotel) {
    header("Location: ../Cliente/index.php?error=Hotel+no+encontrado");
    exit;
}

// --- 3. Cargar datos del tipo de habitación + comprobar que hay rooms disponibles ---
$sqlTipo = "SELECT 
                rt.Id,
                rt.Name,
                rt.Guests,
                rt.CostPerNight,
                COUNT(r.Id) AS AvailableRooms
            FROM RoomType rt
            INNER JOIN Rooms r ON r.Id_RoomType = rt.Id
            WHERE r.Id_Hotel = ?
              AND rt.Id = ?
              AND r.Available = 1
            GROUP BY rt.Id, rt.Name, rt.Guests, rt.CostPerNight";

$stmtT = $conn->prepare($sqlTipo);
$stmtT->bind_param("ii", $hotel_id, $room_type_id);
$stmtT->execute();
$resT = $stmtT->get_result();
$tipo = $resT->fetch_assoc();

if (!$tipo || (int)$tipo['AvailableRooms'] === 0) {
    header("Location: ../hotel.php?hotel_id=" . $hotel_id . "&error=Sin+habitaciones+disponibles");
    exit;
}

// --- 4. Obtener rangos de fechas ya reservadas para este hotel + tipo ---
// Aquí miramos Reservation + Reservation_Rooms + Rooms.
// Suponemos que Status='Confirmada' indica reserva activa.
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
    $rangosOcupados[] = $row; // ['CheckIn_Date' => '...', 'CheckOut_Date' => '...']
}

// Fechas mínimas para el datepicker
$hoy     = (new DateTime())->format('Y-m-d');
$manana  = (new DateTime('+1 day'))->format('Y-m-d');

// Mensajes del carrito o validaciones (por si luego los usamos)
$cartError   = $_SESSION['cart_error']   ?? '';
$cartSuccess = $_SESSION['cart_success'] ?? '';
unset($_SESSION['cart_error'], $_SESSION['cart_success']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar fechas - <?php echo htmlspecialchars($hotel['Name']); ?></title>
    <link rel="stylesheet" href="../styleCarlos.css">
    <style>
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
    </style>
</head>
<body>

<div class="wrapper">
    <div class="layout">
        <!-- Columna izquierda: info hotel + tipo -->
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
            <div style="margin-top:10px;">
                <span class="price-highlight">
                    <?php echo number_format($tipo['CostPerNight'], 2); ?> €/noche
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
                        <li>Del <?php echo htmlspecialchars($r['CheckIn_Date']); ?> al <?php echo htmlspecialchars($r['CheckOut_Date']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="../hotel.php?hotel_id=<?php echo (int)$hotel['Id']; ?>" class="back-link">← Volver al hotel</a>
        </div>

        <!-- Columna derecha: formulario -->
        <div class="card">
            <p class="section-title">Selecciona fechas y añade al carrito</p>

            <?php if ($cartError): ?>
                <div class="msg-error"><?php echo htmlspecialchars($cartError); ?></div>
            <?php endif; ?>

            <?php if ($cartSuccess): ?>
                <div class="msg-ok"><?php echo htmlspecialchars($cartSuccess); ?></div>
            <?php endif; ?>

            <form method="POST" action="../cart/add_reservation.php">
                <input type="hidden" name="hotel_id" value="<?php echo (int)$hotel['Id']; ?>">
                <input type="hidden" name="room_type_id" value="<?php echo (int)$tipo['Id']; ?>">
                <input type="hidden" name="price_per_night" value="<?php echo (float)$tipo['CostPerNight']; ?>">

                <div class="field">
                    <label for="check_in">Fecha de entrada</label>
                    <input type="date" name="check_in" id="check_in" required min="<?php echo $hoy; ?>">
                </div>

                <div class="field">
                    <label for="check_out">Fecha de salida</label>
                    <input type="date" name="check_out" id="check_out" required min="<?php echo $manana; ?>">
                </div>

                <div class="field">
                    <label for="notes">Notas para el hotel (opcional)</label>
                    <textarea name="notes" id="notes" placeholder="Ej: Llegaré tarde por la noche, necesito cuna, etc."></textarea>
                </div>

                <p class="hint">
                    Esta acción solo añade la habitación a tu carrito.  
                    La reserva definitiva se realizará más adelante, al confirmar el carrito.
                </p>

                <button type="submit" class="btn-primary">
                    Añadir al carrito
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
