<?php
// controllers/HotelController.php

require_once __DIR__ . '/../models/HotelModel.php';
require_once __DIR__ . '/../models/RoomTypeModel.php';

class HotelController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function show(): void
    {
        // 1. Validar parámetro
        $hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;

        if ($hotel_id <= 0) {
            header("Location: index.php?error=Hotel+no+especificado");
            exit();
        }

        // 2. Cargar datos del hotel
        $hotel = HotelModel::getById($this->conn, $hotel_id);
        if (!$hotel) {
            header("Location: index.php?error=Hotel+no+encontrado");
            exit();
        }

        // 3. Cargar tipos de habitación disponibles para ese hotel
        $roomTypes = RoomTypeModel::getTypesByHotel($this->conn, $hotel_id);

        // 4. Aplicar lógica de precios (de momento, precio = CostPerNight)
        $habitacionesDisponibles = [];
        foreach ($roomTypes as $tipo) {
            $tipo['PrecioNoche'] = number_format($tipo['CostPerNight'], 2, '.', '');
            $habitacionesDisponibles[] = $tipo;
        }

        // 5. Info de sesión para el navbar / saludo
        $is_logged_in = isset($_SESSION['user_id']);
        $user_name    = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';

        $ciudad        = $hotel['City'];
        $nombreCadena  = "Hoteles Nueva España S.L.";

        // Variables que usará la vista
        $hotelView                = $hotel;
        $ciudadView               = $ciudad;
        $habitacionesDisponiblesView = $habitacionesDisponibles;
        $isLoggedInView           = $is_logged_in;
        $userNameView             = $user_name;
        $nombreCadenaView         = $nombreCadena;

        require __DIR__ . '/../views/hotel/show.php';
    }
}
