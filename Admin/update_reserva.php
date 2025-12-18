<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: ../auth/login.php");
    exit();
}

include("../Config/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['status'])) {
    
    $reservation_id = (int)$_POST['reservation_id'];
    $new_status = trim($_POST['status']);
    
    // Validar que el estado sea uno de los permitidos
    $allowed_statuses = ['Aceptada', 'Cancelada', 'En Proceso', 'Confirmada']; // 'Confirmada' es el estado inicial
    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['reserva_msg'] = 'Error: Estado no válido.';
        header("Location: reservas.php");
        exit();
    }

    // Consulta para actualizar el estado de la reserva
    $sql = "UPDATE Reservation SET Status = ? WHERE Id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $reservation_id);
    
    if ($stmt->execute()) {
        $_SESSION['reserva_msg'] = 'Estado de la reserva #' . $reservation_id . ' actualizado a "' . $new_status . '" correctamente.';
    } else {
        $_SESSION['reserva_msg'] = 'Error al actualizar la reserva: ' . $stmt->error;
    }
    
    $stmt->close();
    
} else {
    $_SESSION['reserva_msg'] = 'Error: Datos de reserva incompletos.';
}

header("Location: reservas.php");
exit();
?>