<?php
session_start(); // Iniciar sesión al comienzo del archivo
// Define la información de la cadena hotelera
$nombreCadena = "Hoteles Nueva España S.L.";
$ciudadesDisponibles = ['Valencia', 'Santander', 'Toledo'];

// --- LÓGICA DE PRECIOS POR CIUDAD ---
$tarifasBase = [
    'Toledo' => 20,
    'Valencia' => 30,
    'Santander' => 25
];
$incrementoPorCiudad = [
    'Toledo' => 15,
    'Valencia' => 12,
    'Santander' => 10
];
// ------------------------------------------

// Comprobar estado de la sesión
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';

include('../Config/conexion.php'); 

// Parámetros de filtrado
$filtroCiudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';

$check_in_filter = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d'); // Usar hoy como defecto
$check_out_filter = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day')); // Mañana como defecto

// Verificar que la conexión sea exitosa
if ($conn->connect_error) {
    die("Error de conexión, revisa conexion.php");
}

// 1. Consulta base para obtener todos los hoteles
$sql = "SELECT Id, Name, City, Address FROM Hotels";
$hoteles = [];
$stmt = null;

// 2. Añadir condición WHERE si hay un filtro de ciudad válido
if (!empty($filtroCiudad) && in_array($filtroCiudad, $ciudadesDisponibles)) {
    // Usar consultas preparadas para mayor seguridad
    $sql .= " WHERE City = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $filtroCiudad);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $resultado = false; 
    }
} else {
    // Consulta sin filtro
    $resultado = $conn->query($sql);
}

if ($resultado && $resultado->num_rows > 0) {
    while($row = $resultado->fetch_assoc()) {
        
        $ciudadHotel = $row['City'];
        // Asignamos el precio base como el precio 'Desde'
        $row['PrecioDesde'] = $tarifasBase[$ciudadHotel] ?? 50; // 50 como fallback seguro
        
        $hoteles[] = $row;
    }
}

