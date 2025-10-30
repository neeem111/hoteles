<?php
include("conexion.php");

$mensaje = "";

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $age = trim($_POST['age']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Encriptar la contraseña antes de guardar
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Verificar si el correo ya está registrado
    $check = $conn->prepare("SELECT * FROM users WHERE Email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        $mensaje = "⚠️ Este correo ya está registrado.";
    } else {
        // Insertar nuevo usuario
        $stmt = $conn->prepare("INSERT INTO users (Name, Age, Address, Email, Password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $name, $age, $address, $email, $password_hashed);

        if ($stmt->execute()) {
            // Redirigir automáticamente al login después del registro
            header("Location: login.php?msg=Registro+exitoso,+ahora+puedes+iniciar+sesión");
            exit();
        } else {
            $mensaje = "❌ Error al registrar usuario.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="styleCarlos.css">
</head>
<body>
    <div class="login-card">
        <h2>Crear cuenta</h2>
        <p class="subtitle">Completa tus datos para registrarte</p>

        <?php if ($mensaje !== ""): ?>
            <div class="error-msg"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label for="name">Nombre completo</label>
                <input type="text" name="name" id="name" required>
            </div>

            <div class="field">
                <label for="age">Edad</label>
                <input type="number" name="age" id="age" required>
            </div>

            <div class="field">
                <label for="address">Dirección</label>
                <input type="text" name="address" id="address" required>
            </div>

            <div class="field">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn-login">Registrarme</button>
        </form>

        <div class="back-store">
            <a href="login.php">← Ya tengo cuenta</a><br>
            <a href="index.php">← Volver a la tienda</a>
        </div>
    </div>
</body>
</html>
