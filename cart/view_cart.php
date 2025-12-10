<?php
session_start();
include('../conexion.php');

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$hoteles_en_carrito = [];
if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT Id, Name, City, Address FROM Hotels WHERE Id IN ($placeholders)";
    if ($stmt = $conn->prepare($sql)) {
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $hoteles_en_carrito[$row['Id']] = $row;
        }
        $stmt->close();
    }
}

$total = 0;
foreach ($cart as $id => $item) {
    $nights     = isset($item['nights']) ? (int)$item['nights'] : 1;
    $roomsCount = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
    $total += $item['precio'] * $nights * $roomsCount;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tu Carrito - Hoteles Nueva España</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 3%; background:#f1f3f5 }
        .cart { max-width: 95%; margin: 3% auto; background: #fff; padding:2.5%; border-radius:1rem; box-shadow:0 0.8rem 2rem rgba(0,0,0,0.08) }
        table { width:100%; border-collapse: collapse; font-size:1rem }
        th, td { padding:1%; border-bottom:1px solid #e9ecef; text-align:left }
        th { background:#f8f9fa; font-weight:700 }
        .actions { text-align:right }
        .msg { padding:0.8%; border-radius:0.6rem; margin-bottom:1%; font-size:0.95rem }
        .success { background:#d4edda; color:#155724 }
        .error { background:#f8d7da; color:#721c24 }
        .btn { display:inline-block; padding:0.6% 1%; border-radius:0.6rem; text-decoration:none; color:#fff; font-size:0.95rem }
        .btn-primary { background:#007bff }
        .btn-ghost { background:#6c757d }
        .price { font-weight:700 }
        input[type=number] { width: 12%; min-width: 60px }
        .btn-checkout {
            background:#a02040;
            color:white;
            padding:11px 20px;
            border:none;
            border-radius:999px;
            cursor:pointer;
            font-weight:600;
            font-size:0.95rem;
            text-decoration:none;
            display:inline-block;
            margin-left:8px;
        }
        .btn-checkout:hover:not(:disabled) {
            background:#801933;
        }
        .btn-checkout:disabled {
            opacity:0.5;
            cursor:not-allowed;
        }
        @media (max-width: 768px) {
            input[type=number] { width: 28%; }
            th, td { padding:1.6%; font-size:0.95rem }
            .actions { display:block; text-align:right; margin-top:3% }
        }
    </style>
</head>
<body>
    <div class="cart">
        <h1 style="margin-top:0">Carrito de Compras</h1>

        <?php if (isset($_SESSION['cart_success'])): ?>
            <div class="msg success"><?php echo htmlspecialchars($_SESSION['cart_success']); ?></div>
            <?php unset($_SESSION['cart_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['cart_error'])): ?>
            <div class="msg error"><?php echo htmlspecialchars($_SESSION['cart_error']); ?></div>
            <?php unset($_SESSION['cart_error']); ?>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <p>Tu carrito está vacío. <a href="../Cliente/index.php">Volver a la tienda</a></p>
        <?php else: ?>
            <form method="post" action="update_cart.php">
            <table>
                <thead>
                    <tr>
                        <th style="width:22%">Hotel</th>
                        <th style="width:10%">Ciudad</th>
                        <th style="width:10%">Precio / noche / Habitacion</th>
                        <th style="width:15%">Fecha de entrada</th>
                        <th style="width:15%">Fecha de salida</th>
                        <th style="width:8%">Noches</th>
                        <th style="width:8%">Habitaciones</th>
                        <th style="width:10%">Subtotal</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $item): ?>
                        <tr>
                            <td><?php echo isset($hoteles_en_carrito[$id]) ? htmlspecialchars($hoteles_en_carrito[$id]['Name']) : 'Hotel #' . $id; ?></td>
                            <td><?php echo isset($hoteles_en_carrito[$id]) ? htmlspecialchars($hoteles_en_carrito[$id]['City']) : '-'; ?></td>
                            <?php
                                $nights     = isset($item['nights']) ? (int)$item['nights'] : 1;
                                $roomsCount = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                                $lineTotal  = $item['precio'] * $nights * $roomsCount;
                            ?>
                            <td class="price">$<?php echo number_format($item['precio'], 2); ?></td>
                            <td>
                                <input 
                                    type="date" 
                                    name="check_in[<?php echo intval($id); ?>]" 
                                    value="<?php echo isset($item['check_in']) ? htmlspecialchars($item['check_in']) : ''; ?>" 
                                    style="width:100%; padding:8px; font-size:14px; box-sizing:border-box"
                                >
                            </td>
                            <td>
                                <input 
                                    type="date" 
                                    name="check_out[<?php echo intval($id); ?>]" 
                                    value="<?php echo isset($item['check_out']) ? htmlspecialchars($item['check_out']) : ''; ?>" 
                                    style="width:100%; padding:8px; font-size:14px; box-sizing:border-box"
                                >
                            </td>
                            <td>
                                <span id="nights-<?php echo intval($id); ?>" style="display:block; text-align:center; padding:8px;"><?php echo $nights; ?></span>
                                <input type="hidden" name="nights[<?php echo intval($id); ?>]" id="nights-hidden-<?php echo intval($id); ?>" value="<?php echo $nights; ?>">
                            </td>
                            <td><?php echo $roomsCount; ?></td>
                            <td class="price">$<?php echo number_format($lineTotal, 2); ?></td>
                            <td><a href="remove_from_cart.php?hotel_id=<?php echo intval($id); ?>" onclick="return confirm('¿Deseas eliminar este elemento del carrito?');" style="color:#dc3545; text-decoration:none; font-weight:600; cursor:pointer;">Eliminar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:18px; display:flex; justify-content:space-between; align-items:center">
                <div>
                    <a href="../Cliente/index.php" class="btn btn-ghost">← Seguir comprando</a>
                </div>
                <div class="actions">
                    <span style="font-size:18px; margin-right:14px">Total: <strong>$<?php echo number_format($total, 2); ?></strong></span>
                    <button type="button" class="btn-checkout" id="checkout-btn" onclick="if(!this.disabled) window.location.href='checkout.php';">
                        Proceder al Pago
                    </button>
                </div>
            </div>
            </form>
        <?php endif; ?>
    </div>
    <script>
        function updateNights(element, hotelId) {
            const row = element.closest('tr');
            const checkInInput = row.querySelector('input[name="check_in[' + hotelId + ']"]');
            const checkOutInput = row.querySelector('input[name="check_out[' + hotelId + ']"]');
            const nightsSpan = row.querySelector('#nights-' + hotelId);
            const nightsHidden = row.querySelector('#nights-hidden-' + hotelId);

            if (checkInInput.value && checkOutInput.value) {
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);

                if (checkOut <= checkIn) {
                    nightsSpan.textContent = 'Salida debe ser posterior a entrada';
                    nightsSpan.style.color = '#dc3545';
                    document.getElementById('checkout-btn').disabled = true;
                    return;
                }

                const diffTime = checkOut - checkIn;
                const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (nights > 0) {
                    nightsSpan.textContent = nights;
                    nightsSpan.style.color = 'inherit';
                    nightsHidden.value = nights;
                    updateSubtotal(row, nights);
                } else {
                    nightsSpan.textContent = 'Inválido';
                    nightsSpan.style.color = '#dc3545';
                    document.getElementById('checkout-btn').disabled = true;
                }
            }
        }

        function updateSubtotal(row, nights) {
            const priceCells = row.querySelectorAll('.price');
            const priceText = priceCells[0].textContent.replace('$', '').trim();
            const price = parseFloat(priceText);
            
            const nightsSpan = row.querySelector('[id^="nights-"]');
            const roomsCell = nightsSpan.closest('td').nextElementSibling;
            const rooms = parseInt(roomsCell.textContent);
            
            const subtotal = price * nights * rooms;
            priceCells[1].textContent = '$' + subtotal.toFixed(2);
            
            updateTotalPrice();
        }

        function updateTotalPrice() {
            const rows = document.querySelectorAll('tbody tr');
            let totalPrice = 0;

            rows.forEach(row => {
                const priceCells = row.querySelectorAll('.price');
                const subtotalText = priceCells[1].textContent.replace('$', '').trim();
                const subtotal = parseFloat(subtotalText) || 0;
                totalPrice += subtotal;
            });

            const totalSpan = document.querySelector('.actions strong');
            if (totalSpan) {
                totalSpan.textContent = '$' + totalPrice.toFixed(2);
            }

            checkCheckoutButtonStatus();
        }

        function checkCheckoutButtonStatus() {
            const nightsSpans = document.querySelectorAll('[id^="nights-"]');
            let hasErrors = false;

            nightsSpans.forEach(span => {
                const text = span.textContent.trim();
                if (text.includes('Salida') || text.includes('Inválido')) {
                    hasErrors = true;
                }
            });

            document.getElementById('checkout-btn').disabled = hasErrors;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const today = new Date().toISOString().split('T')[0];
            
            dateInputs.forEach(input => {
                input.setAttribute('min', today);
                input.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const hotelId = this.name.match(/\d+/)[0];
                    updateNights(this, hotelId);
                });
            });

            checkCheckoutButtonStatus();
        });
    </script>
</body>
</html>
