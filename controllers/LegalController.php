<?php
// controllers/LegalController.php

class LegalController
{
    public function avisoLegal(): void
    {
        // Estado de sesión para el navbar u otros usos
        $is_logged_in = isset($_SESSION['user_id']);
        $user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';

        // Cargar la vista
        require __DIR__ . '/../views/legal/aviso_legal.php';
    }
}
