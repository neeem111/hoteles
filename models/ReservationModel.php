<?php
// models/ReservationModel.php

class ReservationModel
{
    /**
     * Crea la reserva, asigna habitaciones y marca como no disponibles.
     * Devuelve el Id de la reserva o lanza Exception si algo falla.
     */
    public static function createWithRooms(
        mysqli $conn,
        int $userId,
        int $hotelId,
        int $roomTypeId,
        DateTime $checkInDate,
        DateTime $checkOutDate,
        int $numRooms,
        int $numNights,
        int $numGuests,
        string $comments,
        string $paymentMethod,
        array $roomIds
    ): int {
        $checkInSql  = $checkInDate->format('Y-m-d');
        $checkOutSql = $checkOutDate->format('Y-m-d');
        $bookingDate = date('Y-m-d');
        $status      = "Confirmada"; // o "Pendiente"

        $conn->begin_transaction();

        try {
            // 1. Insert en Reservation
            $sqlReservation = "INSERT INTO Reservation 
                (Id_User, CheckIn_Date, CheckOut_Date, Num_Nights, Booking_date, Status) 
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmtReservation = $conn->prepare($sqlReservation);
            $stmtReservation->bind_param(
                "ississ",
                $userId,
                $checkInSql,
                $checkOutSql,
                $numNights,
                $bookingDate,
                $status
            );

            if (!$stmtReservation->execute()) {
                throw new Exception("Error al crear la reserva");
            }

            $reservationId = $conn->insert_id;

            // 2. Insert en Reservation_Rooms
            $sqlResRoom = "INSERT INTO Reservation_Rooms (Id_Reservation, Id_Room) VALUES (?, ?)";
            $stmtResRoom = $conn->prepare($sqlResRoom);

            foreach ($roomIds as $roomId) {
                $stmtResRoom->bind_param("ii", $reservationId, $roomId);
                if (!$stmtResRoom->execute()) {
                    throw new Exception("Error al asociar habitaciones a la reserva");
                }
            }

            // 3. Marcar habitaciones como no disponibles
            $sqlUpdateRoom = "UPDATE Rooms SET Available = 0 WHERE Id = ?";
            $stmtUpdateRoom = $conn->prepare($sqlUpdateRoom);

            foreach ($roomIds as $roomId) {
                $stmtUpdateRoom->bind_param("i", $roomId);
                if (!$stmtUpdateRoom->execute()) {
                    throw new Exception("Error al actualizar disponibilidad de habitaciones");
                }
            }

            // (Futuro) aquí podríamos crear la factura usando $paymentMethod

            $conn->commit();
            return $reservationId;

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
