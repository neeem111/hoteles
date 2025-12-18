<?php
session_start();
// Ajusta la ruta si view_cart.php está en una subcarpeta (ej: Cliente/cart/)
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
                            <tr>
                                <td data-label="Hotel">
                                    <div class="hotel-info">
                                        <h3><?php echo htmlspecialchars($nombreHotel); ?></h3>
                                        <span>📍 <?php echo htmlspecialchars($ciudad); ?></span>
                                        <?php if(isset($item['check_in'])): ?>
                                            <br><small style="color:#adb5bd">📅 <?php echo $item['check_in']; ?> al <?php echo $item['check_out']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Precio/Noche" class="price">$<?php echo number_format($item['precio'], 2); ?></td>
                                <td data-label="Noches"><?php echo $nights; ?></td>
                                <td data-label="Habitaciones">
                                    <input type="number" name="cantidad[<?php echo intval($id); ?>]" value="<?php echo $roomsCount; ?>" min="0">
                                </td>
                                <td data-label="Total" class="price" style="color: var(--color-primary);">$<?php echo number_format($lineTotal, 2); ?></td>
                                <td style="text-align: right;">
                                    <a href="remove_from_cart.php?hotel_id=<?php echo intval($id); ?>" class="btn-remove">🗑️ Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align: right; margin-bottom: 20px;">
                <button type="submit" class="btn btn-update">🔄 Recalcular Precios</button>
            </div>
        </form>
        
        <div class="cart-footer">
            <div>
                <a href="../Cliente/index.php" class="btn btn-ghost">← Seguir Buscando</a>
            </div>
            
            <div class="actions">
                <div class="total-price">
                    Total a Pagar: <strong>$<?php echo number_format($total, 2); ?></strong>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="https://www.sandbox.paypal.com/es/cgi-bin/webscr" method="post">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="sb-u5grq48018566@business.example.com">
                        <input type="hidden" name="currency_code" value="EUR">
                        <input type="hidden" name="item_name" value="Reserva Hoteles NESL - Compra Múltiple">
                        <input type="hidden" name="amount" value="<?php echo $total; ?>">
                        
                        <input type="hidden" name="return" value="http://localhost/hoteles/Cliente/cart/checkout.php">
                        <input type="hidden" name="cancel_return" value="http://localhost/hoteles/Cliente/cart/pago_cancelado.php">
                        
                        <button type="submit" class="btn btn-paypal">
                            Pagar con PayPal 💳
                        </button>
                    </form>

                <?php else: ?>
                    <a href="../auth/login.php?error=Inicia+sesion+para+pagar" class="btn btn-login">
                        🔐 Iniciar Sesión para Pagar
                    </a>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

</body>
</html>