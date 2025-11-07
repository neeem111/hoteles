-- Sentencia USE para asegurar que las tablas se creen en tu BD.
USE mitienda_bd;

-- 1. Tabla de Tipos de Habitación (RoomType)
CREATE TABLE RoomType (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100),
    Guests INT,
    CostPerNight DECIMAL(10, 2)
);

-- 2. Tabla de Hoteles (Hotels)
CREATE TABLE Hotels (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(255),
    City VARCHAR(100),
    Address VARCHAR(255)
);

-- 3. Tabla de Habitaciones (Rooms)
CREATE TABLE Rooms (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Id_RoomType INT NOT NULL,
    Available BOOLEAN DEFAULT 1,
    Id_Hotel INT NOT NULL,
    -- Definición de Claves Foráneas
    FOREIGN KEY (Id_RoomType) REFERENCES RoomType(Id),
    FOREIGN KEY (Id_Hotel) REFERENCES Hotels(Id)
);

-- 4. Tabla de Usuarios (Users)
CREATE TABLE Users (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100),
    Age INT,
    Address VARCHAR(255),
    Email VARCHAR(100) UNIQUE,
    Password VARCHAR(255)
);

-- 5. Tabla de Reservaciones (Reservation)
CREATE TABLE Reservation (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Id_User INT NOT NULL,
    CheckIn_Date DATE,
    CheckOut_Date DATE,
    Num_Nights INT,
    Booking_date DATE,
    Status VARCHAR(50),
    -- Definición de Clave Foránea
    FOREIGN KEY (Id_User) REFERENCES Users(Id)
);

-- 6. Tabla de Unión (Reservacion_Habitaciones)
CREATE TABLE Reservation_Rooms (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Id_Reservation INT NOT NULL,
    Id_Room INT NOT NULL,
    -- Definición de Claves Foráneas
    FOREIGN KEY (Id_Reservation) REFERENCES Reservation(Id),
    FOREIGN KEY (Id_Room) REFERENCES Rooms(Id)
);

-- 7. Tabla de Facturas (Invoices)
CREATE TABLE Invoices (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Id_Reservation INT NOT NULL,
    Id_User INT NOT NULL,
    InvoiceNumber VARCHAR(50) UNIQUE,
    Date DATE,
    Subtotal DECIMAL(10, 2),
    IVA DECIMAL(10, 2),
    Total DECIMAL(10, 2),
    PaymentMethod VARCHAR(50),
    Status VARCHAR(50),
    -- Definición de Claves Foráneas
    FOREIGN KEY (Id_Reservation) REFERENCES Reservation(Id),
    FOREIGN KEY (Id_User) REFERENCES Users(Id)
);

-- 8. Tabla de Items de Factura (InvoiceItems)
CREATE TABLE InvoiceItems (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Id_Invoice INT NOT NULL,
    Description VARCHAR(255),
    Quantity INT,
    UnitPrice DECIMAL(10, 2),
    Total DECIMAL(10, 2),
    -- Definición de Clave Foránea
    FOREIGN KEY (Id_Invoice) REFERENCES Invoices(Id)
);
-----------------------------------------------------------------------------------------DATOS

-- 1. Insertar Hoteles (3 Filas)
INSERT INTO Hotels (Name, City, Address) VALUES 
('Hotel Nueva España - Valencia', 'Valencia', 'Avenida del Puerto, 34'),
('Hotel Nueva España - Santander', 'Santander', 'Paseo de Pereda, 25'),
('Hotel Nueva España - Toledo', 'Toledo', 'Calle del Cardenal Cisneros, 8');


-- 2. Insertar Tipos de Habitación (12 Filas)
INSERT INTO RoomType (Name, Guests, CostPerNight) VALUES
('Individual', 1, 65.00), -- 1
('Doble Estándar', 2, 95.00), -- 2
('Doble con Vistas', 2, 110.00), -- 3
('Suite Junior', 2, 180.00), -- 4
('Familiar (4 personas)', 4, 150.00), -- 5
('Suite Presidencial', 4, 350.00), -- 6
('Solo para Dos (Oferta)', 2, 80.00), -- 7    
('Doble Deluxe', 2, 120.00), -- 8
('Individual (Económica)', 1, 55.00), -- 9
('Estudio', 2, 90.00), -- 10
('Apartamento', 4, 185.00), -- 11
('Suite con Jacuzzi', 2, 250.00); -- 12

-- 3. Insertar Habitaciones (10 Filas)
-- Usaremos Id_RoomType del 1 al 4
INSERT INTO Rooms (Id_RoomType, Available, Id_Hotel) VALUES
-- Hotel 1 (Valencia)
(1, 1, 1), -- Individual
(2, 1, 1), -- Doble Estándar
(3, 1, 1), -- Doble con Vistas
(4, 1, 1), -- Suite Junior
-- Hotel 2 (Santander)
(1, 1, 2), -- Individual
(2, 1, 2), -- Doble Estándar
(3, 0, 2), -- Doble con Vistas (Ocupada)
-- Hotel 3 (Toledo)
(1, 1, 3), -- Individual
(2, 1, 3), -- Doble Estándar
(4, 1, 3); -- Suite Junior

-- 4. Insertar Usuarios (10 Filas)
INSERT INTO Users (Name, Age, Address, Email, Password) VALUES 
('Juan Pérez', 35, 'C/ Ejemplo 1', 'juan.perez@test.es', '123456'),
('Ana García', 28, 'Av. Inventada 2', 'ana.garcia@test.es', '123456'),
('Luis López', 42, 'Pza. Ficticia 3', 'luis.lopez@test.es', '123456'),
('María Sanz', 22, 'Rambla del Mar 4', 'maria.sanz@test.es', '123456'),
('Carlos Ruiz', 50, 'C/ Solitaria 5', 'carlos.ruiz@test.es', '123456'),
('Elena Vidal', 30, 'Av. Luna 6', 'elena.vidal@test.es', '123456'),
('David Gómez', 38, 'Paseo Río 7', 'david.gomez@test.es', '123456'),
('Sofía Martín', 25, 'C/ Estrellas 8', 'sofia.martin@test.es', '123456'),
('Javier Torres', 45, 'Av. Montaña 9', 'javier.torres@test.es', '123456'),
('Laura Castro', 33, 'C/ Bosque 10', 'laura.castro@test.es', '123456');


-- 5. Insertar Reservaciones (10 Filas)
INSERT INTO Reservation (Id_User, CheckIn_Date, CheckOut_Date, Num_Nights, Booking_date, Status) VALUES
(1, '2025-11-10', '2025-11-12', 2, '2025-10-25', 'Confirmada'),
(2, '2025-12-05', '2025-12-10', 5, '2025-10-26', 'Confirmada'),
(3, '2025-11-15', '2025-11-16', 1, '2025-10-27', 'Confirmada'),
(4, '2026-01-01', '2026-01-05', 4, '2025-10-28', 'Pendiente'),
(5, '2025-12-20', '2025-12-25', 5, '2025-10-29', 'Confirmada'),
(6, '2025-11-01', '2025-11-03', 2, '2025-10-30', 'Cancelada'),
(7, '2026-02-14', '2026-02-16', 2, '2025-10-30', 'Confirmada'),
(8, '2025-12-12', '2025-12-15', 3, '2025-10-30', 'Confirmada'),
(9, '2025-11-20', '2025-11-21', 1, '2025-10-30', 'Confirmada'),
(10, '2025-11-25', '2025-11-29', 4, '2025-10-30', 'Pendiente');
