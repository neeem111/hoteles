<?php session_start(); 
// Código de sesión copiado para mantener el navbar si lo incluyes
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aviso Legal - Hoteles NESL</title>
    <link rel="stylesheet" href="styleCarlos.css">
    <style>
        /* Estilos específicos para la página de contenido */
        body {
            background-color: #f8ff9fa; /* Color de fondo claro */
            padding-top: 50px; /* Deja espacio si usas un navbar fijo */
            display: block; /* Desactiva el flex de styleCarlos.css para body */
            min-height: auto;
        }
        .content-card {
            max-width: 900px;
            margin: 50px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .content-card h1 {
            color: #a02040; /* Usamos el color principal de los hoteles */
            border-bottom: 2px solid #ffc107;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .content-card h3 {
            color: #343a40;
            margin-top: 25px;
        }
        .content-card p, .content-card ul {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="content-card">
        <a href="Cliente/index.php" style="color: #007bff; text-decoration: none; font-weight: bold;">← Volver a la página principal</a>
        
        <h1>Aviso Legal y Condiciones de Uso</h1>
        
        <h3>1. Datos del Titular</h3>
        <p><strong>Razón Social:</strong> Hoteles Nueva España S.L.</p>
        <p><strong>NIF:</strong> B-12345678</p>
        <p><strong>Domicilio Social:</strong> Paseo de Pereda, 25, 39004, Santander, España.</p>
        <p><strong>Correo Electrónico:</strong> info@hotelesnesl.es</p>
        <p><strong>Registro Mercantil:</strong> Inscrita en el Registro Mercantil de Santander, Tomo 1234, Folio 56, Sección 8, Hoja S-78900.</p>

        <h3>2. Propiedad Intelectual e Industrial</h3>
        <p>Todos los contenidos del sitio web (textos, imágenes, logotipos, diseño gráfico, etc.) son propiedad exclusiva de Hoteles Nueva España S.L. o de terceros que han autorizado su uso. Queda prohibida la reproducción, distribución o transformación sin el consentimiento expreso y por escrito del titular.</p>

        <h3>3. Responsabilidad</h3>
        <p>Hoteles Nueva España S.L. no se hace responsable de los daños o perjuicios que pudieran derivarse del uso de la información de este sitio web. La empresa se reserva el derecho de modificar, suspender o interrumpir el servicio o la información en cualquier momento y sin previo aviso.</p>
        
        <h3>4. Política de Enlaces y Privacidad</h3>
        <p>El sitio puede contener enlaces a sitios web de terceros. Hoteles Nueva España S.L. no asume responsabilidad alguna sobre el contenido o las prácticas de privacidad de dichos sitios.</p>
        
        <h3>5. Legislación Aplicable y Jurisdicción</h3>
        <p>Las presentes condiciones se regirán por la normativa española vigente. Para la resolución de todas las controversias o cuestiones relacionadas con el presente sitio web, serán competentes los Juzgados y Tribunales de la ciudad de Santander.</p>
    </div>
</body>
</html>