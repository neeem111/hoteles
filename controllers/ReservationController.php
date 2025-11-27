<?php
// controllers/ReservationController.php

require_once __DIR__ . '/../models/HotelModel.php';
require_once __DIR__ . '/../models/RoomTypeModel.php';
require_once __DIR__ . '/../models/ReservationModel.php';

class ReservationController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // GET → muestra el formulario de reserva
    public function showForm(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header("Location: login.php?msg=Debes+iniciar+sesion+para+reservar");
            exit();
        }

        $hotelId    = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
        $roomTypeId = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;

        if ($hotelId <= 0 || $roomTypeId <= 0) {
            header("Location: index.php?error=Hotel+o+tipo+de+habitacion+no+validos");
            exit();
        }

        $hotel    = HotelModel::getById($this->conn, $hotelId);
        $roomType = RoomTypeModel::getById($this->conn, $roomTypeId);

        if (!$hotel || !$roomType) {
            header("Location: index.php?error=Hotel+o+tipo+de+habitacion+no+encontrado");
            exit();
        }

        $maxHabitaciones = RoomTypeModel::countAvailableRooms($this->conn, $hotelId, $roomTypeId);
        $precioPorNoche  = number_format($roomType['CostPerNight'], 2, '.', '');

        $ciudad     = $hotel['City'];
        $user_name  = $_SESSION['user_name']  ?? '';
        $user_email = $_SESSION['user_email'] ?? '';

        // Variables para la vista
        $hotelView             = $hotel;
        $roomTypeView          = $roomType;
        $hotelIdView           = $hotelId;
        $roomTypeIdView        = $roomTypeId;
        $maxHabitacionesView   = $maxHabitaciones;
        $precioPorNocheView    = $precioPorNoche;
        $ciudadView            = $ciudad;
        $userNameView          = $user_name;
        $userEmailView         = $user_email;

        require __DIR__ . '/../views/reservation/form.php';
    }

    // POST → procesa la reserva
    public function process(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header("Location: login.php?msg=Debes+iniciar+sesion+para+confirmar+la+reserva");
            exit();
        }

        $userId = (int)$_SESSION['user_id'];

        $hotelId      = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0;
        $roomTypeId   = isset($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : 0;
        $checkIn      = trim($_POST['check_in'] ?? '');
        $checkOut     = trim($_POST['check_out'] ?? '');
        $numRooms     = isset($_POST['num_rooms']) ? (int)$_POST['num_rooms'] : 0;
        $numGuests    = isset($_POST['num_guests']) ? (int)$_POST['num_guests'] : 0;
        $comments     = trim($_POST['comments'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? '');

        $backUrl = "index.php?page=reserva&hotel_id={$hotelId}&room_type_id={$roomTypeId}";

        if ($hotelId <= 0 || $roomTypeId <= 0) {
            header("Location: {$backUrl}&error=Hotel+o+tipo+de+habitacion+no+validos");
            exit();
        }

        if (empty($checkIn) || empty($checkOut)) {
            header("Location: {$backUrl}&error=Debes+indicar+las+fechas");
            exit();
        }

        if ($numRooms <= 0) {
            header("Location: {$backUrl}&error=Numero+de+habitaciones+no+valido");
            exit();
        }

        if ($numGuests <= 0) {
            header("Location: {$backUrl}&error=Numero+de+huespedes+no+valido");
            exit();
        }

        if (empty($paymentMethod)) {
            header("Location: {$backUrl}&error=Debes+seleccionar+un+metodo+de+pago");
            exit();
        }

        try {
            $checkInDate  = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
        } catch (Exception $e) {
            header("Location: {$backUrl}&error=Fechas+no+validas");
            exit();
        }

        $interval  = $checkInDate->diff($checkOutDate);
        $numNights = (int)$interval->days;

        if ($numNights <= 0) {
            header("Location: {$backUrl}&error=La+fecha+de+salida+debe+ser+posterior+a+la+de+entrada");
            exit();
        }

        // Verificar habitaciones disponibles
        $availableIds = RoomTypeModel::getAvailableRoomIds($this->conn, $hotelId, $roomTypeId, $numRooms);
        if (count($availableIds) < $numRooms) {
            header("Location: {$backUrl}&error=No+hay+suficientes+habitaciones+disponibles");
            exit();
        }

        try {
            $reservationId = ReservationModel::createWithRooms(
                $this->conn,
                $userId,
                $hotelId,
                $roomTypeId,
                $checkInDate,
                $checkOutDate,
                $numRooms,
                $numNights,
                $numGuests,
                $comments,
                $paymentMethod,
                $availableIds
            );
        } catch (Exception $e) {
            // error_log("Error al crear la reserva: " . $e->getMessage());
            header("Location: {$backUrl}&error=No+se+ha+podido+crear+la+reserva");
            exit();
        }

        $reservationIdView = $reservationId;
        require __DIR__ . '/../views/reservation/success.php';
    }
}
