<?php
session_start();

include('Config/conexion.php'); 

$mensaje = "";
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recoger y sanear los datos
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // 2. Validación básica
    if (empty($name) || empty($email) || empty($message)) {
        $mensaje = "Por favor, completa todos los campos obligatorios.";
        $exito = false;
    } else {
        // 3. Preparar la inserción en la tabla ContactMessages
        $stmt = $conn->prepare(
            "INSERT INTO ContactMessages (Name, Email, Subject, Message)
             VALUES (?, ?, ?, ?)"
        );
        
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);

            if ($stmt->execute()) {
                $mensaje = "¡Gracias por contactarnos! Hemos guardado tu mensaje y te responderemos pronto.";
                $exito = true;
            } else {
                $mensaje = "Error al guardar el mensaje. Intenta de nuevo más tarde.";
                $exito = false;
            }
            $stmt->close();
        } else {
            $mensaje = "Error interno del servidor al preparar la inserción.";
            $exito = false;
        }
    }
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - Hoteles NESL</title>
    <link rel="stylesheet" href="Assets/css/styleCarlos.css">
    <style>
        /* Estilos de la tarjeta de login/registro reutilizados */
        .contact-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .contact-container h1 {
            color: #a02040;
            text-align: center;
        }
        .contact-info p {
            font-size: 1em;
            color: #555;
            margin: 15px 0;
            text-align: center;
        }
        .field textarea {
            width: 100%;
            padding: 10px 12px;
            font-size: 0.9rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            resize: vertical; /* Permite redimensionar verticalmente */
            min-height: 100px;
            outline: none;
        }
        .field textarea:focus {
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="contact-container">
        <h1>Contáctanos</h1>
        <p class="subtitle" style="margin-bottom: 30px;">Estamos aquí para ayudarte con tus reservas y consultas.</p>

        <?php if ($mensaje !== ""): ?>
            <div class="<?php echo $exito ? 'success-msg' : 'error-msg'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="contact-info">
            <p><strong>📞 Teléfono:</strong> +34 900 123 456</p>
            <p><strong>📧 Email:</strong> info@hotelesnesl.es</p>
            <p><strong>📍 Dirección:</strong> Avenida de España, 25, Albacete</p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        </div>

        <form method="POST" action="">
            <div class="field">
                <label for="name">Tu Nombre</label>
                <input type="text" name="name" id="name" required>
            </div>
            
            <div class="field">
                <label for="email">Tu Correo Electrónico</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="field">
                <label for="subject">Asunto</label>
                <input type="text" name="subject" id="subject" required>
            </div>

            <div class="field">
                <label for="message">Mensaje</label>
                <textarea name="message" id="message" required></textarea>
            </div>

            <button type="submit" class="btn-login">Enviar Mensaje</button>
        </form>
        
        <div class="back-store" style="margin-top: 25px;">
            <a href="Cliente/index.php">← Volver a la tienda</a>
        </div>
    </div>
</body>
</html>