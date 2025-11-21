<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_cart.php');
    exit;
}

if (!isset($_POST['cantidad']) || !is_array($_POST['cantidad'])) {
    $_SESSION['cart_error'] = 'No se recibieron cantidades.';
    header('Location: view_cart.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$received = $_POST['cantidad'];
$updated = 0; $removed = 0;
foreach ($received as $hotel_id_str => $cant_raw) {
    $hotel_id = intval($hotel_id_str);
    $cantidad = intval($cant_raw);
    if ($hotel_id <= 0 || $cantidad < 0) continue;

    if (!isset($_SESSION['cart'][$hotel_id])) continue;

    if ($cantidad === 0) {
        unset($_SESSION['cart'][$hotel_id]);
        $removed++;
    } else {
        $_SESSION['cart'][$hotel_id]['cantidad'] = $cantidad;
        $updated++;
    }
}

if ($removed > 0) {
    $_SESSION['cart_success'] = "{$removed} artículo(s) eliminado(s).";
} elseif ($updated > 0) {
    $_SESSION['cart_success'] = "{$updated} artículo(s) actualizado(s).";
} else {
    $_SESSION['cart_success'] = 'No se realizaron cambios.';
}

header('Location: view_cart.php');
exit;
?>
