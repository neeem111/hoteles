<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - Cliente</title>
    <link rel="stylesheet" href="styleCarlos.css">
</head>
<body>
    <div class="login-card">
        <h2>Iniciar sesión</h2>

        <?php if (!empty($errorMsgView)): ?>
            <div class="error-msg"><?= $errorMsgView ?></div>
        <?php endif; ?>

        <?php if (!empty($msgView)): ?>
            <div class="info-msg"><?= $msgView ?></div>
        <?php endif; ?>

        <form action="index.php?page=client_login" method="POST">
            <div class="field">
                <label>Correo</label>
                <input type="email" name="email" required>
            </div>

            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>

            <button class="btn-login">Entrar</button>
        </form>

        <div class="back-store">
            <a href="index.php?page=client_register">Registrarme</a>
        </div>
    </div>
</body>
</html>
