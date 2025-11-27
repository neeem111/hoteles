<?php
session_start();

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/LegalController.php';
require_once __DIR__ . '/controllers/HotelController.php';
require_once __DIR__ . '/controllers/ReservationController.php';
require_once __DIR__ . '/controllers/Auth/ClientAuthController.php';


$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'aviso_legal':
        $controller = new LegalController();
        $controller->avisoLegal();
        break;

    case 'hotel':
        $controller = new HotelController($conn);
        $controller->show();
        break;

    case 'reserva':
        $controller = new ReservationController($conn);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->process();
        } else {
            $controller->showForm();
        }
        break;

    case 'client_login':
    $auth = new ClientAuthController($conn);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $auth->loginProcess();
    } else {
        $auth->showLogin();
    }
    break;

    case 'client_register':
        $auth = new ClientAuthController($conn);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth->registerProcess();
        } else {
            $auth->showRegister();
        }
        break;

    case 'home':
    default:
        $controller = new HomeController($conn);
        $controller->index();
        break;
}
