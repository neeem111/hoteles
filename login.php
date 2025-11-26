<?php
session_start();

$error_msg = "";
if (isset($_GET['error'])) {
    $error_msg = htmlspecialchars($_GET['error']);
}
?>
<?php
$msg = "";
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - Mi Tienda</title>
    <link rel="stylesheet" href="styleCarlos.css">
</head>
<body>
    <div class="login-card">
        <h2>Iniciar sesión</h2>
        <p class="subtitle">Ingresa con tu correo y contraseña</p>

        <?php if ($error_msg !== ""): ?>
            <div class="error-msg">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <div class="field">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" required placeholder="tucorreo@ejemplo.com">
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required placeholder="********">
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="back-store">
            <a href="Cliente/register.php">¿No tienes cuenta? Regístrate aquí</a><br>
            <a href="Cliente/index.php">← Volver a la tienda</a>
        </div>

    </div>
</body>
</html>