if ($stmt) {
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌟 Hoteles Nueva España S.L. - Portal de Reservas</title>
    <style>
        :root {
            --color-primary: #a02040; /* Borgoña/Vino, elegante */
            --color-secondary: #ffc107; /* Dorado/Amarillo */
            --color-dark: #343a40;
            --color-light: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-light);
            margin: 0;
            padding-top: 80px; /* Espacio para el nav fijo */
        }
        
        /* --- Barra de Navegación (Original) --- */
        .navbar {
            background-color: #ffffff; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box; 
        }
        .navbar-brand {
            color: var(--color-primary);
            font-size: 1.8em;
            font-weight: 700;
            text-decoration: none;
        }
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-btn {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
            font-size: 0.95em;
        }
        .btn-login {
            background-color: var(--color-primary);
            color: white;
            border: 1px solid var(--color-primary);
        }
        .btn-login:hover {
            background-color: #801933;
        }
        .btn-signup {
            background-color: white;
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
        }
        .btn-signup:hover {
            background-color: var(--color-primary);
            color: white;
        }
        .user-greeting {
            color: var(--color-dark);
            font-weight: 500;
            font-size: 1em;
        }
        .cart-icon a {
            color: var(--color-dark);
            font-size: 1.5em;
            text-decoration: none;
        }
        .cart-icon a:hover {
            color: var(--color-primary);
        }
        .admin-link {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        .admin-link:hover {
            background: #1e7e34;
        }

        /* --- Estilos del Encabezado Principal (Hero) --- */
        .header {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('../images/hotel_hero_background.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 3.5em;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .header p {
            margin-top: 0;
            font-size: 1.3em;
            opacity: 0.9;
        }

        .container {
            padding: 20px;
            max-width: 1300px;
            margin: 0 auto;
        }
        
        h2 {
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 40px;
            font-weight: 700;
            font-size: 2.2em;
            border-bottom: 3px solid var(--color-secondary);
            display: inline-block;
            padding-bottom: 5px;
            margin-left: 50%;
            transform: translateX(-50%);
        }

        /* --- Estilos del Filtro de Búsqueda --- */
        .search-filter {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap; 
            gap: 20px;
        }
        .search-filter form { 
            display:flex; 
            flex-wrap: wrap; 
            justify-content: center; 
            gap:20px; 
            align-items: flex-end;
        }
        .search-filter label {
            font-weight: 600;
            color: var(--color-dark);
            display: block;
            margin-bottom: 5px;
            text-align: left;
        }
        .search-filter select, 
        .search-filter input[type="date"],
        .search-filter .search-item button { /* Incluye el botón aquí para consistencia de altura */
            padding: 12px 18px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
            height: 48px; /* Altura fija para alineación perfecta */
        }
        
        .search-filter select,
        .search-filter input[type="date"] {
            border: 1px solid #ccc;
        }

        /* Estilos específicos para el contenedor de la búsqueda */
        .search-item {
            min-width: 150px;
            max-width: 250px;
        }

        /* Estilos específicos para los botones */
        .search-filter button {
            background-color: #28a745; 
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
        }
        #clear-filters-btn {
            background-color: #ccc !important; 
            color: #555 !important;
            border: 1px solid #ccc !important;
        }
        #clear-filters-btn:hover {
            background-color: #bbb !important;
        }
        .search-filter button:hover {
            background-color: #218838;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            font-weight: 600;
            margin-top: 5px;
            width: 100%;
            text-align: center;
            display: none; /* Oculto por defecto */
        }
        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .hotel-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            display: flex;
            flex-direction: column;
        }
        .hotel-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .hotel-image {
            height: 200px; 
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 1.4em;
            font-weight: 600;
            overflow: hidden; 
        }
        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
        }

        .hotel-content { padding: 20px; flex-grow: 1; }
        .hotel-name { color: var(--color-dark); margin-top: 0; margin-bottom: 10px; font-size: 1.8em; border-left: 4px solid var(--color-secondary); padding-left: 10px; }
        .hotel-details p { margin: 8px 0; color: #555; font-size: 1em; line-height: 1.4; }
        .price-tag { background-color: var(--color-primary); color: white; padding: 8px 15px; border-radius: 5px; font-weight: 700; font-size: 1.3em; display: inline-block; margin-top: 15px; }
        .btn-reserve { display: block; margin-top: 20px; text-align: center; background-color: var(--color-secondary); color: var(--color-dark); padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; transition: background-color 0.2s; }
        .btn-reserve:hover { background-color: #e0ac00; }
        .no-results { text-align: center; padding: 60px; background-color: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); margin: 50px auto; max-width: 600px; }
        .footer { background-color: var(--color-dark); color: #ccc; padding: 30px 20px; margin-top: 50px; text-align: center; font-size: 0.9em; }
        .footer a { color: var(--color-secondary); text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Hoteles NESL</a>
        
        <div class="navbar-actions">

        <?php if ($is_logged_in): ?>
        <a href="mis_pedidos.php" class="nav-btn btn-login" style="background:#5cb5bc; border-color:#5cb85c;">
            📦 Mis Reservas
        </a>
    <?php endif; ?>
            
            <div class="cart-icon">
                <a href="cart/view_cart.php" title="Ver Carrito">🛒 Carrito</a> 
            </div>

            <?php if ($is_logged_in): ?>
                <span class="user-greeting">Bienvenido, <strong><?php echo $user_name; ?></strong></span>
                
                <?php if (isset($_SESSION['user_role']) && strcasecmp($_SESSION['user_role'], 'Administrador') === 0): ?>
                    <a href="../Admin/index.php" class="nav-btn admin-link">Panel Admin</a>
                <?php endif; ?>
                
                <a href="../auth/logout.php" class="nav-btn btn-signup">Cerrar Sesión</a>
            <?php else: ?>
                <a href="../auth/login.php" class="nav-btn btn-login">Iniciar Sesión</a>
                <a href="register.php" class="nav-btn btn-signup">Regístrate</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <header class="header">
        <h1><?php echo $nombreCadena; ?></h1>
        <p>Vive el lujo en los mejores destinos de España. ¡Tu escapada perfecta comienza aquí! 
</p>
    </header>

    <div class="container">
        
        <div class="search-filter">
            <form method="GET" action="index.php" id="search-form" style="display:flex; flex-wrap: wrap; justify-content: center; gap:20px;">
                
                <div class="search-item">
                    <label for="ciudad">Destino:</label>
                    <select name="ciudad" id="ciudad">
                        <option value="">Todas</option>
                        <?php foreach ($ciudadesDisponibles as $ciudad): ?>
                            <option value="<?php echo htmlspecialchars($ciudad); ?>" 
                                <?php echo ($filtroCiudad === $ciudad) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ciudad); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="search-item">
                    <label for="check_in">Check-in:</label>
                    <input type="date" name="check_in" id="check_in" 
                           value="<?php echo htmlspecialchars($check_in_filter); ?>">
                </div>
                
                <div class="search-item">
                    <label for="check_out">Check-out:</label>
                    <input type="date" name="check_out" id="check_out" 
                           value="<?php echo htmlspecialchars($check_out_filter); ?>">
                </div>

                <div class="search-item" style="max-width: 100px;">
                    <button type="submit">🔍 Buscar</button>
                </div>
                
                <div class="search-item" style="max-width: 100px;">
                    <button type="button" id="clear-filters-btn">
                        ❌ Limpiar
                    </button>
                </div>

                <div class="error-message" id="date-error"></div>
            </form>
        </div>
        
        <h2>Ofertas Destacadas</h2>
        
        <?php if (count($hoteles) > 0): ?>
            <div class="hotel-grid">
                <?php foreach ($hoteles as $hotel): ?>
                    <div class="hotel-card">
                           <div class="hotel-image">
                                <img src="../images/<?php echo strtolower(str_replace(' ', '_', $hotel['City'])); ?>_hotel.jpg?v=1.0" alt="Foto del Hotel en <?php echo htmlspecialchars($hotel['City']); ?>">
                           </div>
                         <div class="hotel-content">
                            <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['Name']); ?></h3>
                            <div class="hotel-details">
                                <p><strong>📍 Ciudad:</strong> <?php echo htmlspecialchars($hotel['City']); ?></p>
                                <p><strong>🗺️ Dirección:</strong> <?php echo htmlspecialchars($hotel['Address']); ?></p>
                            </div>
                            
                            <div class="price-tag">
                                Desde <strong>$<?php echo $hotel['PrecioDesde']; ?></strong>/noche 
                            </div>
                            
                            <a href="../Publico/hotel.php?hotel_id=<?php echo $hotel['Id']; ?>&check_in=<?php echo urlencode($check_in_filter); ?>&check_out=<?php echo urlencode($check_out_filter); ?>" class="btn-reserve">
                                Ver Habitaciones
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>⚠️ No se encontraron hoteles en la base de datos o que coincidan con la búsqueda.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> <?php echo $nombreCadena; ?>. | <a href="../Publico/aviso_legal.php">Aviso Legal</a> | <a href="../Publico/contacto.php">Contáctanos</a></p>
    </footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const form = document.getElementById('search-form');
        const errorDiv = document.getElementById('date-error');
        const clearButton = document.getElementById('clear-filters-btn');
        const today = new Date().toISOString().split('T')[0];

        // 1. Establecer el mínimo en la fecha de hoy
        checkInInput.setAttribute('min', today);
        checkOutInput.setAttribute('min', today);

        function validateDates(event) {
            const checkInValue = checkInInput.value;
            const checkOutValue = checkOutInput.value;

            if (!checkInValue || !checkOutValue) {
                // Si falta alguna fecha, no bloqueamos el formulario, pero mostramos advertencia
                errorDiv.style.display = 'block';
                errorDiv.textContent = 'Recomendamos seleccionar ambas fechas para ver la disponibilidad real.';
                return true; 
            }

            const checkIn = new Date(checkInValue);
            const checkOut = new Date(checkOutValue);

            // 2. Comprobar que Check-out > Check-in
            if (checkOut <= checkIn) {
                errorDiv.style.display = 'block';
                errorDiv.textContent = 'La salida debe ser posterior a la entrada.';
                if (event && event.preventDefault) {
                    event.preventDefault(); // Detener el envío del formulario
                }
                return false;
            }
            
            errorDiv.style.display = 'none';
            return true;
        }
        
        // --- FUNCIONALIDAD: LIMPIEZA ---
        clearButton.addEventListener('click', function() {
            // Redirigir a la página base sin parámetros GET para limpiar completamente la URL
            window.location.href = 'index.php';
        });

        // Validar al cambiar las fechas
        checkInInput.addEventListener('change', validateDates);
        checkOutInput.addEventListener('change', validateDates);
        
        // Validar al enviar el formulario
        form.addEventListener('submit', validateDates);

        // Inicializar validación (solo si hay valores, para borrar el mensaje de recomendación al cargar)
        if (checkInInput.value && checkOutInput.value) {
            validateDates(); 
        } else {
            // Mostrar mensaje de recomendación al inicio si no hay fechas
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Recomendamos seleccionar ambas fechas para ver la disponibilidad real.';
        }
    });
</script>

</body>
</html>