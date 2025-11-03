<?php
session_start();
include("conexion.php");

const ADMIN_GATE_PASSWORD = '$ilksong22';

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    unset($_SESSION['admin_gate']);
    header("Location: registerAdmin.php");
    exit();
}

$mensaje = "";
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_access'])) {
    $access_try = trim($_POST['admin_access']);
    if (hash_equals(ADMIN_GATE_PASSWORD, $access_try)) {
        $_SESSION['admin_gate'] = true;
        // Redirigimos para evitar reenvío del formulario del candado
        header("Location: registerAdmin.php");
        exit();
    } else {
        $mensaje = "Contraseña de acceso incorrecta.";
    }
}

if (isset($_SESSION['admin_gate']) && $_SESSION['admin_gate'] === true) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'], $_POST['password'])) {
        $name     = trim($_POST['name']);
        $age      = isset($_POST['age']) ? (int) $_POST['age'] : null;
        $address  = trim($_POST['address'] ?? '');
        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Hash seguro de contraseña
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $rol = 'Administrador';

        // Verificar duplicado de email
        $check = $conn->prepare("SELECT 1 FROM users WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result && $result->num_rows > 0) {
            $mensaje = "Este correo ya está registrado.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO users (Name, Age, Address, Email, Password, Rol)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sissss", $name, $age, $address, $email, $password_hashed, $rol);

            if ($stmt->execute()) {
                // Opcional: cerrar el pase del candado tras registrar
                unset($_SESSION['admin_gate']);
                header("Location: login.php?msg=Administrador+creado,+puedes+iniciar+sesión");
                exit();
            } else {
                $mensaje = "Error al registrar administrador.";
            }
        }
    }

    // Si llega aquí y no se hizo POST de registro, se mostrará el formulario de alta
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alta de Administrador</title>
    <link rel="stylesheet" href="styleCarlos.css">
</head>
<body>

<?php if (!isset($_SESSION['admin_gate']) || $_SESSION['admin_gate'] !== true): ?>
    <!-- Vista del candado -->
    <div class="login-card">
        <h2>Acceso para alta de Administrador</h2>
        <p class="subtitle">Ingresa la contraseña de acceso</p>

        <?php if ($mensaje !== ""): ?>
            <div class="error-msg"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label for="admin_access">Contraseña de acceso</label>
                <input type="password" name="admin_access" id="admin_access" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">Continuar</button>
        </form>

        <div class="back-store">
            <a href="login.php">← Volver al login</a><br>
            <a href="index.php">← Volver a la tienda</a>
        </div>
    </div>

<?php else: ?>
    <!-- Vista del formulario de registro de administrador -->
    <div class="login-card">
        <h2>Crear Administrador</h2>
        <p class="subtitle">Completa los datos para dar de alta</p>

        <?php if ($mensaje !== ""): ?>
            <div class="error-msg"><?php echo htmlspecialchars($mensaje); ?></div>
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

            <button type="submit" class="btn-login">Registrar Administrador</button>
        </form>

        <div class="back-store">
            <a href="registerAdmin.php?logout=1">← Salir del modo de alta</a><br>
            <a href="login.php">← Volver al login</a><br>
            <a href="index.php">← Volver a la tienda</a>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
