<?php
// controllers/Auth/ClientAuthController.php

class ClientAuthController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function showLogin(): void
    {
        $errorMsgView = $_GET['error'] ?? '';
        $msgView      = $_GET['msg']   ?? '';

        require __DIR__ . '/../../views/auth/client/login.php';
    }

    public function loginProcess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=client_login");
            exit();
        }

        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);

        $sql = "SELECT * FROM Users WHERE Email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if (!$user || !password_verify($password, $user['Password']) || $user['Rol'] !== 'Cliente') {
            header("Location: index.php?page=client_login&error=Credenciales+no+válidas");
            exit();
        }

        $_SESSION['user_id']    = $user['Id'];
        $_SESSION['user_name']  = $user['Name'];
        $_SESSION['user_email'] = $user['Email'];
        $_SESSION['user_role']  = 'Cliente';

        header("Location: index.php");
        exit();
    }

    public function showRegister(): void
    {
        $mensajeView = "";
        require __DIR__ . '/../../views/auth/client/register.php';
    }

    public function registerProcess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=client_register");
            exit();
        }

        $name     = trim($_POST['name']);
        $age      = trim($_POST['age']);
        $address  = trim($_POST['address']);
        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);

        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Verificar duplicado
        $check = $this->conn->prepare("SELECT 1 FROM Users WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $mensajeView = "Este correo ya está registrado.";
            require __DIR__ . '/../../views/auth/client/register.php';
            return;
        }

        $stmt = $this->conn->prepare("INSERT INTO Users (Name, Age, Address, Email, Password, Rol) VALUES (?, ?, ?, ?, ?, 'Cliente')");
        $stmt->bind_param("sisss", $name, $age, $address, $email, $password_hashed);

        if ($stmt->execute()) {
            header("Location: index.php?page=client_login&msg=Registro+completado");
            exit();
        } else {
            $mensajeView = "Error al registrar usuario.";
            require __DIR__ . '/../../views/auth/client/register.php';
        }
    }
}
