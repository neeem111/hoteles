<?php
session_start();
include('../conexion.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=Debes+iniciar+sesion');
    exit;
}

$user_id = $_SESSION['user_id'];

// CONSULTA CORREGIDA: 
// 1. Unimos Reservation con Invoices para obtener el 'Total' y el 'Id' de la factura.
// 2. Ordenamos por fecha de reserva descendente.
$sql = "SELECT 
            r.Id AS ReservationId, 
            r.Booking_date, 
            r.Status, 
            i.Total, 
            i.Id AS InvoiceId 
        FROM Reservation r
        LEFT JOIN Invoices i ON r.Id = i.Id_Reservation
        WHERE r.Id_User = ? 
        ORDER BY r.Booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mis Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styleCarlos.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .pedidos-container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        h1 { color: #a02040; font-size: 2rem; margin-bottom: 30px; border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 18px 15px; border-bottom: 1px solid #e9ecef; text-align: left; }
        th { background: #f8f9fa; color: #343a40; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-block; transition: all 0.2s; }
        .btn-view { background: #a02040; color: #fff; }
        .btn-view:hover { background: #801933; }
        .btn-invoice { background: #007bff; color: white; margin-left: 5px; }
        .btn-invoice:hover { background: #0056b3; }
        .btn-back { background: #343a40; color: white; }
        .btn-back:hover { background: #23272b; }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-Confirmada { background: #d4edda; color: #155724; }
        .status-Cancelada { background: #f8d7da; color: #721c24; }
        .status-Pendiente { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
<div class="pedidos-container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>📦 Mis Reservas</h1>
        <a href="../cart/view_cart.php" class="btn btn-back">← Volver al Carrito</a>
    </div>

    <?php if (empty($pedidos)): ?>
        <p style="text-align:center; color:#666; margin-top:20px;">No tienes reservas realizadas todavía.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th># Reserva</th>
                        <th>Fecha Reserva</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td>#<?php echo $pedido['ReservationId']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($pedido['Booking_date'])); ?></td>
                        <td>
                            <?php echo ($pedido['Total']) ? '€' . number_format($pedido['Total'], 2) : '-'; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($pedido['Status']); ?>">
                                <?php echo htmlspecialchars($pedido['Status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="ver_pedido.php?id=<?php echo $pedido['ReservationId']; ?>" class="btn btn-view">👁️ Detalles</a>
                            
                            <?php if ($pedido['InvoiceId']): ?>
                                <a href="ver_factura.php?id=<?php echo $pedido['InvoiceId']; ?>" class="btn btn-invoice" target="_blank">📄 Factura</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>