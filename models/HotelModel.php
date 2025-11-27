<?php
// models/HotelModel.php

class HotelModel
{
    public static function getById(mysqli $conn, int $id): ?array
    {
        $sql = "SELECT Id, Name, City, Address FROM Hotels WHERE Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $hotel = $resultado->fetch_assoc();
        return $hotel ?: null;
    }

    public static function getHotels(mysqli $conn, ?string $filtroCiudad, array $ciudadesDisponibles): array
    {
        $sql = "SELECT Id, Name, City, Address FROM Hotels";
        $hoteles = [];
        $stmt = null;

        if (!empty($filtroCiudad) && in_array($filtroCiudad, $ciudadesDisponibles, true)) {
            $sql .= " WHERE City = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $filtroCiudad);
            $stmt->execute();
            $resultado = $stmt->get_result();
        } else {
            $resultado = $conn->query($sql);
        }

        if ($resultado && $resultado->num_rows > 0) {
            while ($row = $resultado->fetch_assoc()) {
                $hoteles[] = $row;
            }
        }

        if ($stmt) {
            $stmt->close();
        }

        return $hoteles;
    }
}
