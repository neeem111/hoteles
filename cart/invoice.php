<?php
session_start();

// Last reservation info is required to build the invoice
$last = $_SESSION['last_reservations'] ?? [];
if (empty($last)) {
    header('Location: ../Cliente/index.php');
    exit;
}
// Ensure the user is logged in before showing invoice
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userName  = $_SESSION['user_name']  ?? 'Cliente';
$userEmail = $_SESSION['user_email'] ?? '';

// Datos simples de la factura
$suffix = bin2hex(random_bytes(3));
$invoiceNumber = 'INV-' . date('Ymd') . '-' . $suffix;
$issueDate     = date('Y-m-d');

$totalGlobal = 0;
foreach ($last as $res) {
    $totalGlobal += $res['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?php echo htmlspecialchars($invoiceNumber); ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background:#f1f3f5;
            margin:0;
            padding:0;
        }
        .wrapper {
            max-width: 900px;
            margin: 30px auto;
            background:#fff;
            padding: 30px 34px;
            border-radius: 12px;
            box-shadow:0 10px 24px rgba(0,0,0,0.08);
        }
        h1 {
            margin:0 0 6px 0;
            color:#a02040;
        }
        .meta {
            color:#555;
            margin-bottom: 16px;
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
        .btn {
            display:inline-block;
            margin-top:18px;
            padding:10px 16px;
            border-radius:8px;
            text-decoration:none;
            background:#a02040;
            color:#fff;
            font-weight:600;
            font-size:0.9rem;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background:#801933;
        }
        .note {
            font-size:0.9rem;
            color:#666;
            margin-top: 14px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Factura</h1>
        <div class="meta">
            <div>Número: <?php echo htmlspecialchars($invoiceNumber); ?></div>
            <div>Fecha de emisión: <?php echo htmlspecialchars($issueDate); ?></div>
            <div>Cliente: <?php echo htmlspecialchars($userName); ?></div>
            <?php if (!empty($userEmail)): ?>
                <div>Email: <?php echo htmlspecialchars($userEmail); ?></div>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th># Reserva</th>
                    <th>Hotel</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Noches</th>
                    <th>Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($last as $res): ?>
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
                    <td colspan="5" class="total" style="text-align:right;">Total</td>
                    <td class="total"><?php echo number_format($totalGlobal, 2); ?> €</td>
                </tr>
            </tbody>
        </table>

        <button class="btn" onclick="window.print()">Imprimir / Guardar como PDF</button>
        <a class="btn" href="confirmation.php" style="background:#6c757d; margin-left:10px;">Volver</a>

        <p class="note">
            Este documento se genera a partir de la última reserva confirmada en tu sesión. 
            Para facturas oficiales con datos fiscales completos, contacta con el hotel o administración.
        </p>
    </div>
</body>
</html>
