<?php
// models/RoomTypeModel.php

class RoomTypeModel
{
    public static function getById(mysqli $conn, int $id): ?array
    {
        $sql = "SELECT Id, Name, Guests, CostPerNight 
                FROM RoomType 
                WHERE Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $roomType = $resultado->fetch_assoc();
        return $roomType ?: null;
    }

    public static function getTypesByHotel(mysqli $conn, int $hotelId): array
    {
        $sql = "SELECT DISTINCT rt.Id, rt.Name, rt.Guests, rt.CostPerNight
                FROM RoomType rt
                INNER JOIN Rooms r ON r.Id_RoomType = rt.Id
                WHERE r.Id_Hotel = ?
                ORDER BY rt.CostPerNight ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $hotelId);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    public static function countAvailableRooms(mysqli $conn, int $hotelId, int $roomTypeId): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM Rooms
                WHERE Id_Hotel = ? AND Id_RoomType = ? AND Available = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $hotelId, $roomTypeId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        return (int)$resultado['total'];
    }

    public static function getAvailableRoomIds(mysqli $conn, int $hotelId, int $roomTypeId, int $limit): array
    {
        $sql = "SELECT Id 
                FROM Rooms
                WHERE Id_Hotel = ? AND Id_RoomType = ? AND Available = 1
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $hotelId, $roomTypeId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $rooms[] = (int)$row['Id'];
        }
        return $rooms;
    }
}
