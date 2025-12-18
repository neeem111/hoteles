<?php
session_start();
include("../Config/conexion.php");

const ADMIN_GATE_PASSWORD = '$ilksong22';

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    unset($_SESSION['admin_gate']);
    header("Location: register.php");
    exit();
}

$mensaje = "";

// Validar candado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_access'])) {
    $access_try = trim($_POST['admin_access']);
    if ($access_try === ADMIN_GATE_PASSWORD) {
        $_SESSION['admin_gate'] = true;
        header("Location: register.php");
        exit();
    } else {
        $mensaje = "Contraseña de acceso incorrecta.";
    }
}

// Registrar usuario
if (isset($_SESSION['admin_gate']) && $_SESSION['admin_gate'] === true) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['name'], $_POST['age'], $_POST['address'], $_POST['email'], $_POST['password'], $_POST['rol'])) {

        $name     = trim($_POST['name']);
        $age      = (int) $_POST['age'];
        $address  = trim($_POST['address']);
        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);
        $rol      = trim($_POST['rol']);

        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Verificar duplicado
        $check = $conn->prepare("SELECT 1 FROM users WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result && $result->num_rows > 0) {
            $mensaje = "Este correo ya está registrado.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users (Name, Age, Address, Email, Password, Rol)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sissss", $name, $age, $address, $email, $password_hashed, $rol);

            if ($stmt->execute()) {
                unset($_SESSION['admin_gate']);
                header("Location: ../auth/login.php?msg=Usuario+registrado+correctamente");
                exit();
            } else {
                $mensaje = "Error al registrar usuario.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alta de Usuarios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #fff;
            width: 380px;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-top: 0;
        }
        .subtitle {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }
        .field {
            margin-bottom: 16px;
        }
        .field label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-size: 0.9rem;
        }
        .field input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 1rem;
            font-weight: bold;
        }
        .error-msg {
            background: #ffe0e0;
            padding: 10px;
            border-radius: 6px;
            color: #a10000;
            margin-bottom: 15px;
        }
        .back-store {
            text-align: center;
            margin-top: 16px;
            font-size: 0.85rem;
        }
        .back-store a { color: #007bff; text-decoration: none; }
        
        .toggle-wrapper {
            display: flex;
            background: #e9e9e9;
            padding: 5px;
            border-radius: 10px;
            gap: 8px;
            border: 1px solid #ccc;
        }
        .toggle-option {
            flex: 1;
            text-align: center;
            cursor: pointer;
            padding: 10px 0;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: bold;
            font-size: 0.9rem;
            user-select: none;
        }
        .toggle-option input { display: none; }
        .toggle-option input:checked + span {
            background: #007bff;
            color: white;
            padding: 10px 0;
            border-radius: 8px;
            display: block;
        }
    </style>
</head>

<body>

<?php if (!isset($_SESSION['admin_gate']) || $_SESSION['admin_gate'] !== true): ?>

    <div class="login-card">
        <h2>Acceso para alta</h2>
        <p class="subtitle">Ingresa la contraseña especial</p>

        <?php if ($mensaje !== ""): ?>
            <div class="error-msg"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label>Contraseña de acceso</label>
                <input type="password" name="admin_access" required>
            </div>

            <button type="submit" class="btn-login">Continuar</button>
        </form>

        <div class="back-store">
            <a href="../Cliente/index.php">← Volver a la tienda</a>
        </div>
    </div>

<?php else: ?>

    <div class="login-card">
        <h2>Registrar Usuario</h2>
        <p class="subtitle">Administrador o Cliente</p>

        <?php if ($mensaje !== ""): ?>
            <div class="error-msg"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="field">
                <label>Nombre completo</label>
                <input type="text" name="name" required>
            </div>

            <div class="field">
                <label>Edad</label>
                <input type="number" name="age" required>
            </div>

            <div class="field">
                <label>Dirección</label>
                <input type="text" name="address" required>
            </div>

            <div class="field">
                <label>Correo electrónico</label>
                <input type="email" name="email" required>
            </div>

            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>

            <div class="field">
                <label>Tipo de usuario</label>
                
                <div class="toggle-wrapper">
                    <label class="toggle-option">
                        <input type="radio" name="rol" value="Administrador" checked>
                        <span>Administrador</span>
                    </label>

                    <label class="toggle-option">
                        <input type="radio" name="rol" value="Cliente">
                        <span>Cliente</span>
                    </label>
                </div>

            </div>

            <button type="submit" class="btn-login">Registrar Usuario</button>
        </form>

        <div class="back-store">
            <a href="register.php?logout=1">← Salir del modo de alta</a><br>
            <a href="../auth/login.php">← Volver al login</a>
        </div>
    </div>

<?php endif; ?>
<script src="../Assets/js/keepalive.js"></script>
</body>
</html>
