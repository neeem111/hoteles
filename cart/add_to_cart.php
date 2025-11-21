<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

if ($hotel_id <= 0 || $precio <= 0 || $cantidad <= 0) {
    $_SESSION['cart_error'] = 'Parámetros inválidos al añadir al carrito.';
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$hotel_id])) {
    $_SESSION['cart'][$hotel_id]['cantidad'] += $cantidad;
} else {
    $_SESSION['cart'][$hotel_id] = [
        'hotel_id' => $hotel_id,
        'precio' => $precio,
        'cantidad' => $cantidad
    ];
}

$_SESSION['cart_success'] = 'Hotel añadido al carrito.';
header('Location: view_cart.php');
exit;

?>
