<?php
session_start();
include('../Config/conexion.php');

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado. Inicia sesión.");
}

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// 1. Obtener datos de la factura, usuario y hotel
$sql = "SELECT i.*, u.Name as UserName, u.Address as UserAddress, u.Email as UserEmail, 
                h.Name as HotelName, h.Address as HotelAddress, h.City as HotelCity
        FROM Invoices i
        JOIN Users u ON i.Id_User = u.Id
        JOIN Reservation r ON i.Id_Reservation = r.Id
        JOIN Reservation_Rooms rr ON r.Id = rr.Id_Reservation
        JOIN Rooms room ON rr.Id_Room = room.Id
        JOIN Hotels h ON room.Id_Hotel = h.Id
        WHERE i.Id = ? AND i.Id_User = ?
        LIMIT 1"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $invoice_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    die("Factura no encontrada.");
}

// 2. Obtener items
$sqlItems = "SELECT * FROM InvoiceItems WHERE Id_Invoice = ?";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $invoice_id);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?php echo htmlspecialchars($invoice['InvoiceNumber']); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #555;
            background: #f5f5f5;
            padding: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .invoice-title {
            font-size: 40px;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .company-info {
            text-align: right;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: bold;
            color: #a02040;
            display: block;
            margin-bottom: 2px;
        }
        
        table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        table th {
            background: #a02040;
            color: white;
            padding: 12px;
            font-weight: 600;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 1.1em;
            color: #000;
        }
        .text-right { text-align: right; }
        
        /* Botones de acción (se ocultan al imprimir) */
        .actions {
            text-align: right;
            max-width: 800px;
            margin: 0 auto 20px auto;
        }
        .btn {
            background: #333;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-print { background: #a02040; }
        
        @page {
            size: A4 landscape;
            margin: 10mm; 
        }

        @media print {
            body { 
                background: white; 
                margin: 0; 
                padding: 0; 
                font-size: 10pt; /* Fuente más pequeña para que quepa más */
            }
            
            .invoice-box { 
                border: none; 
                box-shadow: none; 
                /* Asegura que use todo el espacio horizontal del A4 Landscape */
                width: 100% !important; 
                max-width: none !important; 
                padding: 0 !important;
            }
            
            .invoice-header {
                justify-content: space-between;
            }
            
            .invoice-title {
                font-size: 30px; /* Reducir el título para evitar que se corte */
            }
            
            .actions { 
                display: none; /* Ocultar botones */
            }
        }
    </style>
</head>
<body>

    <div class="actions">
       
        <button onclick="window.print()" class="btn btn-print">🖨️ Descargar PDF / Imprimir</button>
    </div>

    <div class="invoice-box">
        <div class="invoice-header">
            <div>
                <div class="info-label">DE:</div>
                <strong>Hoteles Nueva España S.L.</strong><br>
                Paseo de Pereda, 25<br>
                Santander, España<br>
                NIF: B-12345678<br>
                info@hotelesnesl.es
            </div>
            <div class="company-info">
                <div class="invoice-title">FACTURA</div>
                <br>
                <strong>Nº Factura:</strong> <?php echo $invoice['InvoiceNumber']; ?><br>
                <strong>Fecha:</strong> <?php echo date("d/m/Y", strtotime($invoice['Date'])); ?><br>
                <strong>Estado:</strong> <?php echo $invoice['Status']; ?>
            </div>
        </div>

        <div class="info-group">
            <div class="info-label">FACTURAR A:</div>
            <strong><?php echo htmlspecialchars($invoice['UserName']); ?></strong><br>
            <?php echo htmlspecialchars($invoice['UserAddress']); ?><br>
            <?php echo htmlspecialchars($invoice['UserEmail']); ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="50%">Descripción</th>
                    <th width="15%" class="text-right">Precio Unit.</th>
                    <th width="10%" class="text-right">Cant.</th>
                    <th width="25%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $itemsResult->data_seek(0); // Asegurarse de empezar desde el principio
                while($item = $itemsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['Description']); ?></td>
                    <td class="text-right"><?php echo number_format($item['UnitPrice'], 2); ?> €</td>
                    <td class="text-right"><?php echo $item['Quantity']; ?></td>
                    <td class="text-right"><?php echo number_format($item['Total'], 2); ?> €</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">Subtotal</td>
                    <td class="text-right"><?php echo number_format($invoice['Subtotal'], 2); ?> €</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">IVA (21%)</td>
                    <td class="text-right"><?php echo number_format($invoice['IVA'], 2); ?> €</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" class="text-right">TOTAL</td>
                    <td class="text-right"><?php echo number_format($invoice['Total'], 2); ?> €</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 50px; text-align: center; color: #777; font-size: 0.85em;">
            <p>Gracias por confiar en Hoteles Nueva España.</p>
            <p>Registro Mercantil de Santander, Tomo 1234, Folio 56, Sección 8, Hoja S-78900.</p>
        </div>
    </div>

</body>
</html>