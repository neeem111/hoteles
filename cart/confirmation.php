<?php
session_start();

// Cargamos las últimas reservas creadas
$last = isset($_SESSION['last_reservations']) ? $_SESSION['last_reservations'] : [];

if (empty($last)) {
    // Si no hay nada, redirigimos a la tienda para evitar accesos directos raros
    header('Location: ../Cliente/index.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? '';
$paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: 'sb';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reserva confirmada - Hoteles NESL</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background:#f1f3f5;
            margin:0;
            padding:0;
        }
        .wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background:#fff;
            border-radius:12px;
            box-shadow:0 8px 22px rgba(0,0,0,0.08);
            padding:24px 28px;
        }
        h1 {
            margin-top:0;
            color:#28a745;
        }
        .subtitle {
            color:#555;
            margin-top:4px;
            margin-bottom:20px;
        }
        table {
            width:100%;
            border-collapse: collapse;
            margin-top:20px;
            font-size:0.95rem;
        }
        th, td {
            padding:10px;
            border-bottom:1px solid #e9ecef;
            text-align:left;
        }
        th {
            background:#f8f9fa;
            font-weight:700;
        }
        .total {
            font-weight:bold;
        }
        .note {
            font-size:0.85rem;
            color:#777;
            margin-top:14px;
        }
        .btn {
            display:inline-block;
            margin-top:18px;
            padding:8px 16px;
            border-radius:8px;
            text-decoration:none;
            background:#a02040;
            color:#fff;
            font-weight:600;
            font-size:0.9rem;
        }
        .btn:hover {
            background:#801933;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1>🎉 Tu reserva ha sido creada</h1>
            <p class="subtitle">
                Gracias <?php echo htmlspecialchars($userName ?: 'por tu reserva'); ?>.  
                Hemos registrado las siguientes reservas en el sistema.
            </p>

            <table>
                <thead>
                    <tr>
                        <th># Reserva</th>
                        <th>Hotel</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Noches</th>
                        <th>Importe estimado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalGlobal = 0;
                    foreach ($last as $res): 
                        $totalGlobal += $res['total'];
                    ?>
                        <tr>
                            <td><?php echo (int)$res['reservation_id']; ?></td>
                            <td><?php echo htmlspecialchars($res['hotel_name']); ?></td>
                            <td><?php echo htmlspecialchars($res['check_in']); ?></td>
                            <td><?php echo htmlspecialchars($res['check_out']); ?></td>
                            <td><?php echo (int)$res['nights']; ?></td>
                            <td><?php echo number_format($res['total'], 2); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="5" class="total" style="text-align:right;">Total estimado</td>
                        <td class="total"><?php echo number_format($totalGlobal, 2); ?> €</td>
                    </tr>
                </tbody>
            </table>

            <div class="pay-block" style="margin-top:18px;">
                <a href="invoice.php" class="btn">Generar factura</a>
                <a href="../Cliente/index.php" class="btn" style="background:#6c757d; margin-left:10px;">Volver a la página principal</a>
            </div>

            <div style="margin-top:26px; padding:16px; border:1px solid #e9ecef; border-radius:10px; background:#f8f9fa;">
                <h3 style="margin-top:0;">Pagar ahora con PayPal</h3>
                <p style="margin:6px 0 12px 0; color:#555;">
                    Usa tu cuenta de prueba sandbox (<?php echo htmlspecialchars('sb-847k0p48080697@personal.example.com'); ?>) para completar el pago seguro (solo entorno de pruebas).
                </p>
                <div id="paypal-button-container"></div>
                <div id="paypal-paid-msg" style="display:none; margin-top:10px; color:#28a745; font-weight:600;"></div>
                <div id="paypal-error-msg" style="display:none; margin-top:10px; color:#b02a37;"></div>
            </div>

            <p class="note">
                Esta página muestra un resumen básico de tus reservas.  
                Los datos sensibles de pago (como tarjeta) no se almacenan ni se muestran aquí en ningún momento.  
                Puedes consultar más detalles contactando con el hotel o en el panel de usuario (si lo implementáis).
            </p>
        </div>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo urlencode($paypalClientId); ?>&currency=EUR"></script>
    <script>
        (function() {
            const totalAmount = "<?php echo number_format($totalGlobal, 2, '.', ''); ?>";
            const paidMsg = document.getElementById('paypal-paid-msg');
            const errorMsg = document.getElementById('paypal-error-msg');

            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'pill',
                    label: 'paypal'
                },
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                currency_code: 'EUR',
                                value: totalAmount
                            },
                            description: 'Reserva Hoteles NESL'
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        if (paidMsg) {
                            paidMsg.textContent = 'Pago completado por ' + (details.payer?.name?.given_name || 'PayPal') + '. ID: ' + details.id;
                            paidMsg.style.display = 'block';
                        }
                        if (errorMsg) {
                            errorMsg.style.display = 'none';
                        }
                    });
                },
                onError: function(err) {
                    if (errorMsg) {
                        errorMsg.textContent = 'No se pudo completar el pago con PayPal. Inténtalo de nuevo o contacta con soporte.';
                        errorMsg.style.display = 'block';
                    }
                    if (paidMsg) {
                        paidMsg.style.display = 'none';
                    }
                    console.error(err);
                }
            }).render('#paypal-button-container');
        })();
    </script>
</body>
</html>
