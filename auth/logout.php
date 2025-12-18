<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['login_log_id'])) {
    $log_id = $_SESSION['login_log_id'];

    // Obtener tiempo de inicio
    $query = "SELECT login_time FROM login_logs WHERE id = $log_id";
    $result = $conn->query($query);

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $login_time = strtotime($row['login_time']);
        $logout_time = time();
        $diff = $logout_time - $login_time;

        $horas = floor($diff / 3600);
        $minutos = floor(($diff % 3600) / 60);
        $segundos = $diff % 60;

        $duration_text = "$horas h $minutos m $segundos s";

        // Actualizar log
        $update = "UPDATE login_logs 
                   SET logout_time = NOW(),
                       duration = '$duration_text'
                   WHERE id = $log_id";

        $conn->query($update);
    }
}

// Destruir sesión
session_unset();
session_destroy();

header("Location: auth/login.php");
exit();
