<?php
// controllers/HomeController.php

require_once __DIR__ . '/../models/HotelModel.php';

class HomeController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        // Info cadena
        $nombreCadena = "Hoteles Nueva España S.L.";
        $ciudadesDisponibles = ['Valencia', 'Santander', 'Toledo'];

        // Lógica de precios por ciudad (como en tu index antiguo)
        $tarifasBase = [
            'Toledo'   => 20,
            'Valencia' => 30,
            'Santander'=> 25
        ];
        $incrementoPorCiudad = [
            'Toledo'   => 15,
            'Valencia' => 12,
            'Santander'=> 10
        ];
        // (de momento solo usamos tarifasBase para el "Desde")

        // Sesión
        $is_logged_in = isset($_SESSION['user_id']);
        $user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';
        $userRole  = $is_logged_in ? ($_SESSION['user_role'] ?? '') : '';

        // Filtro ciudad
        $filtroCiudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';

        // Obtener hoteles desde el modelo
        $hoteles = HotelModel::getHotels($this->conn, $filtroCiudad, $ciudadesDisponibles);

        // Añadir PrecioDesde a cada hotel
        foreach ($hoteles as &$hotel) {
            $ciudadHotel = $hotel['City'];
            $hotel['PrecioDesde'] = $tarifasBase[$ciudadHotel] ?? 50;
        }
        unset($hotel);

        // Cargar vista
        require __DIR__ . '/../views/home/index.php';
    }
}
