-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-11-2025 a las 12:39:02
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mitienda_bd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hotels`
--

CREATE TABLE `hotels` (
  `Id` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `hotels`
--

INSERT INTO `hotels` (`Id`, `Name`, `City`, `Address`) VALUES
(1, 'Hotel Nueva España - Valencia', 'Valencia', 'Avenida del Puerto, 34'),
(2, 'Hotel Nueva España - Santander', 'Santander', 'Paseo de Pereda, 25'),
(3, 'Hotel Nueva España - Toledo', 'Toledo', 'Calle del Cardenal Cisneros, 8'),
(4, 'Hotel Nueva España - Valencia', 'Valencia', 'Avenida del Puerto, 34'),
(5, 'Hotel Nueva España - Santander', 'Santander', 'Paseo de Pereda, 25'),
(6, 'Hotel Nueva España - Toledo', 'Toledo', 'Calle del Cardenal Cisneros, 8'),
(7, 'Hotel Nueva España - Valencia', 'Valencia', 'Avenida del Puerto, 34'),
(8, 'Hotel Nueva España - Santander', 'Santander', 'Paseo de Pereda, 25'),
(9, 'Hotel Nueva España - Toledo', 'Toledo', 'Calle del Cardenal Cisneros, 8'),
(10, 'Hotel Nueva España - Valencia', 'Valencia', 'Avenida del Puerto, 34'),
(11, 'Hotel Nueva España - Santander', 'Santander', 'Paseo de Pereda, 25'),
(12, 'Hotel Nueva España - Toledo', 'Toledo', 'Calle del Cardenal Cisneros, 8'),
(13, 'Hotel Nueva España - Valencia', 'Valencia', 'Avenida del Puerto, 34'),
(14, 'Hotel Nueva España - Santander', 'Santander', 'Paseo de Pereda, 25'),
(15, 'Hotel Nueva España - Toledo', 'Toledo', 'Calle del Cardenal Cisneros, 8');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

CREATE TABLE `invoices` (
  `Id` int(11) NOT NULL,
  `Id_Reservation` int(11) NOT NULL,
  `Id_User` int(11) NOT NULL,
  `InvoiceNumber` varchar(50) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `IVA` decimal(10,2) DEFAULT NULL,
  `Total` decimal(10,2) DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservation`
--

