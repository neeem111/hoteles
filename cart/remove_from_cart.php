<?php
session_start();

$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

if ($hotel_id <= 0) {
    header('Location: view_cart.php');
    exit;
}

if (isset($_SESSION['cart'][$hotel_id])) {
    unset($_SESSION['cart'][$hotel_id]);
    $_SESSION['cart_success'] = 'Artículo eliminado del carrito.';
}

header('Location: view_cart.php');
exit;
?>
