<?php
session_start();
$_SESSION['last_reservations'] = [
    [
        'reservation_id' => 101,
        'hotel_name' => 'Hotel Demo',
        'check_in' => '2024-12-20',
        'check_out' => '2024-12-22',
        'nights' => 2,
        'total' => 150.00,
        'notes' => ''
    ]
];
$_SESSION['user_name'] = 'Cliente Demo';
$_SESSION['user_email'] = 'cliente@example.com';
header('Location: /cart/confirmation.php');
exit;
?>
