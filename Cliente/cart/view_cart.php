<?php
session_start();
// Ajusta la ruta si es necesario
include('../../Config/conexion.php'); 

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
    $total += $item['precio'] * $nights * $roomsCount + ( $item['precio'] * $nights * $roomsCount) *0.21;
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
            <a href="../index.php" class="btn btn-login">Explorar Hoteles</a>
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
                <a href="../index.php" class="btn btn-ghost">← Seguir Buscando</a>
            </div>
            
            <div class="actions">
                <div class="total-price">
                    Total (IVA incluido): <strong>$<?php echo number_format($total, 2); ?></strong>
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
                        <input type="hidden" name="cancel_return" value="http://localhost/hoteles/Cliente/cart/view_cart.php">
                        <button type="submit" class="btn btn-paypal">Pagar con PayPal 💳</button>
                    </form>
                <?php else: ?>
                    <a href="../../auth/login.php?error=Inicia+sesion+para+pagar" class="btn btn-login">🔐 Iniciar Sesión para Pagar</a>
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
                <label style="display:block; margin-bottom:5px; font-weight:600;">Número de Tarjeta</label>
                <input type="text" id="card-number" name="card_number" required placeholder="1234 5678 9012 3456" maxlength="19" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:16px; letter-spacing:2px;">
                <span id="card-number-error" style="color:#dc3545; font-size:0.85rem; display:none;"></span>
            </div>
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Fecha Expiración</label>
                    <input type="text" id="card-expiry" name="card_expiry" required placeholder="MM/AA" maxlength="5" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:16px;">
                    <span id="card-expiry-error" style="color:#dc3545; font-size:0.85rem; display:none;"></span>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">CVV</label>
                    <input type="text" id="card-cvv" name="card_cvv" required placeholder="123" maxlength="4" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:16px;">
                    <span id="card-cvv-error" style="color:#dc3545; font-size:0.85rem; display:none;"></span>
                </div>
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
        const cardNumberInput = document.getElementById('card-number');
        const cardExpiryInput = document.getElementById('card-expiry');
        const cardCvvInput = document.getElementById('card-cvv');
        const cardPaymentForm = document.getElementById('card-payment-form');

        // Abrir modal
        if (payCardBtn) {
            payCardBtn.addEventListener('click', (e) => { 
                e.preventDefault(); 
                paymentModal.style.display = 'block'; 
            });
        }

        // Cerrar modal
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => { 
                paymentModal.style.display = 'none';
                // Limpiar formulario al cerrar
                cardPaymentForm.reset();
                clearErrors();
            });
        }

        // Cerrar modal al hacer clic fuera
        paymentModal.addEventListener('click', (e) => {
            if (e.target === paymentModal) {
                paymentModal.style.display = 'none';
                cardPaymentForm.reset();
                clearErrors();
            }
        });

        // Función para mostrar errores
        function showError(inputId, errorId, message) {
            const input = document.getElementById(inputId);
            const errorSpan = document.getElementById(errorId);
            input.style.borderColor = '#dc3545';
            errorSpan.textContent = message;
            errorSpan.style.display = 'block';
        }

        // Función para limpiar errores
        function clearError(inputId, errorId) {
            const input = document.getElementById(inputId);
            const errorSpan = document.getElementById(errorId);
            input.style.borderColor = '#ccc';
            errorSpan.style.display = 'none';
        }

        function clearErrors() {
            clearError('card-number', 'card-number-error');
            clearError('card-expiry', 'card-expiry-error');
            clearError('card-cvv', 'card-cvv-error');
        }

        // Validar número de tarjeta (solo números, 16 dígitos)
        function validateCardNumber(value) {
            const digitsOnly = value.replace(/\s/g, '');
            return /^\d{16}$/.test(digitsOnly);
        }

        // Formatear número de tarjeta con espacios cada 4 dígitos
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, ''); // Eliminar espacios
                value = value.replace(/\D/g, ''); // Solo números
                
                // Limitar a 16 dígitos
                if (value.length > 16) {
                    value = value.substring(0, 16);
                }
                
                // Agregar espacios cada 4 dígitos
                let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formatted;
                
                // Validación en tiempo real
                if (value.length > 0) {
                    if (value.length < 16) {
                        showError('card-number', 'card-number-error', 'El número de tarjeta debe tener 16 dígitos');
                    } else if (!validateCardNumber(formatted)) {
                        showError('card-number', 'card-number-error', 'El número de tarjeta no es válido');
                    } else {
                        clearError('card-number', 'card-number-error');
                    }
                } else {
                    clearError('card-number', 'card-number-error');
                }
            });

            cardNumberInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/\s/g, '');
                if (value.length > 0 && !validateCardNumber(value)) {
                    showError('card-number', 'card-number-error', 'El número de tarjeta debe tener 16 dígitos');
                }
            });
        }

        // Validar fecha de expiración (MM/AA, no caducada)
        function validateExpiry(value) {
            const parts = value.split('/');
            if (parts.length !== 2) return false;
            
            const month = parseInt(parts[0], 10);
            const year = parseInt(parts[1], 10);
            
            // Validar mes (01-12)
            if (month < 1 || month > 12) return false;
            
            // Validar año (2 dígitos, convertir a 4)
            const currentYear = new Date().getFullYear() % 100;
            const currentMonth = new Date().getMonth() + 1;
            const fullYear = 2000 + year;
            const currentFullYear = new Date().getFullYear();
            
            // Verificar si está caducada
            if (fullYear < currentFullYear) return false;
            if (fullYear === currentFullYear && month < currentMonth) return false;
            
            return true;
        }

        // Formatear fecha de expiración (MM/AA)
        if (cardExpiryInput) {
            cardExpiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Solo números
                
                // Limitar a 4 dígitos
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }
                
                // Agregar barra después de 2 dígitos
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2);
                }
                
                e.target.value = value;
                
                // Validación en tiempo real
                if (value.length > 0) {
                    if (value.length < 5) {
                        showError('card-expiry', 'card-expiry-error', 'Formato: MM/AA');
                    } else if (!validateExpiry(value)) {
                        const parts = value.split('/');
                        const month = parseInt(parts[0], 10);
                        if (month < 1 || month > 12) {
                            showError('card-expiry', 'card-expiry-error', 'El mes debe estar entre 01 y 12');
                        } else {
                            showError('card-expiry', 'card-expiry-error', 'La tarjeta está caducada o la fecha no es válida');
                        }
                    } else {
                        clearError('card-expiry', 'card-expiry-error');
                    }
                } else {
                    clearError('card-expiry', 'card-expiry-error');
                }
            });

            cardExpiryInput.addEventListener('blur', function(e) {
                const value = e.target.value;
                if (value.length > 0 && !validateExpiry(value)) {
                    if (value.length < 5) {
                        showError('card-expiry', 'card-expiry-error', 'Formato: MM/AA');
                    } else {
                        showError('card-expiry', 'card-expiry-error', 'La fecha no es válida o la tarjeta está caducada');
                    }
                }
            });
        }

        // Validar CVV (3-4 dígitos, solo números)
        function validateCVV(value) {
            return /^\d{3,4}$/.test(value);
        }

        if (cardCvvInput) {
            cardCvvInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Solo números
                
                // Limitar a 4 dígitos
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }
                
                e.target.value = value;
                
                // Validación en tiempo real
                if (value.length > 0) {
                    if (value.length < 3) {
                        showError('card-cvv', 'card-cvv-error', 'El CVV debe tener 3 o 4 dígitos');
                    } else if (!validateCVV(value)) {
                        showError('card-cvv', 'card-cvv-error', 'El CVV solo puede contener números');
                    } else {
                        clearError('card-cvv', 'card-cvv-error');
                    }
                } else {
                    clearError('card-cvv', 'card-cvv-error');
                }
            });

            cardCvvInput.addEventListener('blur', function(e) {
                const value = e.target.value;
                if (value.length > 0 && !validateCVV(value)) {
                    showError('card-cvv', 'card-cvv-error', 'El CVV debe tener 3 o 4 dígitos');
                }
            });
        }

        // Validar formulario completo al enviar
        if (cardPaymentForm) {
            cardPaymentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const cardNumber = cardNumberInput.value.replace(/\s/g, '');
                const cardExpiry = cardExpiryInput.value;
                const cardCvv = cardCvvInput.value;
                
                let isValid = true;
                
                // Validar número de tarjeta
                if (!validateCardNumber(cardNumber)) {
                    showError('card-number', 'card-number-error', 'El número de tarjeta debe tener 16 dígitos');
                    isValid = false;
                }
                
                // Validar fecha de expiración
                if (!validateExpiry(cardExpiry)) {
                    if (cardExpiry.length < 5) {
                        showError('card-expiry', 'card-expiry-error', 'Formato: MM/AA');
                    } else {
                        showError('card-expiry', 'card-expiry-error', 'La fecha no es válida o la tarjeta está caducada');
                    }
                    isValid = false;
                }
                
                // Validar CVV
                if (!validateCVV(cardCvv)) {
                    showError('card-cvv', 'card-cvv-error', 'El CVV debe tener 3 o 4 dígitos');
                    isValid = false;
                }
                
                // Si todo es válido, proceder con el pago
                if (isValid) {
                    window.location.href = 'checkout.php?payment_method=card_successful';
                } else {
                    // Scroll al primer error
                    const firstError = document.querySelector('[style*="border-color: rgb(220, 53, 69)"]');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        }
    });
</script>

</body>
</html>