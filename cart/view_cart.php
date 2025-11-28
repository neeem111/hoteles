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
    $total += $item['precio'] * $item['cantidad'];
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
            <p>Tu carrito está vacío. <a href="../index.php">Volver a la tienda</a></p>
        <?php else: ?>
            <form method="post" action="update_cart.php">
            <table>
                <thead>
                    <tr>
                        <th style="width:40%">Hotel</th>
                        <th style="width:20%">Ciudad</th>
                        <th style="width:15%">Precio / noche</th>
                        <th style="width:10%">Noches</th>
                        <th style="width:10%">Subtotal</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $item): ?>
                        <tr>
                            <td><?php echo isset($hoteles_en_carrito[$id]) ? htmlspecialchars($hoteles_en_carrito[$id]['Name']) : 'Hotel #' . $id; ?></td>
                            <td><?php echo isset($hoteles_en_carrito[$id]) ? htmlspecialchars($hoteles_en_carrito[$id]['City']) : '-'; ?></td>
                            <td class="price">$<?php echo number_format($item['precio'], 2); ?></td>
                            <td>
                                <input type="number" name="cantidad[<?php echo intval($id); ?>]" value="<?php echo intval($item['cantidad']); ?>" min="0" style="width:90px; padding:8px; font-size:15px">
                            </td>
                            <td class="price">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                            <td><a href="remove_from_cart.php?hotel_id=<?php echo intval($id); ?>">Eliminar</a></td>
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
                    <button type="submit" class="btn btn-primary">Actualizar Carrito</button>
                    <a href="#" class="btn btn-primary" style="background:#28a745; margin-left:8px">Proceder al Pago</a>
                </div>
            </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
