<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_cart.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$updated = 0;
$errors = [];

// Procesar cada hotel en el carrito
foreach ($_SESSION['cart'] as $hotel_id => $item) {
    $hotel_id = intval($hotel_id);
    if ($hotel_id <= 0) continue;

    // Actualizar noches
    if (isset($_POST['nights'][$hotel_id])) {
        $nights = intval($_POST['nights'][$hotel_id]);
        if ($nights > 0) {
            $_SESSION['cart'][$hotel_id]['nights'] = $nights;
            $updated++;
        } else {
            $errors[] = 'El número de noches debe ser mayor a 0.';
        }
    }

    // Actualizar check_in
    if (isset($_POST['check_in'][$hotel_id])) {
        $check_in = trim($_POST['check_in'][$hotel_id]);
        if (!empty($check_in)) {
            // Validar formato de fecha
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in)) {
                $_SESSION['cart'][$hotel_id]['check_in'] = $check_in;
                $updated++;
            } else {
                $errors[] = 'Formato de fecha de entrada inválido.';
            }
        }
    }

    // Actualizar check_out
    if (isset($_POST['check_out'][$hotel_id])) {
        $check_out = trim($_POST['check_out'][$hotel_id]);
        if (!empty($check_out)) {
            // Validar formato de fecha
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)) {
                $_SESSION['cart'][$hotel_id]['check_out'] = $check_out;
                $updated++;
            } else {
                $errors[] = 'Formato de fecha de salida inválido.';
            }
        }
    }
}

if (!empty($errors)) {
    $_SESSION['cart_error'] = implode(' ', $errors);
} elseif ($updated > 0) {
    $_SESSION['cart_success'] = 'Datos actualizados correctamente.';
} else {
    $_SESSION['cart_success'] = 'No se realizaron cambios.';
}

header('Location: view_cart.php');
exit;
?>
