<?php
session_start();
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_form = trim($_POST['email'] ?? '');
    $pass_form  = trim($_POST['password'] ?? '');

    $sql = "SELECT * FROM users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email_form);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();

        if (password_verify($pass_form, $user['Password'])) {

            $_SESSION['user_id']    = $user['Id'];
            $_SESSION['user_name']  = $user['Name'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_role']  = $user['Rol'] ?? 'Cliente'; // fallback por si hay NULL viejos

            // Redirección según rol
            if (strcasecmp($_SESSION['user_role'], 'Administrador') === 0) {
                header("Location: indexAdmin.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            header("Location: login.php?error=Contraseña+incorrecta");
            exit();
        }
    } else {
        header("Location: login.php?error=Usuario+no+encontrado");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
