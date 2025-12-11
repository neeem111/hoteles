<?php
session_start();
// Ajusta la ruta si view_cart.php está en una subcarpeta (ej: cart/)
include('../conexion.php'); 

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$hoteles_en_carrito = [];
if (!empty($cart)) {
    $ids = array_keys($cart);
    // Verificar que hay IDs válidos para evitar errores SQL
    if (count($ids) > 0) {
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
}

$total = 0;
// Calcular total
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --color-primary: #a02040; /* Borgoña */
            --color-dark: #343a40;
            --color-light: #f8f9fa;
            --color-text: #495057;
            --color-border: #e9ecef;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.12);
        }

        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background-color: #f0f2f5; 
            color: var(--color-text);
            margin: 0;
            padding: 40px 20px;
        }

        .cart-container { 
            max-width: 1100px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: var(--shadow-md);
        }

        h1 {
            color: var(--color-primary);
            font-size: 2rem;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--color-light);
            padding-bottom: 15px;
        }

        /* Alertas */
        .msg { 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            font-weight: 500;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Tabla */
        .table-responsive {
            overflow-x: auto;
        }
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            margin-bottom: 30px;
        }
        th { 
            background: var(--color-light); 
            color: var(--color-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 18px 15px;
            border-bottom: 2px solid var(--color-border);
        }
        td { 
            padding: 20px 15px; 
            vertical-align: middle;
            border-bottom: 1px solid var(--color-border);
        }
        tr:last-child td { border-bottom: none; }
        
        /* Elementos de la tabla */
        .hotel-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: var(--color-dark); }
        .hotel-info span { font-size: 0.9rem; color: #868e96; }
        
        .price { font-weight: 700; color: var(--color-dark); font-size: 1.1rem; }
        
        input[type=number] { 
            width: 60px; 
            padding: 8px; 
            border: 2px solid var(--color-border); 
            border-radius: 6px; 
            text-align: center; 
            font-weight: 600;
            transition: border-color 0.2s;
        }
        input[type=number]:focus { border-color: var(--color-primary); outline: none; }

        .btn-remove { 
            color: #dc3545; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .btn-remove:hover { text-decoration: underline; opacity: 0.8; }

        /* Pie del carrito */
        .cart-footer {
            background: var(--color-light);
            padding: 30px;
            border-radius: 12px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .total-price {
            font-size: 1.5rem;
            color: var(--color-dark);
        }
        .total-price strong { color: var(--color-primary); font-size: 2rem; }

        .actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        /* Botones */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 1rem;
        }
        .btn-ghost { background: transparent; color: #6c757d; border: 2px solid #dee2e6; }
        .btn-ghost:hover { border-color: #adb5bd; color: var(--color-dark); }
        
        .btn-update { background: var(--color-dark); color: white; }
        .btn-update:hover { background: #23272b; transform: translateY(-1px); }

        .btn-paypal { 
            background: #0070ba; 
            color: white; 
            box-shadow: 0 4px 15px rgba(0, 112, 186, 0.3);
        }
        .btn-paypal:hover { 
            background: #005ea6; 
            box-shadow: 0 6px 20px rgba(0, 112, 186, 0.4);
            transform: translateY(-2px);
        }
        .btn-login { background: var(--color-primary); color: white; }

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
            .cart-footer { flex-direction: column; text-align: center; }
            .actions { flex-direction: column; width: 100%; }
            .btn { width: 100%; }
            th { display: none; } /* Ocultar cabeceras en móvil */
            td { display: block; text-align: right; padding: 10px 0; border-bottom: none; }
            td::before { content: attr(data-label); float: left; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; color: #868e96; }
            tr { display: block; background: #fff; border: 1px solid var(--color-border); padding: 20px; margin-bottom: 20px; border-radius: 8px; }
            .hotel-info { text-align: right; }
        }
    </style>
</head>
<body>

<div class="cart-container">
    <h1>🛒 Tu Carrito de Reservas</h1>

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
    <?php if (empty($cart)): ?>
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 1.2rem; color: #868e96; margin-bottom: 20px;">Tu carrito está vacío actualmente.</p>
            <a href="../Cliente/index.php" class="btn btn-login">Explorar Hoteles</a>
        </div>
    <?php else: ?>

        <form method="post" action="update_cart.php">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="40%">Hotel & Habitación</th>
                            <th width="15%">Precio Noche</th>
                            <th width="10%">Noches</th>
                            <th width="15%">Habitaciones</th>
                            <th width="15%">Total</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $id => $item): ?>
                            <?php
                                $hotelInfo = isset($hoteles_en_carrito[$id]) ? $hoteles_en_carrito[$id] : null;
                                $nombreHotel = $hotelInfo ? $hotelInfo['Name'] : 'Hotel #' . $id;
                                $ciudad = $hotelInfo ? $hotelInfo['City'] : '';
                                
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

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="https://www.sandbox.paypal.com/es/cgi-bin/webscr" method="post">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="sb-u5grq48018566@business.example.com">
                        <input type="hidden" name="currency_code" value="EUR">
                        <input type="hidden" name="item_name" value="Reserva Hoteles NESL - Compra Múltiple">
                        <input type="hidden" name="amount" value="<?php echo $total; ?>">
                        
                        <input type="hidden" name="return" value="http://localhost/hoteles/cart/checkout.php">
                        <input type="hidden" name="cancel_return" value="http://localhost/hoteles/cart/pago_cancelado.php">
                        
                        <button type="submit" class="btn btn-paypal">
                            Pagar con PayPal 💳
                        </button>
                    </form>

                <?php else: ?>
                    <a href="../login.php?error=Inicia+sesion+para+pagar" class="btn btn-login">
                        🔐 Iniciar Sesión para Pagar
                    </a>
                <?php endif; ?>
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