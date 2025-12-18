<?php
session_start();
// Ajusta la ruta si es necesario
include('../conexion.php'); 

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$hoteles_en_carrito = [];
if (!empty($cart)) {
    $ids = array_keys($cart);
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
// Calcular total estrictamente desde la sesión
foreach ($cart as $id => $item) {
    $nights     = isset($item['nights']) ? (int)$item['nights'] : 1;
    $roomsCount = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
    $total += $item['precio'] * $nights * $roomsCount;
}

// --- FUNCIÓN DE UTILIDAD PARA FORMATO DE FECHA ---
function format_date_es($date) {
    if (empty($date)) return 'N/D';
    try {
        return (new DateTime($date))->format('d/m/Y');
    } catch (Exception $e) {
        return 'Inválida';
    }
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
            --color-primary: #a02040;
            --color-dark: #343a40;
            --color-light: #f8f9fa;
            --color-text: #495057;
            --color-border: #e9ecef;
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
            max-width: 1200px;
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

        .msg { 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            font-weight: 500;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .table-responsive { overflow-x: auto; }
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
            padding: 18px 15px;
            border-bottom: 2px solid var(--color-border);
            text-align: left;
        }
        td { 
            padding: 20px 15px; 
            vertical-align: middle;
            border-bottom: 1px solid var(--color-border);
        }
        
        .hotel-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: var(--color-dark); }
        .hotel-info span { font-size: 0.9rem; color: #868e96; }
        
        .price { font-weight: 700; color: var(--color-dark); font-size: 1.1rem; }

        .btn-remove { 
            color: #dc3545; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 500; 
        }
        .btn-remove:hover { text-decoration: underline; }

        .cart-footer {
            background: var(--color-light);
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-price { font-size: 1.5rem; color: var(--color-dark); }
        .total-price strong { color: var(--color-primary); font-size: 2rem; }

        .actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .btn-ghost { background: transparent; color: #6c757d; border: 2px solid #dee2e6; }
        .btn-paypal { background: #0070ba; color: white; }
        .btn-login { background: var(--color-primary); color: white; }
        .btn-card { background: #f0f0f0; color: var(--color-dark); border: 2px solid #ddd; }

        @media (max-width: 768px) {
            .cart-footer { flex-direction: column; text-align: center; gap: 20px; }
            .actions { flex-direction: column; width: 100%; }
            th { display: none; }
            td { display: block; text-align: right; padding: 10px 0; }
            td::before { content: attr(data-label); float: left; font-weight: bold; }
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
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 1.2rem; color: #868e96; margin-bottom: 20px;">Tu carrito está vacío actualmente.</p>
            <a href="../Cliente/index.php" class="btn btn-login">Explorar Hoteles</a>
        </div>
    <?php else: ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th width="35%">Hotel & Fechas</th>
                        <th width="15%">Precio Noche</th>
                        <th width="10%">Noches</th>
                        <th width="10%">Habitaciones</th>
                        <th width="20%">Subtotal</th>
                        <th width="10%">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $item): ?>
                        <?php
                            $hotelInfo = isset($hoteles_en_carrito[$id]) ? $hoteles_en_carrito[$id] : null;
                            $nombreHotel = $hotelInfo ? $hotelInfo['Name'] : 'Hotel #' . $id;
                            $nights     = isset($item['nights']) ? (int)$item['nights'] : 1;
                            $roomsCount = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                            $lineTotal  = $item['precio'] * $nights * $roomsCount;
                            
                            $check_in_es = format_date_es($item['check_in'] ?? null);
                            $check_out_es = format_date_es($item['check_out'] ?? null);
                        ?>
                        <tr>
                            <td data-label="Hotel & Fechas">
                                <div class="hotel-info">
                                    <h3><?php echo htmlspecialchars($nombreHotel); ?></h3>
                                    <span>📅 Entrada: <strong><?php echo htmlspecialchars($check_in_es); ?></strong></span><br>
                                    <span>📅 Salida: <strong><?php echo htmlspecialchars($check_out_es); ?></strong></span>
                                    <p style="color:#a02040; margin-top:5px; font-size:0.85em;">
                                        * No editable. Para cambiar fechas, elimine y añada de nuevo.
                                    </p>
                                </div>
                            </td>
                            <td data-label="Precio/Noche" class="price">$<?php echo number_format($item['precio'], 2); ?></td>
                            
                            <td data-label="Noches" style="font-weight: bold;"><?php echo $nights; ?></td>
                            <td data-label="Habitaciones" style="font-weight: bold;"><?php echo $roomsCount; ?></td>

                            <td data-label="Subtotal" class="price" style="color: var(--color-primary);">$<?php echo number_format($lineTotal, 2); ?></td>
                            
                            <td style="text-align: right;">
                                <a href="remove_from_cart.php?hotel_id=<?php echo intval($id); ?>" 
                                   class="btn-remove"
                                   onclick="return confirm('¿Deseas eliminar este hotel del carrito?');">
                                    🗑️ Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-footer">
            <div>
                <a href="../Cliente/index.php" class="btn btn-ghost">← Seguir Buscando</a>
            </div>
            
            <div class="actions">
                <div class="total-price">
                    Total: <strong>$<?php echo number_format($total, 2); ?></strong>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="#" id="pay-card-btn" class="btn btn-card">💳 Pagar con Tarjeta</a>
                    
                    <form action="https://www.sandbox.paypal.com/es/cgi-bin/webscr" method="post" id="paypal-form">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="sb-u5grq48018566@business.example.com">
                        <input type="hidden" name="currency_code" value="EUR">
                        <input type="hidden" name="item_name" value="Reserva Hoteles NESL - Compra Múltiple">
                        <input type="hidden" name="amount" value="<?php echo number_format($total, 2, '.', ''); ?>">
                        <input type="hidden" name="return" value="http://localhost/hoteles/Cliente/cart/checkout.php?status=success_paypal">
                        <input type="hidden" name="custom" value="<?php echo $_SESSION['user_id']; ?>">
                        <input type="hidden" name="cancel_return" value="http://localhost/hoteles/Cliente/cart/pago_cancelado.php">
                        <button type="submit" class="btn btn-paypal">Pagar con PayPal 💳</button>
                    </form>
                <?php else: ?>
                    <a href="../auth/login.php?error=Inicia+sesion+para+pagar" class="btn btn-login">🔐 Iniciar Sesión para Pagar</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="payment-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:2000;">
    <div style="max-width:450px; margin:10% auto; background:white; padding:30px; border-radius:12px; position:relative;">
        <h2 style="margin-top:0; color:var(--color-primary);">Detalles de la Tarjeta</h2>
        <p>Total: <strong>$<?php echo number_format($total, 2); ?></strong></p>
        <form id="card-payment-form">
            <div style="margin-bottom:15px;">
                <label style="display:block;">Número de Tarjeta</label>
                <input type="text" required placeholder="XXXX XXXX XXXX XXXX" maxlength="16" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <input type="text" placeholder="MM/AA" maxlength="5" style="width:50%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                <input type="text" placeholder="CVV" maxlength="4" style="width:50%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>
            <button type="submit" class="btn" style="background:var(--color-primary); color:white; width:100%;">Confirmar Pago</button>
            <button type="button" id="close-modal-btn" style="background:none; border:none; color:#999; width:100%; margin-top:10px; cursor:pointer;">Cancelar</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const payCardBtn = document.getElementById('pay-card-btn');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const paymentModal = document.getElementById('payment-modal');

        if (payCardBtn) {
            payCardBtn.addEventListener('click', (e) => { e.preventDefault(); paymentModal.style.display = 'block'; });
        }
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => { paymentModal.style.display = 'none'; });
        }
        document.getElementById('card-payment-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            window.location.href = 'checkout.php?payment_method=card_successful';
        });
    });
</script>

</body>
</html>