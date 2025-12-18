<?php
session_start();

// Cargamos las últimas reservas creadas
$last = isset($_SESSION['last_reservations']) ? $_SESSION['last_reservations'] : [];

if (empty($last)) {
    // Si no hay nada, redirigimos a la tienda para evitar accesos directos raros
    header('Location: ../index.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? '';
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
        .btn-invoice {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .btn-invoice:hover {
            background-color: #0056b3;
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
                        <th>Fechas</th>
                        <th>Total (con IVA)</th>
                        <th>Factura</th>
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
                            <td>
                                <?php echo htmlspecialchars($res['check_in']); ?> a<br>
                                <?php echo htmlspecialchars($res['check_out']); ?>
                            </td>
                            <td><?php echo number_format($res['total'], 2); ?> €</td>
                            <td>
                                <?php if(isset($res['invoice_id'])): ?>
                                    <a href="../ver_factura.php?id=<?php echo $res['invoice_id']; ?>" target="_blank" class="btn-invoice">
                                        📄 Descargar
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="total" style="text-align:right;">Total Pagado</td>
                        <td class="total" colspan="2"><?php echo number_format($totalGlobal, 2); ?> €</td>
                    </tr>
                </tbody>
            </table>

            <p class="note">
                Esta página muestra un resumen básico de tus reservas.  
                Los datos sensibles de pago (como tarjeta) no se almacenan ni se muestran aquí en ningún momento.  
                Puedes consultar más detalles contactando con el hotel o en el panel de usuario.
            </p>

            <a href="../index.php" class="btn">Volver a la página principal</a>
        </div>
    </div>
</body>
</html>