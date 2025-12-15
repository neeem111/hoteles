<?php
session_start();

// Solo permite acceso a administradores
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['user_role'], 'Administrador') !== 0) {
    header("Location: ../login.php");
    exit();
}

include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'], $_POST['available'])) {
    
    $room_id = (int)$_POST['room_id'];
    $new_available_state = (int)$_POST['available'];
    
    // Validar el estado (solo 0 o 1)
    if ($new_available_state !== 0 && $new_available_state !== 1) {
        $_SESSION['room_msg'] = 'Error: Valor de disponibilidad no válido.';
        header("Location: gestion_habitaciones.php");
        exit();
    }
    
    $status_text = ($new_available_state == 1) ? 'Operativa (Disponible)' : 'Mantenimiento (No disponible)';

    // Consulta para actualizar el estado de la habitación
    $sql = "UPDATE Rooms SET Available = ? WHERE Id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $new_available_state, $room_id);
    
    if ($stmt->execute()) {
        $_SESSION['room_msg'] = 'Habitación #' . $room_id . ' actualizada a "' . $status_text . '" correctamente.';
    } else {
        $_SESSION['room_msg'] = 'Error al actualizar la habitación: ' . $stmt->error;
    }
    
    $stmt->close();
    
} else {
    $_SESSION['room_msg'] = 'Error: Datos de habitación incompletos.';
}

header("Location: gestion_habitaciones.php");
exit();
?>