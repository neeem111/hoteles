<?php
session_start();
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_form = $_POST['email'] ?? '';
    $pass_form  = $_POST['password'] ?? '';

    $email_form = trim($email_form);
    $pass_form  = trim($pass_form);

    $sql = "SELECT * FROM users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email_form);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();

        // Verificar contraseña encriptada
        if (password_verify($pass_form, $user['Password'])) {

            $_SESSION['user_id'] = $user['Id'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_email'] = $user['Email'];

            header("Location: index.php");
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