CREATE TABLE `reservation` (
  `Id` int(11) NOT NULL,
  `Id_User` int(11) NOT NULL,
  `CheckIn_Date` date DEFAULT NULL,
  `CheckOut_Date` date DEFAULT NULL,
  `Num_Nights` int(11) DEFAULT NULL,
  `Booking_date` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservation_rooms`
--

CREATE TABLE `reservation_rooms` (
  `Id` int(11) NOT NULL,
  `Id_Reservation` int(11) NOT NULL,
  `Id_Room` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rooms`
--

CREATE TABLE `rooms` (
  `Id` int(11) NOT NULL,
  `Id_RoomType` int(11) NOT NULL,
  `Available` tinyint(1) DEFAULT 1,
  `Id_Hotel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rooms`
--

INSERT INTO `rooms` (`Id`, `Id_RoomType`, `Available`, `Id_Hotel`) VALUES
(1, 1, 1, 1),
(2, 2, 1, 1),
(3, 3, 1, 1),
(4, 4, 1, 1),
(5, 1, 1, 2),
(6, 2, 1, 2),
(7, 3, 0, 2),
(8, 1, 1, 3),
(9, 2, 1, 3),
(10, 4, 1, 3),
(11, 1, 1, 1),
(12, 2, 1, 1),
(13, 3, 1, 1),
(14, 4, 1, 1),
(15, 1, 1, 2),
(16, 2, 1, 2),
(17, 3, 0, 2),
(18, 1, 1, 3),
(19, 2, 1, 3),
(20, 4, 1, 3),
(21, 1, 1, 1),
(22, 2, 1, 1),
(23, 3, 1, 1),
(24, 4, 1, 1),
(25, 1, 1, 2),
(26, 2, 1, 2),
(27, 3, 0, 2),
(28, 1, 1, 3),
(29, 2, 1, 3),
(30, 4, 1, 3),
(31, 1, 1, 1),
(32, 2, 1, 1),
(33, 3, 1, 1),
(34, 4, 1, 1),
(35, 1, 1, 2),
(36, 2, 1, 2),
(37, 3, 0, 2),
(38, 1, 1, 3),
(39, 2, 1, 3),
(40, 4, 1, 3),
(41, 1, 1, 1),
(42, 2, 1, 1),
(43, 3, 1, 1),
(44, 4, 1, 1),
(45, 1, 1, 2),
(46, 2, 1, 2),
(47, 3, 0, 2),
(48, 1, 1, 3),
(49, 2, 1, 3),
(50, 4, 1, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roomtype`
--

CREATE TABLE `roomtype` (
  `Id` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Guests` int(11) DEFAULT NULL,
  `CostPerNight` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roomtype`
--

INSERT INTO `roomtype` (`Id`, `Name`, `Guests`, `CostPerNight`) VALUES
(1, 'Individual', 1, 65.00),
(2, 'Doble Estándar', 2, 95.00),
(3, 'Doble con Vistas', 2, 110.00),
(4, 'Suite Junior', 2, 180.00),
(5, 'Familiar (4 personas)', 4, 150.00),
(6, 'Suite Presidencial', 4, 350.00),
(7, 'Solo para Dos (Oferta)', 2, 80.00),
(8, 'Doble Deluxe', 2, 120.00),
(9, 'Individual (Económica)', 1, 55.00),
(10, 'Estudio', 2, 90.00),
(11, 'Apartamento', 4, 185.00),
(12, 'Suite con Jacuzzi', 2, 250.00),
(13, 'Individual', 1, 65.00),
(14, 'Doble Estándar', 2, 95.00),
(15, 'Doble con Vistas', 2, 110.00),
(16, 'Suite Junior', 2, 180.00),
(17, 'Familiar (4 personas)', 4, 150.00),
(18, 'Suite Presidencial', 4, 350.00),
(19, 'Solo para Dos (Oferta)', 2, 80.00),
(20, 'Doble Deluxe', 2, 120.00),
(21, 'Individual (Económica)', 1, 55.00),
(22, 'Estudio', 2, 90.00),
(23, 'Apartamento', 4, 185.00),
(24, 'Suite con Jacuzzi', 2, 250.00),
(25, 'Individual', 1, 65.00),
(26, 'Doble Estándar', 2, 95.00),
(27, 'Doble con Vistas', 2, 110.00),
(28, 'Suite Junior', 2, 180.00),
(29, 'Familiar (4 personas)', 4, 150.00),
(30, 'Suite Presidencial', 4, 350.00),
(31, 'Solo para Dos (Oferta)', 2, 80.00),
(32, 'Doble Deluxe', 2, 120.00),
(33, 'Individual (Económica)', 1, 55.00),
(34, 'Estudio', 2, 90.00),
(35, 'Apartamento', 4, 185.00),
(36, 'Suite con Jacuzzi', 2, 250.00),
(37, 'Individual', 1, 65.00),
(38, 'Doble Estándar', 2, 95.00),
(39, 'Doble con Vistas', 2, 110.00),
(40, 'Suite Junior', 2, 180.00),
(41, 'Familiar (4 personas)', 4, 150.00),
(42, 'Suite Presidencial', 4, 350.00),
(43, 'Solo para Dos (Oferta)', 2, 80.00),
(44, 'Doble Deluxe', 2, 120.00),
(45, 'Individual (Económica)', 1, 55.00),
(46, 'Estudio', 2, 90.00),
(47, 'Apartamento', 4, 185.00),
(48, 'Suite con Jacuzzi', 2, 250.00),
(49, 'Individual', 1, 65.00),
(50, 'Doble Estándar', 2, 95.00),
(51, 'Doble con Vistas', 2, 110.00),
(52, 'Suite Junior', 2, 180.00),
(53, 'Familiar (4 personas)', 4, 150.00),
(54, 'Suite Presidencial', 4, 350.00),
(55, 'Solo para Dos (Oferta)', 2, 80.00),
(56, 'Doble Deluxe', 2, 120.00),
(57, 'Individual (Económica)', 1, 55.00),
(58, 'Estudio', 2, 90.00),
(59, 'Apartamento', 4, 185.00),
(60, 'Suite con Jacuzzi', 2, 250.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `Id` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Age` int(11) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Rol` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`Id`, `Name`, `Age`, `Address`, `Email`, `Password`, `Rol`) VALUES
(64, 'Carlos Alberto Mejía Vergara', 27, 'Privada de ejemplo 22', 'carlytos.mejia@outlook.com', '$2y$10$rSaY1iUPQoSO28Als1OcDuJutO3D6rfTCyd6tExh/KvWcbUBHJGJO', 'Administrador'),
(65, 'Fernando Mejia Castillo', 21, 'Calle ejemplo (cerca de la bombonera)', 'fmejiacastillo@gmail.com', '$2y$10$VmZ7hEP/uTntag4ALZP3uOe7UGWE9M2v4l.lOu2lvEYGqLKDhP21.', 'Cliente'),
(66, 'Carmen', 21, 'Ejemplo', 'ejemplo@1.es', '$2y$10$lrZpaghX7tDqTFKB0pYl5OksI/Ok4s0VX6haG04nrniSYajyugj5a', 'Administrador'),
(67, 'Julieta Itzel Pichardo Meza', 21, 'En su casa', 'july.pichardo.meza@gmail.com', '$2y$10$UiWOsMfreloX0wGNL.4UT.moFwQNEHZFloixfPfjt2GUOttT90gQq', 'Cliente');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `InvoiceNumber` (`InvoiceNumber`),
  ADD KEY `Id_Reservation` (`Id_Reservation`),
  ADD KEY `Id_User` (`Id_User`);

--
-- Indices de la tabla `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_User` (`Id_User`);

--
-- Indices de la tabla `reservation_rooms`
--
ALTER TABLE `reservation_rooms`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_Reservation` (`Id_Reservation`),
  ADD KEY `Id_Room` (`Id_Room`);

--
-- Indices de la tabla `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_RoomType` (`Id_RoomType`),
  ADD KEY `Id_Hotel` (`Id_Hotel`);

--
-- Indices de la tabla `roomtype`
--
ALTER TABLE `roomtype`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `hotels`
--
ALTER TABLE `hotels`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `invoices`
--
ALTER TABLE `invoices`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservation`
--
ALTER TABLE `reservation`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservation_rooms`
--
ALTER TABLE `reservation_rooms`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rooms`
--
ALTER TABLE `rooms`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `roomtype`
--
ALTER TABLE `roomtype`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`Id_Reservation`) REFERENCES `reservation` (`Id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`Id_User`) REFERENCES `users` (`Id`);

--
-- Filtros para la tabla `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`Id_User`) REFERENCES `users` (`Id`);

--
-- Filtros para la tabla `reservation_rooms`
--
ALTER TABLE `reservation_rooms`
  ADD CONSTRAINT `reservation_rooms_ibfk_1` FOREIGN KEY (`Id_Reservation`) REFERENCES `reservation` (`Id`),
  ADD CONSTRAINT `reservation_rooms_ibfk_2` FOREIGN KEY (`Id_Room`) REFERENCES `rooms` (`Id`);

--
-- Filtros para la tabla `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`Id_RoomType`) REFERENCES `roomtype` (`Id`),
  ADD CONSTRAINT `rooms_ibfk_2` FOREIGN KEY (`Id_Hotel`) REFERENCES `hotels` (`Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
